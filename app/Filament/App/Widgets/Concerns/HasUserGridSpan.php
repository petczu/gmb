<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets\Concerns;

use App\Support\DashboardWidgets;

/**
 * Lets the user override this widget's grid width (full ↔ half) from the
 * dashboard's arrange controls; without an override the widget keeps its own
 * $columnSpan default.
 */
trait HasUserGridSpan
{
    public function getColumnSpan(): int|string|array
    {
        return DashboardWidgets::spanOverrideForClass(static::class) ?? $this->columnSpan;
    }
}
