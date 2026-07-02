<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reports';

    protected string $view = 'filament.app.pages.reports';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('nav.report_builder');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('view_reports') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public function mount(): void
    {
        $workspace = \App\Models\Workspace::find(session('current_workspace_id'));
        $savedBlocks = $workspace?->report_blocks; // last-used selection, if any

        $this->form->fill([
            'period' => 'last_30',
            'location_id' => null,
            'compareMode' => 'previous',
            'language' => 'en',
            'preset' => 'full',
            'blocks' => \App\Support\ReportBlocks::normalize($savedBlocks),
            'ai_instructions' => (string) $workspace?->getAttribute('report_ai_instructions'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->compact()
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('period')
                                ->label(__('common.period'))
                                ->options(__('common.periods'))
                                ->default('last_30')
                                ->selectablePlaceholder(false)
                                ->live(),

                            DatePicker::make('startDate')->label(__('common.from'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('period') === 'custom')->live(),

                            DatePicker::make('endDate')->label(__('common.to'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('period') === 'custom')->live(),

                            Select::make('location_id')
                                ->label(__('pages/reports.location'))
                                ->placeholder(__('common.all_locations'))
                                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->live(),
                        ]),

                        Grid::make(4)->schema([
                            Select::make('compareMode')
                                ->label(__('pages/reports.compare'))
                                ->options(__('pages/reports.compare_options'))
                                ->default('previous')
                                ->selectablePlaceholder(false)
                                ->live(),

                            DatePicker::make('compareStartDate')->label(__('pages/reports.compare_from'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('compareMode') === 'custom')->live(),

                            DatePicker::make('compareEndDate')->label(__('pages/reports.compare_to'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('compareMode') === 'custom')->live(),
                        ]),

                        Grid::make(4)->schema([
                            Select::make('language')
                                ->label(__('pages/reports.report_language'))
                                ->options(['en' => 'English', 'de' => 'Deutsch'])
                                ->default('en')
                                ->selectablePlaceholder(false)
                                ->live(),
                        ]),
                    ]),

                Section::make(__('pages/reports.content_section'))
                    ->description(__('pages/reports.content_section_desc'))
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Select::make('preset')
                            ->label(__('pages/reports.preset'))
                            ->options(\App\Support\ReportBlocks::presetLabels())
                            ->default('full')
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $set('blocks', \App\Support\ReportBlocks::presets()[$state] ?? \App\Support\ReportBlocks::default());
                            }),

                        CheckboxList::make('blocks')
                            ->label(__('pages/reports.blocks'))
                            ->options(\App\Support\ReportBlocks::labels())
                            ->columns(2)
                            ->bulkToggleable()
                            ->live(),

                        // Owner guidance passed to the AI narrative — most useful
                        // for the staff roster and name aliases (Suly = Suleyman).
                        \Filament\Forms\Components\Textarea::make('ai_instructions')
                            ->label(__('pages/reports.ai_instructions'))
                            ->helperText(__('pages/reports.ai_instructions_help'))
                            ->placeholder(__('pages/reports.ai_instructions_placeholder'))
                            ->rows(3)
                            ->maxLength(2000)
                            // Rewrite the owner's rough notes into crisp AI guidance.
                            ->hintAction(
                                \Filament\Actions\Action::make('improveInstructions')
                                    ->label(__('pages/reports.ai_improve'))
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->action(function (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void {
                                        $rough = trim((string) $get('ai_instructions'));

                                        if ($rough === '') {
                                            \Filament\Notifications\Notification::make()->title(__('pages/reports.ai_improve_empty'))->warning()->send();

                                            return;
                                        }

                                        $key = 'report-instr-improve:'.(auth()->id() ?? 'guest');
                                        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, maxAttempts: 10)) {
                                            \Filament\Notifications\Notification::make()->title(__('pages/reports.ai_improve_rate_limited'))->warning()->send();

                                            return;
                                        }
                                        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);

                                        try {
                                            $improved = app(\App\Services\Ai\InstructionImprover::class)->improve(
                                                $rough,
                                                \App\Models\Workspace::find((string) session('current_workspace_id')),
                                            );
                                            $set('ai_instructions', $improved);
                                            \Filament\Notifications\Notification::make()->title(__('pages/reports.ai_improve_done'))->success()->send();
                                        } catch (\Throwable $e) {
                                            \Illuminate\Support\Facades\Log::warning('Report instruction improve failed', ['error' => $e->getMessage()]);
                                            \Filament\Notifications\Notification::make()->title(__('pages/reports.ai_improve_failed'))->danger()->send();
                                        }
                                    }),
                            ),
                    ]),
            ]);
    }

    /** Bumped after Generate to force the preview iframe to reload. */
    public int $generation = 0;

    /** @return array<string, mixed> */
    protected function filterArray(): array
    {
        $d = $this->data;

        return [
            'period' => $d['period'] ?? 'last_30',
            'startDate' => $d['startDate'] ?? null,
            'endDate' => $d['endDate'] ?? null,
            'location_id' => $d['location_id'] ?? null,
            'compareMode' => $d['compareMode'] ?? 'previous',
            'compareStartDate' => $d['compareStartDate'] ?? null,
            'compareEndDate' => $d['compareEndDate'] ?? null,
            'language' => $d['language'] ?? 'en',
        ];
    }

    /** @return array<string, mixed> */
    protected function queryParams(): array
    {
        return array_filter(
            $this->filterArray() + ['g' => $this->generation, 'blocks' => $this->blocksParam()],
            fn ($v): bool => $v !== null && $v !== '',
        );
    }

    /** Enabled report blocks as a comma-separated, canonically-ordered string. */
    protected function blocksParam(): string
    {
        return implode(',', \App\Support\ReportBlocks::normalize($this->data['blocks'] ?? null));
    }

    /** @return array{used:int, cap:int} */
    protected function reportUsage(): array
    {
        $workspace = \App\Models\Workspace::findOrFail(session('current_workspace_id'));
        $usage = app(\App\Services\Billing\AiUsageService::class);

        return ['used' => $usage->reportsThisMonth($workspace), 'cap' => $usage->reportCap($workspace)];
    }

    /** "N of M AI reports left this month" (empty when unlimited / billing off). */
    public function reportsLeftLabel(): string
    {
        ['used' => $used, 'cap' => $cap] = $this->reportUsage();

        if ($cap >= PHP_INT_MAX) {
            return '';
        }

        return __('pages/reports.usage', ['left' => max(0, $cap - $used), 'cap' => $cap]);
    }

    /** Confirm modal before spending an AI report generation. */
    public function generateAction(): \Filament\Actions\Action
    {
        ['used' => $used, 'cap' => $cap] = $this->reportUsage();
        $left = $cap >= PHP_INT_MAX ? null : max(0, $cap - $used);

        return \Filament\Actions\Action::make('generate')
            ->modalIcon('heroicon-o-sparkles')
            ->modalHeading(__('pages/reports.generate_heading'))
            ->modalDescription($left === null
                ? __('pages/reports.generate_desc')
                : __('pages/reports.generate_desc_left', ['left' => $left]))
            ->modalSubmitActionLabel(__('pages/reports.generate_submit'))
            ->action(fn () => $this->generate());
    }

    /** Produce the AI summary for the current selection (the explicit, paid action). */
    public function generate(): void
    {
        $period = \App\Support\DashboardPeriod::fromFilters($this->filterArray());
        $report = app(\App\Services\Reports\ReportData::class)->build($period);
        $workspace = \App\Models\Workspace::findOrFail(session('current_workspace_id'));

        $language = $this->data['language'] ?? 'en';
        $blocks = \App\Support\ReportBlocks::normalize($this->data['blocks'] ?? null);

        // Persist the builder choices BEFORE generating: the AI guidance is read
        // back from the workspace inside ReportInsights, so it must be saved
        // first to apply to this very generation (and to scheduled reports).
        $workspace->report_blocks = implode(',', $blocks);
        $workspace->setAttribute('report_ai_instructions', trim((string) ($this->data['ai_instructions'] ?? '')));
        $workspace->save();

        $result = app(\App\Services\Reports\ReportGenerator::class)->generate($period, $report, $workspace, $language);

        // Save a snapshot (rendered HTML) so it can be re-viewed later without
        // spending another AI generation.
        $previousLocale = app()->getLocale();
        app()->setLocale(in_array($language, ['en', 'de'], true) ? $language : 'en');
        $html = view('reports.monthly', [
            'data' => $report,
            'insights' => $result['insights'],
            'generatedAt' => \Carbon\CarbonImmutable::now()->format('M j, Y'),
            'blocks' => $blocks,
            'brand' => \App\Services\Reports\ReportBranding::for($workspace),
        ])->render();
        app()->setLocale($previousLocale);

        \App\Models\GeneratedReport::create([
            'title' => $report['businessName'],
            'period_label' => $report['periodLabel'],
            'language' => $language,
            'html' => $html,
            'generated_by' => auth()->id(),
            'generated_by_name' => auth()->user()?->name,
        ]);

        $this->generation++; // reloads the iframe → preview now shows the cached AI summary

        \Filament\Notifications\Notification::make()
            ->title($result['ai'] ? __('pages/reports.report_generated') : __('pages/reports.limit_reached'))
            ->body($result['ai']
                ? __('pages/reports.report_generated_body')
                : __('pages/reports.limit_reached_body'))
            ->{$result['ai'] ? 'success' : 'warning'}()
            ->send();
    }

    public function previewUrl(): string
    {
        return route('reports.preview', $this->queryParams());
    }

    public function downloadUrl(): string
    {
        return route('reports.download', $this->queryParams());
    }

    /** True once an AI report has been generated for the current selection. */
    public function reportReady(): bool
    {
        $period = \App\Support\DashboardPeriod::fromFilters($this->filterArray());
        $language = $this->data['language'] ?? 'en';

        return app(\App\Services\Reports\ReportGenerator::class)->hasCached($period, $language);
    }
}
