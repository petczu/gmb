<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Tiny inline SVG sparkline (single polyline, no axes) used by the
 * competitor benchmark page and dashboard widget.
 */
class Sparkline
{
    /**
     * @param  array<int, int|float>  $values
     */
    public static function svg(array $values, int $width = 100, int $height = 24, string $stroke = '#2d19ec'): ?HtmlString
    {
        if (count($values) < 2) {
            return null;
        }

        $min = min($values);
        $max = max($values);
        $range = max(1, $max - $min);
        $step = ($width - 4) / (count($values) - 1);

        $points = [];
        foreach ($values as $i => $value) {
            $x = round($i * $step, 1);
            $y = round(($height - 2) - (($value - $min) / $range) * ($height - 6), 1);
            $points[] = $x.','.$y;
        }

        return new HtmlString(
            '<svg width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" fill="none" xmlns="http://www.w3.org/2000/svg">'
            .'<polyline points="'.implode(' ', $points).'" stroke="'.$stroke.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>'
            .'</svg>'
        );
    }
}
