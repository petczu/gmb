<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Tables;

use App\Filament\App\Pages\Reports;
use App\Jobs\SendReportEmail;
use App\Models\ReportSchedule;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportSchedulesTable
{
    /**
     * Number of explicit recipients for the badge: included roles/people plus
     * external emails (new shape), or the flat email list (legacy). Zero means
     * "no explicit routing" → falls back to all members.
     *
     * @param  array<int|string, mixed>  $recipients
     */
    private static function recipientCount(array $recipients): int
    {
        $isStructured = array_key_exists('include', $recipients)
            || array_key_exists('exclude', $recipients)
            || array_key_exists('emails', $recipients);

        if (! $isStructured) {
            return count(array_filter($recipients, fn ($e): bool => is_string($e) && $e !== ''));
        }

        return count((array) ($recipients['include'] ?? [])) + count((array) ($recipients['emails'] ?? []));
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(ReportSchedule::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedClock)
            ->emptyStateHeading(__('resources/report_schedules.empty_heading'))
            ->emptyStateDescription(__('resources/report_schedules.empty_desc'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('resources/report_schedules.empty_cta'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => Reports::getUrl()),
            ])
            ->columns([
                TextColumn::make('name')->label(__('resources/report_schedules.col_name'))->searchable()->sortable(),

                IconColumn::make('enabled')->label(__('resources/report_schedules.col_enabled'))->boolean(),

                TextColumn::make('frequency')
                    ->label(__('resources/report_schedules.frequency'))
                    ->badge()
                    ->formatStateUsing(fn (ReportSchedule $r): string => $r->frequency === 'weekly'
                        ? __('resources/report_schedules.frequency_weekly', ['day' => [
                            1 => __('resources/report_schedules.mon'),
                            2 => __('resources/report_schedules.tue'),
                            3 => __('resources/report_schedules.wed'),
                            4 => __('resources/report_schedules.thu'),
                            5 => __('resources/report_schedules.fri'),
                            6 => __('resources/report_schedules.sat'),
                            7 => __('resources/report_schedules.sun'),
                        ][$r->send_day] ?? ''])
                        : __('resources/report_schedules.frequency_monthly', ['day' => $r->send_day])),

                TextColumn::make('period')
                    ->label(__('resources/report_schedules.col_period'))
                    ->badge()
                    ->formatStateUsing(fn (ReportSchedule $r): string => ((array) __('common.periods'))[$r->period] ?? (string) $r->period)
                    ->visibleFrom('md'),

                TextColumn::make('recipients')
                    ->label(__('resources/report_schedules.col_recipients'))
                    ->formatStateUsing(function (ReportSchedule $r): string {
                        $count = self::recipientCount((array) $r->recipients);

                        return $count === 0
                            ? __('resources/report_schedules.recipients_all')
                            : __('resources/report_schedules.recipients_count', ['count' => $count]);
                    })
                    ->visibleFrom('lg'),

                TextColumn::make('last_sent_at')->label(__('resources/report_schedules.col_last_sent'))->since()->placeholder(__('resources/report_schedules.never'))->visibleFrom('md'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('sendNow')
                        ->label(__('resources/report_schedules.send_now'))
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->requiresConfirmation()
                        ->modalDescription(__('resources/report_schedules.send_now_desc'))
                        ->action(function (ReportSchedule $record): void {
                            $workspaceId = (string) session('current_workspace_id');
                            SendReportEmail::dispatch($workspaceId, $record->id);

                            Notification::make()
                                ->title(__('resources/report_schedules.report_queued'))
                                ->body(__('resources/report_schedules.report_queued_body'))
                                ->success()
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
