<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Clusters\ReportsCluster;
use App\Filament\App\Resources\ReportSchedules\Schemas\ReportScheduleForm;
use App\Models\Competitor;
use App\Models\GeneratedReport;
use App\Models\Location;
use App\Models\ReportSchedule;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Ai\InstructionImprover;
use App\Services\Billing\AiUsageService;
use App\Services\Reports\ReportBranding;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportGenerator;
use App\Support\AiRateLimit;
use App\Support\DashboardPeriod;
use App\Support\ReportBlocks;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $cluster = ReportsCluster::class;

    protected static ?int $navigationSort = 1;

    // Inside the reports cluster: /reports/builder
    protected static ?string $slug = 'builder';

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
        $workspace = Workspace::find(session('current_workspace_id'));
        $savedBlocks = $workspace?->report_blocks; // last-used selection, if any

        $this->form->fill([
            'period' => 'last_30',
            'location_id' => [],
            'compareMode' => 'previous',
            'language' => 'en',
            'preset' => 'full',
            'blocks' => $this->stripUnavailableBlocks(ReportBlocks::normalize($savedBlocks)),
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
                        // One row: period, location, compare, language. The
                        // custom-range date pickers wrap onto the next row only
                        // when their "Custom" option is selected.
                        Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                            Select::make('period')
                                ->label(__('common.period'))
                                ->options(__('common.periods'))
                                ->default('last_30')
                                ->selectablePlaceholder(false)
                                ->live(),

                            Select::make('location_id')
                                ->label(__('pages/reports.location'))
                                ->placeholder(__('common.all_locations'))
                                ->multiple()
                                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->live(),

                            Select::make('compareMode')
                                ->label(__('pages/reports.compare'))
                                ->options(__('pages/reports.compare_options'))
                                ->default('previous')
                                ->selectablePlaceholder(false)
                                ->live(),

                            Select::make('language')
                                ->label(__('pages/reports.report_language'))
                                ->options(['en' => 'English', 'de' => 'Deutsch'])
                                ->default('en')
                                ->selectablePlaceholder(false)
                                ->live(),

                            DatePicker::make('startDate')->label(__('common.from'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('period') === 'custom')->live(),

                            DatePicker::make('endDate')->label(__('common.to'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('period') === 'custom')->live(),

                            DatePicker::make('compareStartDate')->label(__('pages/reports.compare_from'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('compareMode') === 'custom')->live(),

                            DatePicker::make('compareEndDate')->label(__('pages/reports.compare_to'))->native(false)->maxDate(now())
                                ->prefixIcon('heroicon-o-calendar')
                                ->visible(fn (callable $get): bool => $get('compareMode') === 'custom')->live(),
                        ]),
                    ]),

                Section::make(__('pages/reports.content_section'))
                    ->description(__('pages/reports.content_section_desc'))
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Select::make('preset')
                            ->label(__('pages/reports.preset'))
                            ->options(ReportBlocks::presetLabels())
                            ->default('full')
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $set('blocks', $this->stripUnavailableBlocks(ReportBlocks::presets()[$state] ?? ReportBlocks::default()));
                            }),

                        CheckboxList::make('blocks')
                            ->label(__('pages/reports.blocks'))
                            ->options(ReportBlocks::labels())
                            // No competitors tracked → the block would render empty;
                            // disable it and say where to set them up instead.
                            ->disableOptionWhen(fn (string $value): bool => $value === 'competitors' && ! $this->competitorsConfigured())
                            ->descriptions($this->competitorsConfigured() ? [] : ['competitors' => __('pages/reports.competitors_block_hint')])
                            ->columns(2)
                            ->bulkToggleable()
                            ->live(),

                        // Owner guidance passed to the AI narrative — most useful
                        // for the staff roster and name aliases (Suly = Suleyman).
                        Textarea::make('ai_instructions')
                            ->label(__('pages/reports.ai_instructions'))
                            ->helperText(__('pages/reports.ai_instructions_help'))
                            ->placeholder(__('pages/reports.ai_instructions_placeholder'))
                            ->rows(3)
                            ->maxLength(2000)
                            // Rewrite the owner's rough notes into crisp AI guidance.
                            ->hintAction(
                                Action::make('improveInstructions')
                                    ->label(__('pages/reports.ai_improve'))
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->action(function (Get $get, Set $set): void {
                                        $rough = trim((string) $get('ai_instructions'));

                                        if ($rough === '') {
                                            Notification::make()->title(__('pages/reports.ai_improve_empty'))->warning()->send();

                                            return;
                                        }

                                        if (AiRateLimit::hit('report-instr-improve')) {
                                            Notification::make()->title(__('pages/reports.ai_improve_rate_limited'))->warning()->send();

                                            return;
                                        }

                                        try {
                                            $improved = app(InstructionImprover::class)->improve(
                                                $rough,
                                                Workspace::find((string) session('current_workspace_id')),
                                            );
                                            $set('ai_instructions', $improved);
                                            Notification::make()->title(__('pages/reports.ai_improve_done'))->success()->send();
                                        } catch (\Throwable $e) {
                                            Log::warning('Report instruction improve failed', ['error' => $e->getMessage()]);
                                            Notification::make()->title(__('pages/reports.ai_improve_failed'))->danger()->send();
                                        }
                                    }),
                            ),
                    ]),
            ]);
    }

    /** Bumped after Generate to force the preview iframe to reload. */
    public int $generation = 0;

    /** Any competitors tracked in this workspace? (Gates the benchmark block.) */
    protected function competitorsConfigured(): bool
    {
        return once(fn (): bool => Competitor::query()->exists());
    }

    /**
     * Drop blocks whose data source isn't set up (they'd render empty anyway).
     *
     * @param  array<int, string>  $blocks
     * @return array<int, string>
     */
    protected function stripUnavailableBlocks(array $blocks): array
    {
        return $this->competitorsConfigured()
            ? $blocks
            : array_values(array_diff($blocks, ['competitors']));
    }

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
        return implode(',', ReportBlocks::normalize($this->data['blocks'] ?? null));
    }

    /** @return array{used:int, cap:int} */
    protected function reportUsage(): array
    {
        $workspace = Workspace::findOrFail(session('current_workspace_id'));
        $usage = app(AiUsageService::class);

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
    public function generateAction(): Action
    {
        ['used' => $used, 'cap' => $cap] = $this->reportUsage();
        $left = $cap >= PHP_INT_MAX ? null : max(0, $cap - $used);

        return Action::make('generate')
            ->visible(fn (): bool => auth()->user()?->can('generate_reports') ?? false)
            ->modalIcon('heroicon-o-sparkles')
            ->modalHeading(__('pages/reports.generate_heading'))
            ->modalDescription($left === null
                ? __('pages/reports.generate_desc')
                : __('pages/reports.generate_desc_left', ['left' => $left]))
            ->modalSubmitActionLabel(__('pages/reports.generate_submit'))
            ->action(fn () => $this->generate());
    }

    /**
     * Create a ReportSchedule from the CURRENT builder selection (period,
     * location, compare) — the modal only asks for the delivery details, so the
     * report is configured once, in one place.
     */
    public function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->modalIcon('heroicon-o-clock')
            ->modalHeading(__('pages/reports.schedule_heading'))
            ->modalDescription(__('pages/reports.schedule_desc'))
            ->modalSubmitActionLabel(__('pages/reports.schedule_submit'))
            ->schema([
                TextInput::make('name')
                    ->default(__('resources/report_schedules.default_name'))
                    ->required()->maxLength(120),

                Grid::make(2)->schema([
                    Select::make('frequency')
                        ->label(__('resources/report_schedules.frequency'))
                        ->options(['monthly' => __('resources/report_schedules.frequency_monthly_opt'), 'weekly' => __('resources/report_schedules.frequency_weekly_opt')])
                        ->default('monthly')->selectablePlaceholder(false)->live()->required(),

                    TextInput::make('send_day')
                        ->label(__('resources/report_schedules.day_of_month'))
                        ->numeric()->minValue(1)->maxValue(28)->default(1)
                        ->visible(fn (callable $get): bool => $get('frequency') === 'monthly')
                        ->required(),

                    Select::make('send_day')
                        ->label(__('resources/report_schedules.day_of_week'))
                        ->options([
                            1 => __('resources/report_schedules.monday'),
                            2 => __('resources/report_schedules.tuesday'),
                            3 => __('resources/report_schedules.wednesday'),
                            4 => __('resources/report_schedules.thursday'),
                            5 => __('resources/report_schedules.friday'),
                            6 => __('resources/report_schedules.saturday'),
                            7 => __('resources/report_schedules.sunday'),
                        ])
                        ->default(1)->selectablePlaceholder(false)
                        ->visible(fn (callable $get): bool => $get('frequency') === 'weekly')
                        ->required(),
                ]),

                // Recipients by role/member (Included minus Excluded), same
                // model as the schedule edit page and the Notifications page.
                Select::make('recipients.include')
                    ->label(__('resources/report_schedules.recipients_include'))
                    ->placeholder(__('resources/report_schedules.recipients_all'))
                    ->multiple()
                    ->options(fn (): array => ReportScheduleForm::recipientOptions()),

                Select::make('recipients.exclude')
                    ->label(__('resources/report_schedules.recipients_exclude'))
                    ->placeholder(__('resources/report_schedules.recipients_none'))
                    ->multiple()
                    ->options(fn (): array => ReportScheduleForm::peopleOptions())
                    ->helperText(__('resources/report_schedules.recipients_helper')),
            ])
            ->action(function (array $data): void {
                $filters = $this->filterArray();

                // Schedules run on rolling periods only — a custom date range
                // can't repeat, so it falls back to "last month".
                $period = array_key_exists($filters['period'], (array) __('common.periods_no_custom'))
                    ? $filters['period']
                    : 'last_month';

                $locationIds = array_values(array_map('intval', array_filter((array) ($filters['location_id'] ?? []))));

                ReportSchedule::create([
                    'name' => $data['name'],
                    'enabled' => true,
                    'frequency' => $data['frequency'],
                    'send_day' => (int) $data['send_day'],
                    'period' => $period,
                    'language' => in_array($filters['language'] ?? 'en', ['en', 'de'], true) ? $filters['language'] : 'en',
                    // location_id mirrors a single selection for backwards
                    // compatibility; location_ids is the source of truth.
                    'location_id' => count($locationIds) === 1 ? $locationIds[0] : null,
                    'location_ids' => $locationIds ?: null,
                    'compare' => ($filters['compareMode'] ?? 'previous') !== 'none',
                    'blocks' => ReportBlocks::normalize($this->data['blocks'] ?? null),
                    'recipients' => [
                        'include' => array_values((array) ($data['recipients']['include'] ?? [])),
                        'exclude' => array_values((array) ($data['recipients']['exclude'] ?? [])),
                    ],
                ]);

                ActivityLogger::log('schedule.created', ['name' => $data['name'], 'frequency' => $data['frequency']]);

                Notification::make()
                    ->title(__('pages/reports.schedule_created'))
                    ->body(__('pages/reports.schedule_created_body'))
                    ->success()
                    ->send();
            });
    }

    /** Produce the AI summary for the current selection (the explicit, paid action). */
    public function generate(): void
    {
        $period = DashboardPeriod::fromFilters($this->filterArray());
        $report = app(ReportData::class)->build($period);
        $workspace = Workspace::findOrFail(session('current_workspace_id'));

        $language = $this->data['language'] ?? 'en';
        $blocks = ReportBlocks::normalize($this->data['blocks'] ?? null);

        // Persist the builder choices BEFORE generating: the AI guidance is read
        // back from the workspace inside ReportInsights, so it must be saved
        // first to apply to this very generation (and to scheduled reports).
        $workspace->report_blocks = implode(',', $blocks);
        $workspace->setAttribute('report_ai_instructions', trim((string) ($this->data['ai_instructions'] ?? '')));
        $workspace->save();

        $result = app(ReportGenerator::class)->generate($period, $report, $workspace, $language);

        // Save a snapshot (rendered HTML) so it can be re-viewed later without
        // spending another AI generation.
        $previousLocale = app()->getLocale();
        app()->setLocale(in_array($language, ['en', 'de'], true) ? $language : 'en');
        $html = view('reports.monthly', [
            'data' => $report,
            'insights' => $result['insights'],
            'generatedAt' => CarbonImmutable::now()->format('M j, Y'),
            'blocks' => $blocks,
            'brand' => ReportBranding::for($workspace),
        ])->render();
        app()->setLocale($previousLocale);

        $saved = GeneratedReport::create([
            'title' => $report['businessName'],
            'period_label' => $report['periodLabel'],
            'language' => $language,
            'html' => $html,
            'generated_by' => auth()->id(),
            'generated_by_name' => auth()->user()?->name,
        ]);

        ActivityLogger::log('report.generated', ['period' => $report['periodLabel']], $saved);

        $this->generation++; // reloads the iframe → preview now shows the cached AI summary

        Notification::make()
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
}
