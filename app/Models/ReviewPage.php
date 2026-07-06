<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A public review-collection page ("leave us a review" funnel). CENTRAL model —
 * resolved by slug or custom domain before any tenancy exists.
 *
 * @property string $workspace_id
 * @property string $slug
 * @property ?string $custom_domain
 * @property array $settings
 * @property bool $active
 */
class ReviewPage extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['workspace_id', 'slug', 'custom_domain', 'settings', 'active'];

    protected $casts = [
        'settings' => 'array',
        'active' => 'boolean',
    ];

    /** Target platforms offered in the editor (styling is built in per key). */
    public const PLATFORMS = ['google', 'tripadvisor', 'custom'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(ReviewPageStat::class);
    }

    public static function generateSlug(string $name): string
    {
        $base = Str::slug(Str::limit($name, 40, '')) ?: 'reviews';
        $slug = $base;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    /** Resolve by an incoming custom-domain host (exact, lowercased). */
    public static function findByDomain(string $host): ?self
    {
        $host = strtolower(trim($host));

        return $host === '' ? null : static::query()
            ->where('custom_domain', $host)
            ->where('active', true)
            ->first();
    }

    /** Enabled target buttons, in configured order. */
    public function targets(): array
    {
        return array_values(array_filter(
            (array) ($this->settings['targets'] ?? []),
            fn (array $t): bool => ! empty($t['url']) && ($t['enabled'] ?? true),
        ));
    }

    /** Bump a daily counter (view or a target click). Atomic upsert. */
    public function bump(string $metric): void
    {
        ReviewPageStat::query()->upsert(
            [['review_page_id' => $this->id, 'day' => now()->toDateString(), 'metric' => $metric, 'count' => 1]],
            ['review_page_id', 'day', 'metric'],
            ['count' => DB::raw('count + 1')],
        );
    }
}
