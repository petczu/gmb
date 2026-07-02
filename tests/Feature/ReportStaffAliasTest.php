<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Reports\ReportInsights;
use Tests\TestCase;

/**
 * The deterministic staff-alias safety net: owner guidance lines like
 * "Suleyman (also written Suli, Suly)" are parsed into an alias map, and staff
 * rows the AI failed to merge are folded into the canonical person.
 */
class ReportStaffAliasTest extends TestCase
{
    private const GUIDANCE = <<<'TXT'
    Customers may use different names when mentioning game masters. Merge the following:
    Alex (also written Alexandra)
    Lisa (also written Liz)
    Suleyman (also written Suli, Suly)
    TXT;

    public function test_parses_alias_lines_and_skips_prose(): void
    {
        $map = ReportInsights::parseAliasMap(self::GUIDANCE);

        $this->assertSame([
            'alexandra' => 'Alex',
            'liz' => 'Lisa',
            'suli' => 'Suleyman',
            'suly' => 'Suleyman',
        ], $map);
    }

    public function test_parses_german_filler_words(): void
    {
        $map = ReportInsights::parseAliasMap("Suleyman (auch Suly geschrieben)\nEva (auch Evi)");

        $this->assertSame(['suly' => 'Suleyman', 'evi' => 'Eva'], $map);
    }

    public function test_empty_or_null_guidance_yields_no_aliases(): void
    {
        $this->assertSame([], ReportInsights::parseAliasMap(null));
        $this->assertSame([], ReportInsights::parseAliasMap('Just a note without any alias lines.'));
    }

    public function test_merges_unmerged_ai_rows_into_canonical_person(): void
    {
        $staff = [
            ['name' => 'Suly', 'mentions' => 2, 'sentiment' => 'positive', 'note' => 'Hilfsbereit und freundlich.'],
            ['name' => 'Eva', 'mentions' => 4, 'sentiment' => 'positive', 'note' => 'Game Masterin.'],
            ['name' => 'Suleyman', 'mentions' => 1, 'sentiment' => 'positive', 'note' => 'Bester Mann.'],
        ];

        $merged = ReportInsights::mergeStaffByAliases($staff, ReportInsights::parseAliasMap(self::GUIDANCE));

        $this->assertCount(2, $merged);
        $this->assertSame('Eva', $merged[0]['name']);
        $this->assertSame('Suleyman', $merged[1]['name']);
        $this->assertSame(3, $merged[1]['mentions']);
        $this->assertStringContainsString('Hilfsbereit', $merged[1]['note']);
        $this->assertStringContainsString('Bester Mann', $merged[1]['note']);
        $this->assertSame('positive', $merged[1]['sentiment']);
    }

    public function test_mixed_sentiment_when_variants_disagree(): void
    {
        $staff = [
            ['name' => 'Suly', 'mentions' => 1, 'sentiment' => 'negative', 'note' => ''],
            ['name' => 'Suleyman', 'mentions' => 2, 'sentiment' => 'positive', 'note' => ''],
        ];

        $merged = ReportInsights::mergeStaffByAliases($staff, ['suly' => 'Suleyman']);

        $this->assertCount(1, $merged);
        $this->assertSame('mixed', $merged[0]['sentiment']);
    }

    public function test_no_alias_map_leaves_staff_untouched(): void
    {
        $staff = [['name' => 'Suly', 'mentions' => 2, 'sentiment' => 'positive', 'note' => '']];

        $this->assertSame($staff, ReportInsights::mergeStaffByAliases($staff, []));
    }
}
