<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\GeneratedReports\Tables;

use App\Filament\App\Pages\Reports;
use App\Models\GeneratedReport;
use App\Models\ReportShare;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class GeneratedReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->searchable(GeneratedReport::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->emptyStateHeading(__('resources/generated_reports.empty_heading'))
            ->emptyStateDescription(__('resources/generated_reports.empty_desc'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('resources/generated_reports.empty_cta'))
                    ->icon(Heroicon::OutlinedDocumentChartBar)
                    ->url(fn (): string => Reports::getUrl()),
            ])
            ->columns([
                TextColumn::make('title')->label(__('resources/generated_reports.col_business'))->searchable()->sortable(),

                TextColumn::make('period_label')->label(__('resources/generated_reports.col_period'))->badge(),

                TextColumn::make('language')
                    ->label(__('resources/generated_reports.col_language'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->visibleFrom('md'),

                TextColumn::make('generated_by_name')->label(__('resources/generated_reports.col_by'))->placeholder('—')->visibleFrom('lg'),

                TextColumn::make('created_at')->label(__('resources/generated_reports.col_generated'))->dateTime('M j, Y · H:i')->sortable()->visibleFrom('md'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('resources/generated_reports.view'))
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn (GeneratedReport $record): string => route('reports.saved.preview', $record->getKey()))
                        ->openUrlInNewTab(),

                    Action::make('download')
                        ->label(__('resources/generated_reports.download_pdf'))
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn (GeneratedReport $record): string => route('reports.saved.download', $record->getKey())),

                    self::shareAction(),

                    DeleteAction::make()
                        // Also revoke the public share link, if any.
                        ->before(fn (GeneratedReport $record) => ReportShare::query()
                            ->where('workspace_id', (string) session('current_workspace_id'))
                            ->where('generated_report_id', $record->getKey())
                            ->delete()),
                ]),
            ]);
    }

    /** Create/update the single public share link for a report. */
    private static function shareAction(): Action
    {
        return Action::make('share')
            ->label(__('resources/generated_reports.share_link'))
            ->icon(Heroicon::OutlinedShare)
            ->modalHeading(__('resources/generated_reports.share_heading'))
            ->modalDescription(__('resources/generated_reports.share_desc'))
            // Get-or-create the link as the modal opens, so it is shown right away.
            ->fillForm(function (GeneratedReport $record): array {
                $share = self::ensureShare($record);

                return [
                    'access_from' => $share->access_from?->toDateString(),
                    'access_until' => $share->access_until?->toDateString(),
                ];
            })
            ->schema([
                Placeholder::make('current_link')
                    ->label(__('resources/generated_reports.current_link'))
                    ->content(function (GeneratedReport $record): HtmlString {
                        $u = e(route('reports.shared', self::shareFor($record)->token));

                        return new HtmlString(
                            '<div x-data="{ copied:false }" style="display:flex; align-items:center; gap:8px;">'
                            .'<a href="'.$u.'" target="_blank" style="color:#2d19ec; word-break:break-all; flex:1; font-size:13px;">'.$u.'</a>'
                            .'<button type="button" @click="navigator.clipboard.writeText(\''.$u.'\'); copied=true; setTimeout(()=>copied=false,1500)" '
                            .'style="flex:none; display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; cursor:pointer; font-size:12px; white-space:nowrap;">'
                            .'<svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
                            .'<span x-text="copied ? \''.e(__('resources/generated_reports.copied')).'\' : \''.e(__('resources/generated_reports.copy')).'\'">'.e(__('resources/generated_reports.copy')).'</span>'
                            .'</button>'
                            .'</div>'
                        );
                    }),

                TextInput::make('password')
                    ->label(__('resources/generated_reports.password'))
                    ->password()
                    ->revealable()
                    ->helperText(__('resources/generated_reports.password_helper')),

                DatePicker::make('access_from')->label(__('resources/generated_reports.access_from'))->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->helperText(__('resources/generated_reports.access_from_helper')),

                DatePicker::make('access_until')->label(__('resources/generated_reports.access_until'))->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->helperText(__('resources/generated_reports.access_until_helper')),
            ])
            ->modalSubmitActionLabel(__('resources/generated_reports.save_link'))
            ->action(function (array $data, GeneratedReport $record): void {
                $workspaceId = (string) session('current_workspace_id');
                $existing = self::shareFor($record);

                $share = ReportShare::updateOrCreate(
                    ['workspace_id' => $workspaceId, 'generated_report_id' => $record->getKey()],
                    [
                        'token' => $existing?->token ?? Str::random(48),
                        'title' => $record->title,
                        // Always refresh the snapshot so the share reflects this report.
                        'html' => $record->html,
                        'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
                        'access_from' => $data['access_from'] ?? null,
                        'access_until' => $data['access_until'] ?? null,
                    ],
                );

                Notification::make()
                    ->title(__('resources/generated_reports.share_saved'))
                    ->success()
                    ->send();
            });
    }

    private static function shareFor(GeneratedReport $record): ?ReportShare
    {
        return ReportShare::query()
            ->where('workspace_id', (string) session('current_workspace_id'))
            ->where('generated_report_id', $record->getKey())
            ->first();
    }

    /** Get the report's single share link, creating it (no password/window) if missing. */
    private static function ensureShare(GeneratedReport $record): ReportShare
    {
        return ReportShare::firstOrCreate(
            [
                'workspace_id' => (string) session('current_workspace_id'),
                'generated_report_id' => $record->getKey(),
            ],
            [
                'token' => Str::random(48),
                'title' => $record->title,
                'html' => $record->html,
            ],
        );
    }
}
