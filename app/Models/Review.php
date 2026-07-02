<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TENANT model — lives in the per-workspace DB (default connection, swapped by
 * stancl while tenancy is initialized).
 */
class Review extends Model
{
    protected $fillable = [
        'location_id',
        'external_review_id',
        'author_name',
        'rating',
        'text',
        'review_link',
        'created_at_external',
        'reply_text',
        'replied_at',
        'reply_status',
        'reply_source',
        'synced_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at_external' => 'datetime',
        'replied_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Fire the reply.published webhook from a single place — there are four
        // callers that publish a reply (manual, AI auto-reply, automations, MCP)
        // and they all flip reply_status to 'published' via a save().
        static::updated(function (Review $review): void {
            if ($review->wasChanged('reply_status') && $review->reply_status === 'published') {
                app(\App\Services\Webhooks\WebhookDispatcher::class)
                    ->dispatch(\App\Webhooks\WebhookEvents::REPLY_PUBLISHED, $review->toWebhookPayload());
            }
        });
    }

    /**
     * Compact review representation sent in webhook payloads.
     *
     * @return array<string, mixed>
     */
    public function toWebhookPayload(): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'location' => $this->location?->name,
            'author' => $this->author_name,
            'rating' => $this->rating,
            'text' => $this->originalText(),
            'reply' => $this->reply_text,
            'reply_source' => $this->reply_source,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'created_at' => $this->created_at_external?->toIso8601String(),
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(AutoReplyQueueItem::class);
    }

    public function hasReply(): bool
    {
        return filled($this->reply_text);
    }

    /**
     * Google returns localized review text as
     * "(Translated by Google) <translation>\n\n(Original)\n<original>".
     * We display the ORIGINAL language; translation is available separately.
     */
    public function originalText(): ?string
    {
        return self::splitGoogleText($this->text)['original'];
    }

    public function translatedText(): ?string
    {
        return self::splitGoogleText($this->text)['translated'];
    }

    /**
     * @return array{original: ?string, translated: ?string}
     */
    public static function splitGoogleText(?string $text): array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return ['original' => null, 'translated' => null];
        }

        // "(Original)" marks the start of the author's original-language text.
        if (preg_match('/^(.*?)\(Original\)\s*(.*)$/su', $text, $m)) {
            $translated = preg_replace('/^\(Translated by Google\)\s*/u', '', trim($m[1]));

            return [
                'original' => trim($m[2]) ?: null,
                'translated' => trim((string) $translated) ?: null,
            ];
        }

        // No original marker — strip a lone "(Translated by Google)" prefix if any.
        $stripped = preg_replace('/^\(Translated by Google\)\s*/u', '', $text);

        return ['original' => trim((string) $stripped) ?: null, 'translated' => null];
    }
}
