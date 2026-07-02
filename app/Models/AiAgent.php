<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a reusable AI reply agent (persona + tone).
 */
class AiAgent extends Model
{
    protected $fillable = [
        'name',
        'description',
        'knowledge',
        'tone',
        'reply_native_language',
        'is_default',
    ];

    /**
     * The full instruction sent to the model: the persona/description plus the
     * optional knowledge base (business facts) the agent should ground replies in.
     */
    public function instructions(): string
    {
        return self::buildInstructions((string) $this->description, $this->knowledge);
    }

    /** Compose persona + (truncated) knowledge base into one instruction string. */
    public static function buildInstructions(string $description, ?string $knowledge): string
    {
        $description = trim($description);
        $knowledge = trim((string) $knowledge);

        if ($knowledge === '') {
            return $description;
        }

        return $description."\n\n"
            ."Business knowledge base — use these facts when relevant, never contradict or invent beyond them:\n"
            .\Illuminate\Support\Str::limit($knowledge, 6000, '');
    }

    protected $casts = [
        'reply_native_language' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Tone-of-voice options shown in the form.
     *
     * @return array<string, string>
     */
    public static function tones(): array
    {
        return [
            'professional' => 'Professional',
            'friendly' => 'Friendly',
            'warm' => 'Warm',
            'concise' => 'Concise',
            'playful' => 'Playful',
            'formal' => 'Formal',
        ];
    }

    /**
     * Ensure only one default agent exists in the tenant.
     */
    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true])->save();
    }
}
