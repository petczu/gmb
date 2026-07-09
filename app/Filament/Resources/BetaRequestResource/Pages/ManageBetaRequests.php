<?php

declare(strict_types=1);

namespace App\Filament\Resources\BetaRequestResource\Pages;

use App\Filament\Resources\BetaRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBetaRequests extends ManageRecords
{
    protected static string $resource = BetaRequestResource::class;
}
