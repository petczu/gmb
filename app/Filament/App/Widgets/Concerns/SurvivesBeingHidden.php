<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets\Concerns;

/**
 * Filament aborts 403 when an already-rendered widget's canView() turns false
 * (Widgets\Concerns\CanAuthorizeAccess::hydrateCanAuthorizeAccess). Our
 * dashboard widgets go invisible MID-SESSION by design: the user hides them
 * via the "Customize" action while they are still in the DOM and polling.
 * A stale poll must not blow up the page with a 403 overlay — the widget
 * simply disappears on the next page render. canView() here is a per-user
 * display preference, not an authorization boundary.
 */
trait SurvivesBeingHidden
{
    public function hydrateCanAuthorizeAccess(): void
    {
        // Intentionally no abort — see the trait docblock.
    }
}
