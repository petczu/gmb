<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Per-user starred pages, pinned as extra ungrouped sidebar items above the
 * navigation groups. Stored on users.favorite_pages as [{path, label}]; the
 * label is snapshotted when starring, so renames don't break old entries.
 */
class FavoritePages
{
    /** @return list<array{path: string, label: string}> */
    public static function all(?User $user): array
    {
        return collect((array) ($user?->favorite_pages ?? []))
            ->filter(fn ($f): bool => is_array($f) && filled($f['path'] ?? null) && filled($f['label'] ?? null))
            ->values()
            ->all();
    }

    public static function contains(?User $user, string $path): bool
    {
        return collect(self::all($user))->contains(fn (array $f): bool => $f['path'] === $path);
    }

    /** Star/unstar a page; returns true when it ended up starred. */
    public static function toggle(User $user, string $path, string $label, ?string $icon = null): bool
    {
        $favorites = collect(self::all($user));

        $favorites = $favorites->contains(fn (array $f): bool => $f['path'] === $path)
            ? $favorites->reject(fn (array $f): bool => $f['path'] === $path)
            : $favorites->push(['path' => $path, 'label' => mb_substr($label, 0, 60), 'icon' => $icon]);

        $user->forceFill(['favorite_pages' => $favorites->values()->all()])->save();

        return self::contains($user, $path);
    }

    /**
     * Sidebar items for the current user's favorites (ungrouped, below the
     * built-in top-level pages).
     *
     * @return list<NavigationItem>
     */
    public static function navigationItems(?User $user): array
    {
        return collect(self::all($user))->values()->map(
            fn (array $favorite, int $index): NavigationItem => NavigationItem::make('favorite-'.$index)
                // The page's own icon (snapshotted when starring), so the
                // pinned entry looks exactly like the original nav item.
                ->label($favorite['label'])
                ->icon(filled($favorite['icon'] ?? null) ? self::normalizeIcon((string) $favorite['icon']) : Heroicon::OutlinedStar)
                ->url(url($favorite['path']))
                ->sort(40 + $index)
                ->isActiveWhen(fn (): bool => request()->getPathInfo() === $favorite['path']),
        )->all();
    }

    /** A humane label for the page class shown in the star button + sidebar. */
    public static function labelFor(string $pageClass): string
    {
        try {
            // Resource pages label like their resource ("Locations", not
            // "List Locations"); standalone pages use their own nav label.
            if (method_exists($pageClass, 'getResource')) {
                return (string) $pageClass::getResource()::getNavigationLabel();
            }

            if (method_exists($pageClass, 'getNavigationLabel')) {
                return (string) $pageClass::getNavigationLabel();
            }
        } catch (\Throwable) {
            // Fall through to the class-name heuristic.
        }

        return Str::headline((string) preg_replace('/^(List|Manage|Create|Edit|View)/', '', class_basename($pageClass)));
    }

    /** The page's nav icon name, so the pinned item keeps the original look. */
    public static function iconFor(string $pageClass): ?string
    {
        try {
            $icon = method_exists($pageClass, 'getResource')
                ? $pageClass::getResource()::getNavigationIcon()
                : (method_exists($pageClass, 'getNavigationIcon') ? $pageClass::getNavigationIcon() : null);

            if ($icon instanceof \BackedEnum) {
                $icon = (string) $icon->value;
            }

            if (! is_string($icon)) {
                return null;
            }

            return self::normalizeIcon($icon);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Heroicon enum values are bare ("o-megaphone"); the blade-icons registry
     * needs the full set-prefixed name. Also applied on read, so favorites
     * stored before this normalization keep working.
     */
    public static function normalizeIcon(string $icon): string
    {
        return str_starts_with($icon, 'heroicon-') ? $icon : 'heroicon-'.$icon;
    }
}
