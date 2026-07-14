<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Support\FavoritePages;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The star button in a page's header: toggles the page in the user's
 * sidebar favorites. The page reloads after toggling so the sidebar
 * reflects the change immediately.
 */
class FavoritePageStar extends Component
{
    public string $path;

    public string $label;

    public ?string $icon = null;

    public bool $starred = false;

    public function mount(string $path, string $label, ?string $icon = null): void
    {
        $this->path = $path;
        $this->label = $label;
        $this->icon = $icon;
        $this->starred = FavoritePages::contains($this->user(), $path);
    }

    public function toggle(): void
    {
        $user = $this->user();

        if ($user === null) {
            return;
        }

        $this->starred = FavoritePages::toggle($user, $this->path, $this->label, $this->icon);

        // The sidebar is outside this component; reload so the pin appears.
        $this->js('window.location.reload()');
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public function render(): View
    {
        return view('livewire.favorite-page-star');
    }
}
