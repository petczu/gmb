<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * CENTRAL model — a review-showcase widget the customer embeds on their own
 * site. Resolved by its public token before any tenancy exists; the selected
 * reviews are served from a pre-built {@see self::$snapshot} so the embed never
 * has to boot the tenant DB.
 *
 * @property string $workspace_id
 * @property string $token
 * @property ?string $name
 * @property array<string, mixed> $settings
 * @property ?array<string, mixed> $snapshot
 * @property ?Carbon $refreshed_at
 * @property bool $active
 */
class ReviewWidget extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['workspace_id', 'token', 'name', 'settings', 'snapshot', 'active'];

    protected $casts = [
        'settings' => 'array',
        'snapshot' => 'array',
        'refreshed_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    /** A URL-safe, unguessable public identifier for the embed. */
    public static function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(32));
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    /**
     * The default configuration a brand-new widget starts from. Kept here so the
     * builder, the snapshot job and the renderer all agree on the shape.
     *
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            // Layout
            'layout' => 'grid',            // slider | grid | list | masonry
            'target_column_width' => 320,  // px, dynamic column count
            'rows' => 1,                   // slider only
            'gap' => 16,                   // px between cards
            // Card
            'show_avatar' => true,
            'show_rating' => true,
            'show_date' => true,
            'show_reply' => false,
            'text_max_lines' => 6,         // 0 = no clamp
            'rounded' => 12,               // px card radius
            // Header
            'show_header' => true,
            'header_title' => null,        // null = workspace name
            'show_summary' => true,        // avg rating + count badge
            // Style & colour
            'theme' => 'light',            // light | dark
            'accent' => '#2d19ec',
            'card_background' => null,      // null = theme default
            'text_color' => null,
            // Review selection (drives the snapshot)
            'location_ids' => [],          // [] = all connected locations
            'min_rating' => 4,
            'require_text' => true,
            'max_reviews' => 12,
            'sort' => 'newest',            // newest | highest | random
            'hidden_ids' => [],            // tenant review ids never shown
            'pinned_ids' => [],            // tenant review ids forced to the front
            'branding' => true,            // show a subtle "Reviews by" footer link
        ];
    }

    /** A single setting with a fallback to the shipped default. */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default ?? (self::defaultSettings()[$key] ?? null);
    }

    public function layout(): string
    {
        return (string) $this->setting('layout', 'grid');
    }

    /** The reviews stored in the snapshot, already filtered and ordered. */
    public function snapshotReviews(): array
    {
        return (array) ($this->snapshot['reviews'] ?? []);
    }

    /** Header aggregate stored alongside the reviews. */
    public function snapshotSummary(): array
    {
        return (array) ($this->snapshot['summary'] ?? ['average' => 0, 'count' => 0]);
    }

    public function embedUrl(): string
    {
        return route('review-widget.embed', $this->token);
    }

    public function jsUrl(): string
    {
        return route('review-widget.js', $this->token);
    }

    /** The `<script>` snippet the customer pastes into their page. */
    public function embedSnippet(): string
    {
        return '<script defer async src="'.e($this->jsUrl()).'"></script>'
            .'<div id="reviews-widget-'.e($this->token).'"></div>';
    }
}
