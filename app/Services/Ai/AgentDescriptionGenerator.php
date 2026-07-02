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
            'Given information about the business, produce a clear, practical set of instructions the agent will follow when writing replies:',
            'who the business is and what it offers, the voice and tone, what to emphasise, how to handle positive vs critical reviews, personalization rules, and concrete do\'s and don\'ts.',
            'Address the instructions directly to the agent (e.g. "You are replying on behalf of ...").',
            'Do not invent specific facts (addresses, names, offers) that are not supported by the provided information.',
            'Output ONLY the instruction text. No preamble, no markdown headings, no surrounding quotes. Keep it focused, roughly 150-250 words.',
            "Write it in {$locale}.",
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
