<?php

declare(strict_types=1);

namespace App\Mail\Templates;

/**
 * Builds the pre-rendered HTML fragments that fill {{ table }} / {{ items }}
 * block placeholders in dynamic email templates (reviews tables, goal tables,
 * anomaly lists). The surrounding copy stays editable in the admin; the data
 * block is generated here so it always matches the email's real payload.
 */
class EmailBlocks
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows  cell HTML (callers escape text themselves)
     */
    public static function table(array $headers, array $rows): string
    {
        $head = '';
        foreach ($headers as $i => $h) {
            $align = $i === 0 ? 'left' : 'center';
            $head .= '<th style="text-align:'.$align.';padding:8px 10px;border-bottom:2px solid #edeff2;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.03em;">'.e($h).'</th>';
        }

        $body = '';
        foreach ($rows as $row) {
            $cells = '';
            foreach ($row as $i => $cell) {
                $align = $i === 0 ? 'left' : 'center';
                $cells .= '<td style="text-align:'.$align.';padding:9px 10px;border-bottom:1px solid #f1f3f5;font-size:14px;color:#374151;vertical-align:top;">'.$cell.'</td>';
            }
            $body .= '<tr>'.$cells.'</tr>';
        }

        return '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;margin:12px 0;">'
            .'<thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table>';
    }

    /**
     * @param  list<string>  $lines  pre-formatted HTML list items
     */
    public static function list(array $lines): string
    {
        $items = '';
        foreach ($lines as $line) {
            $items .= '<li style="margin:6px 0;line-height:1.5;">'.$line.'</li>';
        }

        return '<ul style="margin:12px 0;padding-inline-start:20px;color:#374151;font-size:14px;">'.$items.'</ul>';
    }

    /**
     * Per-row stat cards (a title + label/value pairs stacked vertically) —
     * mobile-friendly alternative to a wide numeric table.
     *
     * @param  list<array{title: string, rows: list<array{label: string, value: string}>}>  $items
     */
    public static function stats(array $items): string
    {
        $out = '';
        foreach ($items as $item) {
            $rows = '';
            foreach ($item['rows'] as $row) {
                // Value is treated as HTML so callers can pass a trend arrow;
                // plain-text values must be escaped by the caller.
                $rows .= '<tr>'
                    .'<td style="padding:5px 0;font-size:13px;color:#6b7280;">'.e((string) $row['label']).'</td>'
                    .'<td align="right" style="padding:5px 0;font-size:14px;font-weight:600;color:#111827;">'.((string) $row['value']).'</td>'
                    .'</tr>';
            }

            $out .= '<div style="border:1px solid #eef2f7;border-radius:10px;padding:12px 14px;margin:12px 0;background:#fafbfc;">'
                .'<div style="font-weight:600;font-size:15px;color:#111827;margin-bottom:4px;">'.e((string) $item['title']).'</div>'
                .'<table width="100%" cellpadding="0" cellspacing="0" role="presentation">'.$rows.'</table>'
                .'</div>';
        }

        return $out;
    }

    /**
     * Email-safe horizontal progress bar (table cells with a coloured fill —
     * works in Gmail/Outlook, unlike SVG). Shows "value / max · pct%".
     */
    public static function progressBar(int $value, int $max, string $color = '#1800ff'): string
    {
        $pct = $max > 0 ? (int) min(100, round($value / $max * 100)) : 0;
        $fill = $value <= 0 ? 0 : max(2, $pct);
        $track = 100 - $fill;

        $cells = '';
        if ($fill > 0) {
            $cells .= '<td width="'.$fill.'%" style="background:'.e($color).';height:10px;font-size:0;line-height:10px;border-radius:6px 0 0 6px;">&nbsp;</td>';
        }
        if ($track > 0) {
            $radius = $fill > 0 ? '0 6px 6px 0' : '6px';
            $cells .= '<td style="background:#eef0f3;height:10px;font-size:0;line-height:10px;border-radius:'.$radius.';">&nbsp;</td>';
        }

        return '<div style="margin:14px 0;">'
            .'<div style="font-size:13px;color:#6b7280;margin-bottom:6px;">'.e($value.' / '.$max.' · '.$pct.'%').'</div>'
            .'<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;"><tr>'.$cells.'</tr></table>'
            .'</div>';
    }

    /** Coloured trend indicator: green ▲ up, red ▼ down, grey — flat. */
    public static function trend(int $delta): string
    {
        if ($delta > 0) {
            return '<span style="color:#16a34a;font-weight:600;white-space:nowrap;">▲ +'.$delta.'</span>';
        }
        if ($delta < 0) {
            return '<span style="color:#dc2626;font-weight:600;white-space:nowrap;">▼ '.$delta.'</span>';
        }

        return '<span style="color:#9ca3af;font-weight:600;">—</span>';
    }

    public static function stars(int $rating): string
    {
        return '<span style="color:#f59e0b;letter-spacing:1px;">'.str_repeat('★', max(0, min(5, $rating))).'</span>';
    }

    /**
     * Mobile-friendly vertical review cards (author + stars on top, optional
     * location, then the full review text) instead of a wide table.
     *
     * @param  list<array{author?: string, rating?: int|null, location?: string|null, snippet?: string}>  $items
     */
    public static function reviews(array $items): string
    {
        $cards = '';
        foreach ($items as $item) {
            $author = e((string) ($item['author'] ?? ''));
            $snippet = e((string) ($item['snippet'] ?? ''));
            $rating = $item['rating'] ?? null;
            $stars = $rating !== null
                ? '<td align="right" style="white-space:nowrap;vertical-align:top;font-size:15px;padding-inline-start:8px;">'.self::stars((int) $rating).'</td>'
                : '';
            $location = isset($item['location']) && $item['location'] !== null && $item['location'] !== ''
                ? '<div style="font-size:12px;color:#9ca3af;margin-top:2px;">'.e((string) $item['location']).'</div>'
                : '';

            $cards .= '<div style="border:1px solid #eef2f7;border-radius:10px;padding:12px 14px;margin:12px 0;background:#fafbfc;">'
                .'<table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr>'
                .'<td style="font-weight:600;font-size:15px;color:#111827;vertical-align:top;">'.$author.'</td>'.$stars
                .'</tr></table>'.$location
                .'<div style="font-size:14px;color:#374151;margin-top:8px;line-height:1.55;">'.$snippet.'</div>'
                .'</div>';
        }

        return $cards;
    }
}
