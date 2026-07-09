<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrackedPlaceResource\Pages;

use App\Filament\Resources\TrackedPlaceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTrackedPlaces extends ManageRecords
{
    protected static string $resource = TrackedPlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add place')
                ->mutateDataUsing(fn (array $data): array => TrackedPlaceResource::enrich($data)),
        ];
    }
}
