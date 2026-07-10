<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Extension\Table\TableExtension;
use Throwable;

/**
 * Renders an Ask-AI assistant message to safe HTML: GitHub-flavoured markdown
 * (tables, lists, bold, headings) plus lightweight inline-SVG charts emitted
 * by the agent as fenced ```chart blocks. Raw HTML in the model output is
 * stripped, so the content is safe to echo unescaped.
 */
class ChatRenderer
{
    public static function render(string $content): HtmlString
    {
        // Pull out ```chart blocks first (markdown would mangle the JSON), render
        // them to SVG, and leave a placeholder that survives the markdown pass.
        $charts = [];
        $content = preg_replace_callback(
            '/```chart\s*\n(.*?)\n```/s',
            function (array $m) use (&$charts): string {
                $svg = self::chart((string) $m[1]);
                if ($svg === null) {
                    return $m[0]; // leave the fence as-is if it doesn't parse
                }
                $token = 'CHARTPLACEHOLDER'.count($charts).'ENDCHART';
                $charts[$token] = $svg;

                return "\n\n".$token."\n\n";
            },
            $content,
        ) ?? $content;

        $html = Str::markdown(
            $content,
            ['html_input' => 'strip', 'allow_unsafe_links' => false],
            [new TableExtension],
        );

        foreach ($charts as $token => $svg) {
            // The placeholder lands inside a <p> after markdown; swap the whole
            // paragraph for the chart.
            $html = str_replace(['<p>'.$token.'</p>', $token], $svg, $html);
        }

        return new HtmlString($html);
    }

    /**
     * Render a chart spec to inline SVG. Supported:
     *   {"type":"bar","title":"...","data":[{"label":"5★","value":202}, ...]}
     *   {"type":"donut","title":"...","data":[{"label":"Search","value":60}, ...]}
     */
    private static function chart(string $json): ?string
    {
        try {
            $spec = json_decode(trim($json), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($spec) || empty($spec['data']) || ! is_array($spec['data'])) {
            return null;
        }

        $rows = [];
        foreach ($spec['data'] as $row) {
            if (! is_array($row) || ! isset($row['label'])) {
                continue;
            }
            $rows[] = ['label' => (string) $row['label'], 'value' => (float) ($row['value'] ?? 0)];
        }

        if ($rows === []) {
            return null;
        }

        $title = isset($spec['title']) ? (string) $spec['title'] : '';

        return ($spec['type'] ?? 'bar') === 'donut'
            ? self::donut($rows, $title)
            : self::bar($rows, $title);
    }

    /**
     * Horizontal bars, one per row, labelled with the value. Sized for the
     * narrow chat panel; pure inline styles so no external CSS is needed.
     *
     * @param  list<array{label: string, value: float}>  $rows
     */
    private static function bar(array $rows, string $title): string
    {
        $max = max(1.0, max(array_map(fn (array $r): float => $r['value'], $rows)));

        $bars = '';
        foreach ($rows as $r) {
            $pct = max(2.0, round($r['value'] / $max * 100, 1));
            $value = self::num($r['value']);
            $bars .= '<div style="display:flex;align-items:center;gap:.5rem;margin:.28rem 0;font-size:.78rem;">'
                .'<span style="flex:0 0 5.2rem;color:#4b5563;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="'.e($r['label']).'">'.e($r['label']).'</span>'
                .'<span style="flex:1;height:.7rem;background:#e9e9ee;border-radius:999px;overflow:hidden;"><span style="display:block;height:100%;width:'.$pct.'%;background:#2d19ec;border-radius:999px;"></span></span>'
                .'<span style="flex:0 0 auto;font-weight:600;color:#111827;">'.$value.'</span>'
                .'</div>';
        }

        return self::frame($title, $bars);
    }

    /**
     * Donut with a legend. Colours cycle through a small brand-ish palette.
     *
     * @param  list<array{label: string, value: float}>  $rows
     */
    private static function donut(array $rows, string $title): string
    {
        $total = array_sum(array_map(fn (array $r): float => $r['value'], $rows));
        if ($total <= 0) {
            return self::bar($rows, $title);
        }

        $palette = ['#2d19ec', '#ef4444', '#f59e0b', '#22c55e', '#0ea5e9', '#a855f7', '#64748b'];
        $radius = 38;
        $circumference = 2 * M_PI * $radius;
        $offset = 0.0;

        $segments = '';
        $legend = '';
        foreach ($rows as $i => $r) {
            $color = $palette[$i % count($palette)];
            $len = $r['value'] / $total * $circumference;
            if ($len > 0) {
                $segments .= '<circle cx="50" cy="50" r="'.$radius.'" fill="none" stroke="'.$color.'" stroke-width="15" '
                    .'stroke-dasharray="'.round($len, 2).' '.round($circumference - $len, 2).'" '
                    .'stroke-dashoffset="'.round(-$offset, 2).'" transform="rotate(-90 50 50)"/>';
            }
            $offset += $len;

            $pct = round($r['value'] / $total * 100, 1);
            $legend .= '<div style="display:flex;align-items:center;gap:.4rem;font-size:.76rem;margin:.15rem 0;">'
                .'<span style="width:.6rem;height:.6rem;border-radius:999px;background:'.$color.';flex:none;"></span>'
                .'<span style="flex:1;color:#4b5563;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'.e($r['label']).'</span>'
                .'<span style="font-weight:600;color:#111827;">'.$pct.'%</span></div>';
        }

        $body = '<div style="display:flex;align-items:center;gap:.9rem;">'
            .'<svg viewBox="0 0 100 100" width="96" height="96" style="flex:none;">'
            .'<circle cx="50" cy="50" r="'.$radius.'" fill="none" stroke="#eef0f4" stroke-width="15"/>'.$segments
            .'<text x="50" y="54" text-anchor="middle" font-size="15" font-weight="700" fill="#111827">'.self::num($total).'</text></svg>'
            .'<div style="flex:1;min-width:0;">'.$legend.'</div></div>';

        return self::frame($title, $body);
    }

    private static function frame(string $title, string $body): string
    {
        $heading = $title !== ''
            ? '<div style="font-weight:600;font-size:.8rem;color:#111827;margin-bottom:.45rem;">'.e($title).'</div>'
            : '';

        return '<div style="margin:.5rem 0;padding:.7rem .8rem;background:#fff;border:1px solid #ececf1;border-radius:.7rem;">'.$heading.$body.'</div>';
    }

    /** 1245 → "1,245"; 4.93 → "4.93" (trims trailing zeros on decimals). */
    private static function num(float $value): string
    {
        return $value == (int) $value
            ? number_format($value)
            : rtrim(rtrim(number_format($value, 2), '0'), '.');
    }
}
