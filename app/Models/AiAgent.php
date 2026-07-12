<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * Compose persona + (truncated) knowledge base + the workspace's shared
     * reply rules into one instruction string. Shared rules apply to EVERY
     * agent (style corrections like "say 'Raum', never 'Room' inside a German
     * sentence"), so a fix never has to be copied into each agent.
     */
    public static function buildInstructions(string $description, ?string $knowledge, ?string $sharedRules = null): string
    {
        $description = trim($description);
        $knowledge = trim((string) $knowledge);
        $sharedRules = trim($sharedRules ?? self::sharedRules());

        $out = $description;

        if ($knowledge !== '') {
            $out .= "\n\n"
                ."Business knowledge base — use these facts when relevant, never contradict or invent beyond them:\n"
                .Str::limit($knowledge, 6000, '');
        }

        if ($sharedRules !== '') {
            $out .= "\n\n"
                ."Workspace-wide reply rules set by the owner — follow them strictly in every reply:\n"
                .Str::limit($sharedRules, 2000, '');
        }

        return $out;
    }

    /** The workspace's shared reply rules (empty outside tenant context). */
    public static function sharedRules(): string
    {
        $tenant = tenant();

        return $tenant instanceof Workspace
            ? trim((string) $tenant->getAttribute('reply_guidelines'))
            : '';
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
