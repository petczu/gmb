<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets\Concerns;

use Illuminate\Contracts\View\View;

/**
 * Replaces Filament's empty lazy-loading box with a shimmer skeleton shaped
 * like the widget's real content ($skeletonVariant: stats | chart | list |
 * table). Only worth using on widgets whose data comes from external APIs.
 */
trait HasSkeletonPlaceholder
{
    public function placeholder(): View
    {
        return view('filament.app.widgets.partials.skeleton', [
            'variant' => property_exists($this, 'skeletonVariant') ? $this->skeletonVariant : 'chart',
            'height' => $this->getPlaceholderHeight(),
            ...$this->getPlaceholderData(),
        ]);
    }
}
