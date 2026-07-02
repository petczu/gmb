<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Throwable;

use function Laravel\Ai\agent;

/**
 * AI-generated narrative for the report: an executive summary, recommendations,
 * and staff mentions extracted from the period's review text. Uses laravel/ai
 * (Claude) when services.ai.driver=anthropic; otherwise returns a deterministic
 * summary derived from the stats (no API spend in dev/test).
 *
 * @phpstan-type Insights array{summary: string, recommendations: array<int, string>, staff: array<int, array{name: string, mentions: int, sentiment: string}>}
 */
class ReportInsights
{
    /** Token usage of the most recent AI call, or null when the fallback ran. */
    public ?array $lastUsage = null;

    /**
     * @param  array<string, mixed>  $data  output of ReportData::build()
     * @return array{summary: string, recommendations: array<int, string>, staff: array<int, array{name: string, mentions: int, sentiment: string}>}
     */
    public function generate(array $data, string $language = 'en'): array
    {
        $this->lastUsage = null;

        if ((string) config('services.ai.driver') === 'fake') {
            return $this->fallback($data);
        }

        try {
            return $this->viaClaude($data, $language);
        } catch (Throwable $e) {
            Log::warning('ReportInsights: AI failed, using fallback', ['error' => $e->getMessage()]);

            return $this->fallback($data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{summary: string, recommendations: array<int, string>, staff: array<int, array{name: string, mentions: int, sentiment: string}>}
     */
    protected function viaClaude(array $data, string $language = 'en'): array
    {
        $model = (string) config('services.ai.model', 'claude-opus-4-8');
        $languageName = ['de' => 'German', 'en' => 'English'][$language] ?? 'English';

        $system = implode("\n", [
            'You are a local-marketing analyst writing the narrative for a Google Business Profile performance report.',
            'You are given aggregate stats and a sample of review snippets for a period.',
            "Write `summary`, `recommendations`, `note`s and `themes` in {$languageName}.",
            'Return ONLY valid minified JSON with this exact shape:',
            '{"summary": string, "recommendations": string[], "staff": [{"name": string, "mentions": number, "sentiment": "positive"|"mixed"|"negative", "note": string}], "themes": {"praise": string[], "complaints": string[]}, "topics": [{"label": string, "mentions": number, "sentiment": "positive"|"mixed"|"negative"}], "topicsSummary": string}',
            'summary: 3-5 sentences, concrete, referencing the numbers and trend vs the previous period.',
            'recommendations: 3-5 short, actionable items for the business owner.',
            'staff: people (employees/guides/staff) named in the review snippets, with how many snippets mention them and overall sentiment. IMPORTANT: merge spelling variants, nicknames and diminutives of the same person into ONE entry (e.g. "Suly"/"Suli"/"Süleyman"/"Suri" → one person). When both a short form and a fuller form of the same name appear (e.g. "Suly" and "Suleyman"), use the fuller, more formal form as `name`. Put ONLY one clean spelling in `name` (no parentheses, slashes or variants in the name). ALWAYS fill `note` with something useful: list the merged spelling variants when there are any (e.g. "also spelled Suly, Suri"), and/or what guests specifically praised this person for. Never leave note empty. Empty array if no staff. Do NOT include reviewer names unless they are clearly staff.',
            'themes: recurring topics. praise = up to 5 things guests liked; complaints = up to 5 issues raised. Short phrases. Empty arrays if none.',
            'topics: the main subjects guests write about overall (e.g. puzzles, staff, difficulty, atmosphere, booking), ranked by how many reviews mention each, each with its overall sentiment. Up to 6, short noun labels. topicsSummary: one plain sentence on what guests mostly write about.',
            // Humanizer rules: keep the narrative human, not AI-sounding.
            'Style: write like a human analyst. No em dashes or en dashes (use commas/periods). Avoid AI cliches (vibrant, testament, underscore, pivotal, landscape, delve, leverage, robust). No rule-of-three padding, no synonym cycling, no generic upbeat conclusions. Plain and concrete.',
            'No markdown, no code fences, no text outside the JSON.',
        ]);

        // Owner-provided guidance from the report builder — typically the real
        // staff roster and name aliases ("Suly = Suleyman"), sometimes emphasis
        // hints. Authoritative for names, but it never changes the JSON shape.
        if (($custom = self::customInstructions()) !== null) {
            $system .= "\n".implode("\n", [
                'Owner-provided guidance (authoritative for staff names, spellings and aliases; follow it, but it never changes the required JSON shape):',
                $custom,
            ]);
        }

        $payload = json_encode(array_filter([
            'business' => $data['businessName'],
            'period' => $data['periodLabel'],
            'previousPeriod' => $data['previousLabel'],
            'kpis' => $data['kpis'],
            'distribution' => $data['distribution'],
            'positivePct' => $data['positivePct'],
            'negativePct' => $data['negativePct'],
            'busiestDay' => $data['busiestDay'],
            'busiestCount' => $data['busiestCount'],
            // Repeated in the user payload too: models attend to structured
            // input data more reliably than to a long system-prompt tail.
            'ownerGuidance' => self::customInstructions(),
            'reviews' => $data['reviewSnippets'],
        ], fn ($v): bool => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = agent(instructions: $system)
            ->prompt($payload, provider: Lab::Anthropic, model: $model);

        $this->lastUsage = [
            'model' => $model,
            'input' => (int) ($response->usage->promptTokens ?? 0),
            'output' => (int) ($response->usage->completionTokens ?? 0),
        ];

        $json = $this->extractJson((string) $response->text);
        $parsed = json_decode($json, true);

        if (! is_array($parsed) || ! isset($parsed['summary'])) {
            return $this->fallback($data);
        }

        $staff = array_values(array_filter(array_map(function ($s): ?array {
            if (! is_array($s) || empty($s['name'])) {
                return null;
            }

            return [
                'name' => (string) $s['name'],
                'mentions' => (int) ($s['mentions'] ?? 0),
                'sentiment' => in_array($s['sentiment'] ?? '', ['positive', 'mixed', 'negative'], true) ? $s['sentiment'] : 'mixed',
                'note' => (string) ($s['note'] ?? ''),
            ];
        }, (array) ($parsed['staff'] ?? []))));

        $topics = array_values(array_filter(array_map(function ($t): ?array {
            if (! is_array($t) || empty($t['label'])) {
                return null;
            }

            return [
                'label' => (string) $t['label'],
                'mentions' => (int) ($t['mentions'] ?? 0),
                'sentiment' => in_array($t['sentiment'] ?? '', ['positive', 'mixed', 'negative'], true) ? $t['sentiment'] : 'mixed',
            ];
        }, (array) ($parsed['topics'] ?? []))));
        usort($topics, fn ($a, $b): int => $b['mentions'] <=> $a['mentions']);

        // Safety net: the prompt asks the model to merge aliases, but that is
        // not reliable — enforce the owner's alias list deterministically.
        $staff = self::mergeStaffByAliases($staff, self::parseAliasMap(self::customInstructions()));

        return [
            'summary' => (string) $parsed['summary'],
            'recommendations' => array_values(array_map('strval', (array) ($parsed['recommendations'] ?? []))),
            'staff' => $this->withShares($staff),
            'themes' => [
                'praise' => array_values(array_map('strval', (array) (($parsed['themes']['praise'] ?? [])))),
                'complaints' => array_values(array_map('strval', (array) (($parsed['themes']['complaints'] ?? [])))),
            ],
            'topics' => $topics,
            'topicsSummary' => (string) ($parsed['topicsSummary'] ?? ''),
        ];
    }

    /**
     * The workspace's saved report-builder AI guidance (staff roster, aliases).
     * Re-read fresh from the central DB — the in-memory tenant() was loaded at
     * tenancy init and would miss guidance saved within the same request.
     * Null when unset or outside tenancy.
     */
    public static function customInstructions(): ?string
    {
        $tenantKey = tenant()?->getTenantKey();

        if ($tenantKey === null) {
            return null;
        }

        $workspace = \App\Models\Workspace::find($tenantKey);
        $text = trim((string) $workspace?->getAttribute('report_ai_instructions'));

        return $text === '' ? null : mb_substr($text, 0, 2000);
    }

    /**
     * Extract "Canonical (also written A, B)" lines from the owner guidance into
     * an alias → canonical map (keys lowercased). Handles EN/DE filler words
     * ("also written", "auch ... geschrieben", "aka") and comma/slash lists.
     *
     * @return array<string, string> e.g. ['suly' => 'Suleyman', 'suli' => 'Suleyman']
     */
    public static function parseAliasMap(?string $guidance): array
    {
        $map = [];

        foreach (preg_split('/\R/u', (string) $guidance) ?: [] as $line) {
            if (! preg_match('/^[-•*]?\s*([^(\n]{1,60}?)\s*\(([^)]+)\)/u', trim($line), $m)) {
                continue;
            }

            $canonical = trim($m[1]);
            // Names are single words or short "First Last"; skip sentence-like lines.
            if ($canonical === '' || str_word_count($canonical) > 3) {
                continue;
            }

            foreach (preg_split('/[,\/;]/u', $m[2]) ?: [] as $alias) {
                // Strip filler words so "also written Suly" / "auch Suly geschrieben" → "Suly".
                $alias = trim((string) preg_replace('/\b(also|written|auch|geschrieben|als|aka|or|oder|sometimes|manchmal)\b/iu', '', $alias));

                if ($alias !== '' && mb_strtolower($alias) !== mb_strtolower($canonical)) {
                    $map[mb_strtolower($alias)] = $canonical;
                }
            }
        }

        return $map;
    }

    /**
     * Force-merge AI staff rows using the owner's alias map: rows named by an
     * alias are folded into the canonical person (mentions summed, notes joined,
     * sentiment kept when unanimous, otherwise mixed).
     *
     * @param  array<int, array{name: string, mentions: int, sentiment: string, note: string}>  $staff
     * @param  array<string, string>  $aliasMap
     * @return array<int, array{name: string, mentions: int, sentiment: string, note: string}>
     */
    public static function mergeStaffByAliases(array $staff, array $aliasMap): array
    {
        if ($aliasMap === []) {
            return $staff;
        }

        $merged = [];

        foreach ($staff as $person) {
            $canonical = $aliasMap[mb_strtolower($person['name'])] ?? $person['name'];
            $key = mb_strtolower($canonical);

            if (! isset($merged[$key])) {
                $merged[$key] = ['name' => $canonical, 'mentions' => 0, 'sentiment' => $person['sentiment'], 'note' => ''];
            }

            $merged[$key]['mentions'] += $person['mentions'];

            if ($person['note'] !== '' && ! str_contains($merged[$key]['note'], $person['note'])) {
                $merged[$key]['note'] = trim($merged[$key]['note'] === '' ? $person['note'] : $merged[$key]['note'].' '.$person['note']);
            }

            if ($merged[$key]['sentiment'] !== $person['sentiment']) {
                $merged[$key]['sentiment'] = 'mixed';
            }
        }

        $result = array_values($merged);
        usort($result, fn (array $a, array $b): int => $b['mentions'] <=> $a['mentions']);

        return $result;
    }

    /**
     * Add each person's share of total staff credits (for the bonus table).
     *
     * @param  array<int, array{name: string, mentions: int, sentiment: string, note: string}>  $staff
     * @return array<int, array{name: string, mentions: int, sentiment: string, note: string, share: int}>
     */
    public function withShares(array $staff): array
    {
        $total = array_sum(array_map(fn (array $s): int => $s['mentions'], $staff));

        return array_map(function (array $s) use ($total): array {
            $s['share'] = $total > 0 ? (int) round($s['mentions'] / $total * 100) : 0;

            return $s;
        }, $staff);
    }

    /** Pull the first {...} JSON object out of the model response. */
    protected function extractJson(string $text): string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        return ($start !== false && $end !== false && $end > $start)
            ? substr($text, $start, $end - $start + 1)
            : $text;
    }

    /** Deterministic, non-AI insights (for the live preview — no token cost). */
    public function fallbackFor(array $data): array
    {
        return $this->fallback($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{summary: string, recommendations: array<int, string>, staff: array<int, array{name: string, mentions: int, sentiment: string}>}
     */
    protected function fallback(array $data): array
    {
        $k = $data['kpis'];
        $trend = $k['avg']['delta'] > 0 ? 'up' : ($k['avg']['delta'] < 0 ? 'down' : 'flat');
        $trendWord = ['up' => 'improved', 'down' => 'declined', 'flat' => 'held steady'][$trend];

        $summary = sprintf(
            '%s received %d reviews in %s (%s%d vs the previous period), averaging %s★ — ratings %s by %s. '.
            '%d%% of reviews were positive (4–5★) and %d%% were critical (1–2★). The response rate was %d%%.',
            $data['businessName'],
            $k['total']['value'],
            $data['periodLabel'],
            $k['total']['delta'] >= 0 ? '+' : '',
            $k['total']['delta'],
            number_format((float) $k['avg']['value'], 2),
            $trendWord,
            number_format(abs((float) $k['avg']['delta']), 2),
            $data['positivePct'],
            $data['negativePct'],
            $k['responseRate']['value'],
        );

        $recs = [];
        if ($k['responseRate']['value'] < 100) {
            $recs[] = 'Reply to the remaining unanswered reviews — aim for a 100% response rate.';
        }
        if ($data['negativePct'] > 0) {
            $recs[] = 'Address recurring themes in 1–2★ reviews and follow up with those customers.';
        }
        $recs[] = 'Ask satisfied customers for a review while their experience is fresh to sustain volume.';

        return ['summary' => $summary, 'recommendations' => $recs, 'staff' => [], 'themes' => ['praise' => [], 'complaints' => []], 'topics' => [], 'topicsSummary' => ''];
    }
}
