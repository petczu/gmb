<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Pages;

use App\Filament\App\Resources\ReportSchedules\ReportScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportSchedule extends EditRecord
{
    protected static string $resource = ReportScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Legacy schedules stored `recipients` as a flat list of emails. Surface
     * those under the new external-emails field so the form round-trips them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $recipients = (array) ($data['recipients'] ?? []);
        $isStructured = array_key_exists('include', $recipients)
            || array_key_exists('exclude', $recipients)
            || array_key_exists('emails', $recipients);

        if (! $isStructured) {
            $data['recipients'] = [
                'include' => [],
                'exclude' => [],
                'emails' => array_values(array_filter($recipients, fn ($e): bool => is_string($e) && $e !== '')),
            ];
        }

        return $data;
    }
}
