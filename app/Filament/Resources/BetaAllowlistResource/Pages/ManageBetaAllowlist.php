<?php

declare(strict_types=1);

namespace App\Filament\Resources\BetaAllowlistResource\Pages;

use App\Filament\Resources\BetaAllowlistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBetaAllowlist extends ManageRecords
{
    protected static string $resource = BetaAllowlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add email')
                ->mutateDataUsing(function (array $data): array {
                    $data['email'] = mb_strtolower(trim((string) $data['email']));

                    return $data;
                }),
        ];
    }
}
