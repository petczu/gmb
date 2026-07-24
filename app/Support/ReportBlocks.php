<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Registry of the configurable report blocks and the presets that select them.
 *
 * A report's content is an ordered list of enabled block keys; the Blade
 * template renders only those, in canonical order. Presets are just named
 * starting selections — the user can then toggle individual blocks on/off.
 */
class ReportBlocks
{
    /** Canonical render order. The Blade iterates blocks in this order. */
    public const ORDER = [
        'glance',
        'performance',
        'summary',
        'topics',
        'staff',
        'cadence',
        'themes',
        'responses',
        'distribution',
        'volume',
        'competitors',
        'highlights',
        'recommendations',
        'methodology',
    ];

    /** Blocks whose content comes from the AI call (ReportInsights). */
    public const AI = ['summary', 'topics', 'staff', 'themes', 'recommendations'];

    /**
     * key => label shown in the report-builder toggles.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::ORDER as $key) {
            $labels[$key] = __('report.block_'.$key);
        }

        return $labels;
    }

    /**
     * Named presets → enabled block keys.
     *
     * @return array<string, array<int, string>>
     */
    public static function presets(): array
    {
        return [
            'standard' => ['glance', 'performance', 'summary', 'topics', 'distribution', 'volume', 'highlights', 'recommendations'],
            'full' => self::ORDER,
            'bonus' => ['glance', 'summary', 'staff', 'cadence', 'recommendations', 'methodology'],
            'compliance' => ['glance', 'summary', 'cadence', 'methodology'],
        ];
    }

    /** @return array<string, string> preset key => label */
    public static function presetLabels(): array
    {
        return [
            'standard' => __('report.preset_standard'),
            'full' => __('report.preset_full'),
            'bonus' => __('report.preset_bonus'),
            'compliance' => __('report.preset_compliance'),
        ];
    }

    /** Default block selection when nothing is configured yet. */
    public static function default(): array
    {
        return self::presets()['full'];
    }

    /**
     * Normalise/clean a requested block list to known keys, in canonical order.
     *
     * @param  array<int, string>|string|null  $blocks
     * @return array<int, string>
     */
    public static function normalize(array|string|null $blocks): array
    {
        if (is_string($blocks)) {
            $blocks = array_filter(explode(',', $blocks));
        }

        $blocks = $blocks ?: self::default();
        $set = array_intersect(self::ORDER, $blocks);

        return array_values($set ?: self::default());
    }
}
