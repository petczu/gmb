<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Billing\Plans;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use App\Services\Onboarding\OnboardingStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;

/**
 * First-run setup wizard for a brand-new workspace: company details → plan
 * (Stripe Checkout, 14-day trial) → connect the first Google location. Step
 * completion is derived from real data via OnboardingStatus, so returning from
 * the external redirects (Stripe / Google OAuth) resumes on the right step and
 * MarkOnboardingComplete dismisses onboarding once everything is done.
 */
class Onboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'onboarding';

    protected string $view = 'filament.app.pages.onboarding';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function getTitle(): string
    {
        return __('onboarding.wizard_title');
    }

    public static function canAccess(): bool
    {
        $workspace = Workspace::find(session('current_workspace_id'));

        return $workspace !== null && $workspace->isOnboarding();
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    protected function billing(): LocationBilling
    {
        return app(LocationBilling::class);
    }

    public function mount(): void
    {
        $workspace = $this->workspace();

        if (! $workspace->isOnboarding()) {
            $this->redirect('/');

            return;
        }

        $this->form->fill([
            'name' => $workspace->name,
            'billing_country' => $workspace->billing_country ?? 'AT',
            'vat_number' => $workspace->vat_number,
            'address_line1' => $workspace->address_line1,
            'postal_code' => $workspace->postal_code,
            'city' => $workspace->city,
            'plan' => 'growth',
            'interval' => 'monthly',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    $this->companyStep(),
                    $this->planStep(),
                    $this->locationStep(),
                ])
                    ->startOnStep($this->startStep())
                    // No global submit — the last step ends in the Google connect
                    // redirect, and completion is detected from data.
                    ->submitAction(null),
            ]);
    }

    protected function companyStep(): Step
    {
        return Step::make(__('onboarding.step_company_label'))
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->description(__('onboarding.step_company_hint'))
            ->schema([
                TextInput::make('name')->label(__('pages/company.display_name'))->required()->maxLength(120),
                Select::make('billing_country')
                    ->label(__('pages/company.country'))
                    ->options(\App\Support\Countries::list())
                    ->searchable()
                    ->required(),
                TextInput::make('vat_number')->label(__('pages/company.vat_number'))->maxLength(40),
                TextInput::make('address_line1')->label(__('pages/company.address_line1'))->maxLength(200),
                TextInput::make('postal_code')->label(__('pages/company.postal_code'))->maxLength(20),
                TextInput::make('city')->label(__('pages/company.city'))->maxLength(120),
            ])
            ->columns(2)
            ->afterValidation(function (): void {
                $workspace = $this->workspace();
                $state = $this->form->getRawState();

                $workspace->name = (string) $state['name'];
                foreach (['billing_country', 'vat_number', 'address_line1', 'postal_code', 'city'] as $field) {
                    $workspace->setAttribute($field, $state[$field] ?? null);
                }
                $workspace->save();
            });
    }

    protected function planStep(): Step
    {
        $planDone = fn (): bool => ! $this->billing()->enabled()
            || $this->billing()->subscription($this->workspace()) !== null;

        return Step::make(__('onboarding.step_plan_label'))
            ->icon(Heroicon::OutlinedCreditCard)
            ->description(__('onboarding.step_plan_hint'))
            ->visible(fn (): bool => $this->billing()->enabled())
            ->schema([
                Text::make(__('onboarding.wiz_plan_done'))
                    ->visible($planDone),

                Radio::make('plan')
                    ->label(__('onboarding.wiz_plan_pick'))
                    ->options($this->planOptions())
                    ->descriptions($this->planDescriptions())
                    ->hidden($planDone),

                Radio::make('interval')
                    ->label(__('onboarding.wiz_interval'))
                    ->options([
                        'monthly' => __('onboarding.wiz_monthly'),
                        'yearly' => __('onboarding.wiz_yearly'),
                    ])
                    ->inline()
                    ->visible(fn (): bool => Plans::hasYearly() && ! $planDone()),

                Actions::make([
                    Action::make('startTrial')
                        ->label(__('onboarding.wiz_start_trial'))
                        ->icon(Heroicon::OutlinedRocketLaunch)
                        ->action('startTrial'),
                ])->hidden($planDone),
            ])
            ->afterValidation(function () use ($planDone): void {
                if (! $planDone()) {
                    Notification::make()->title(__('onboarding.wiz_plan_required'))->warning()->send();

                    throw new Halt;
                }
            });
    }

    protected function locationStep(): Step
    {
        return Step::make(__('onboarding.step_location_label'))
            ->icon(Heroicon::OutlinedMapPin)
            ->description(__('onboarding.step_location_hint'))
            ->schema([
                Text::make(__('onboarding.wiz_location_body')),

                Actions::make([
                    Action::make('connectGoogle')
                        ->label(__('onboarding.wiz_connect_google'))
                        ->icon(Heroicon::OutlinedLink)
                        ->url(route('zernio.google.connect')),
                ]),
            ]);
    }

    /** Redirect to Stripe Checkout for the picked plan (14-day trial, no card). */
    public function startTrial(): mixed
    {
        $state = $this->form->getRawState();
        $plan = (string) ($state['plan'] ?? 'growth');
        $interval = (string) ($state['interval'] ?? 'monthly');

        $checkout = $this->billing()->checkout(
            $this->workspace(),
            $plan,
            $interval,
            url('/onboarding'),
            url('/onboarding'),
        );

        return redirect()->away($checkout->url);
    }

    /** First not-yet-done step (1-based), so external redirects resume correctly. */
    protected function startStep(): int
    {
        foreach (array_values(app(OnboardingStatus::class)->steps($this->workspace())) as $index => $step) {
            if (! $step['done']) {
                return $index + 1;
            }
        }

        return 1;
    }

    /**
     * @return array<string, string>
     */
    protected function planOptions(): array
    {
        $options = [];
        foreach (Plans::all() as $key => $plan) {
            if ($plan->priceId !== null) {
                $options[$key] = $plan->name.' — €'.$plan->priceUsd.' '.__('onboarding.wiz_per_location');
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected function planDescriptions(): array
    {
        $descriptions = [];
        foreach (Plans::all() as $key => $plan) {
            if ($plan->priceId !== null) {
                $descriptions[$key] = __('onboarding.wiz_plan_desc_'.$key);
            }
        }

        return $descriptions;
    }
}
