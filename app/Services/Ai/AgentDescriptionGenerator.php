<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

use function Laravel\Ai\agent;

/**
 * AI helper that drafts the "describe your agent" persona/instructions for a new
 * AI review-reply agent. The user provides a website URL (fetched server-side)
 * and/or a few notes about the business; Claude expands that into a practical
 * set of reply instructions in the user's interface language.
 *
 * This is a setup convenience, not a billable reply: usage is logged to the
 * ledger for cost auditing (delta 0) but never counts against the AI cap.
 */
class AgentDescriptionGenerator
{
    public function __construct(private readonly AiCreditService $credits) {}

    public function generate(?string $url, ?string $notes, ?Workspace $workspace = null): string
    {
        $siteText = filled($url) ? $this->fetchWebsite((string) $url) : null;
        $locale = app()->getLocale() === 'de' ? 'German' : 'English';

        if (config('services.ai.driver') === 'fake') {
            return $this->fallback($url, $notes, $siteText);
        }

        $model = (string) config('services.ai.model', 'claude-opus-4-8');

        $response = agent(instructions: $this->systemPrompt($locale))
            ->prompt($this->userMessage($url, $notes, $siteText), provider: Lab::Anthropic, model: $model);

        $text = trim((string) $response->text);
        if ($text === '') {
            throw new RuntimeException('The AI returned an empty description.');
        }

        if ($workspace instanceof Workspace) {
            $this->credits->logUsage(
                workspace: $workspace,
                reason: 'agent_description',
                model: $model,
                inputTokens: (int) ($response->usage->promptTokens ?? 0),
                outputTokens: (int) ($response->usage->completionTokens ?? 0),
            );
        }

        return $text;
    }

    private function systemPrompt(string $locale): string
    {
        return implode("\n", [
            'You write the configuration ("persona / instructions") for an AI agent that replies to Google Business reviews on behalf of a business.',
            'Given information about the business, produce a complete reply PLAYBOOK the agent will follow. It must be practical and specific to this business, and cover ALL of these sections (as plain paragraphs or short dash lists, no markdown headings):',
            '1. Who the business is: what it offers, where it is, who its guests are. Only facts supported by the provided information.',
            '2. Review-type playbook, one rule set per type: rating-only review with no text (1-2 short sentences: thank + invite back); short positive ("great", "nice": brief and light); detailed positive (react to what they describe, mention staff or specifics they name, add genuine emotion, invite back); mixed 4-star (acknowledge the good part, address the minor issue calmly, show improvement mindset); negative (thank for the feedback, apologize naturally, offer to make it right directly, never defensive, no emojis).',
            '3. Style rotation: name 3-4 reply styles that fit this business (e.g. energetic, warm, casual, staff-focused) and tell the agent to vary between them instead of repeating one pattern.',
            '4. Reviewer-name rules: use only the cleaned first name ("John D." is John, drop stray letters), skip messy or unclear names entirely, and do not open every reply with the name; sometimes leave it out.',
            '5. Language: reply in the same language as the review. If the business location or audience implies a distinct local way of speaking, describe the natural local tone with 2-3 concrete "use X, not Y" word examples in that language (e.g. everyday Saudi phrasing instead of formal literary Arabic, du instead of Sie for a casual German venue). Only when the provided information supports it; never guess.',
            '6. Local visibility: occasionally weave ONE natural keyword into a reply (the business category plus city, in the review\'s language), never forced and never in negative replies.',
            '7. Emoji policy: at most one light emoji in positive replies when it fits the business, never in negative or mixed replies.',
            '8. A short final self-check list: name clean, sounds human, not formal, short (2-4 lines), not repetitive with recent replies.',
            'Address the instructions directly to the agent (e.g. "You are replying on behalf of ...").',
            'Do not invent specific facts (addresses, names, offers) that are not supported by the provided information.',
            'Output ONLY the instruction text. No preamble, no markdown headings, no surrounding quotes. Roughly 250-400 words.',
            // Shared humanizer rules: the persona text itself is customer-visible
            // configuration and must not carry AI artifacts either.
            ...Humanizer::rules(),
            "Write it in {$locale} (keep any word examples from section 5 in their own language).",
        ]);
    }

    private function userMessage(?string $url, ?string $notes, ?string $siteText): string
    {
        $parts = [];
        if (filled($url)) {
            $parts[] = 'Business website: '.trim((string) $url);
        }
        $parts[] = "Website content:\n".(filled($siteText) ? $siteText : '(could not be read / not provided)');
        $parts[] = "Notes from the user:\n".(filled($notes) ? trim((string) $notes) : '(none)');

        return implode("\n\n", $parts);
    }

    /**
     * Fetch a URL and return readable text, or null on failure. Includes a basic
     * SSRF guard so internal/reserved addresses can't be reached.
     */
    private function fetchWebsite(string $url): ?string
    {
        if (! $this->isPublicHttpUrl($url)) {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => config('app.name').'Bot/1.0 (+agent-setup)'])
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->htmlToText($response->body());
    }

    private function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';
        if ($host === '') {
            return false;
        }

        // Resolve to IPv4 and reject private/reserved ranges (SSRF guard).
        $ips = gethostbynamel($host) ?: [];
        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    private function htmlToText(string $html): string
    {
        // Drop script/style blocks, then strip the remaining tags.
        $html = preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return Str::limit($text, 6000, '');
    }

    private function fallback(?string $url, ?string $notes, ?string $siteText): string
    {
        $subject = filled($notes) ? trim((string) $notes) : (filled($url) ? 'the business at '.trim((string) $url) : 'this business');

        return implode("\n\n", array_filter([
            "You are replying to Google reviews on behalf of {$subject}.",
            'Write warm, professional, concise replies (2-4 sentences). Thank positive reviewers specifically for what they mention. For critical reviews, acknowledge the issue, apologise where appropriate, and offer to make it right offline. Never argue or get defensive.',
            'Keep it human and genuine: vary sentence length, avoid clichés and over-the-top praise, and do not invent facts that are not in the review.',
            filled($siteText) ? 'Reference what the business actually offers where relevant.' : null,
        ]));
    }
}
