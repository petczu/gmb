<?php

declare(strict_types=1);

namespace App\Mail\Templates;

use App\Models\EmailTemplate;
use Illuminate\Mail\Markdown;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Turns an editable markdown template into a fully branded, CSS-inlined email.
 * Scalar :placeholders come from the email's data; {{ button:Label }} becomes a
 * branded CTA linking to :url; {{ block }} tokens are replaced with pre-rendered
 * HTML fragments (e.g. a reviews table) supplied by the caller.
 */
class EmailTemplateRenderer
{
    public function __construct(private Markdown $markdown) {}

    /** @param array<string, string|int|null> $data */
    public function subject(string $key, string $locale, array $data = []): string
    {
        return $this->substitute($this->template($key, $locale)->subject, $data);
    }

    /**
     * @param  array<string, string|int|null>  $data
     * @param  array<string, string>  $blocks  token => pre-rendered HTML
     */
    public function render(string $key, string $locale, array $data = [], array $blocks = []): string
    {
        return $this->preview($this->template($key, $locale)->body, $data, $blocks);
    }

    /**
     * Render an arbitrary markdown body to a branded email — used for live
     * preview of unsaved edits in the admin panel.
     *
     * @param  array<string, string|int|null>  $data
     * @param  array<string, string>  $blocks
     */
    public function preview(string $body, array $data = [], array $blocks = []): string
    {
        $body = $this->substitute($body, $data);

        $html = Str::markdown($body);
        $html = $this->injectImages($html);
        $html = $this->injectButtons($html, (string) ($data['url'] ?? '#'));
        $html = $this->injectBlocks($html, $blocks);

        return (string) $this->markdown->render('mail.templated', ['slotHtml' => new HtmlString($html)]);
    }

    /** Resolve the row for key+locale, falling back to English then the catalogue default. */
    private function template(string $key, string $locale): EmailTemplate
    {
        $row = EmailTemplate::query()->where('key', $key)->where('locale', $locale)->first()
            ?? EmailTemplate::query()->where('key', $key)->where('locale', 'en')->first();

        if ($row !== null) {
            return $row;
        }

        return new EmailTemplate([
            'key' => $key,
            'locale' => $locale,
            'subject' => EmailTemplateCatalog::defaultSubject($key, $locale),
            'body' => EmailTemplateCatalog::defaultBody($key, $locale),
        ]);
    }

    /** @param array<string, string|int|null> $data */
    private function substitute(string $text, array $data): string
    {
        $map = [];
        foreach ($data as $token => $value) {
            $map[':'.$token] = (string) $value;
        }

        // strtr replaces longer tokens first, so :workspace beats :work.
        return strtr($text, $map);
    }

    /** Illustration keys shipped in public/images/email (decorative heroes). */
    public const IMAGES = [
        'welcome', 'reviews', 'celebration', 'attention',
        'team', 'time', 'payment-ok', 'payment-issue', 'robot', 'robot-agent', 'pause',
        'disconnected', 'connected', 'inbox', 'send-failed', 'progress',
        'recap', 'tips',
    ];

    /**
     * {{ image:key }} → centered hero illustration. Only whitelisted keys are
     * replaced (anything else is left visible so a typo is caught in preview).
     */
    private function injectImages(string $html): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*image:([a-z-]+)\s*\}\}/u',
            function (array $m): string {
                $key = trim($m[1]);

                if (! in_array($key, self::IMAGES, true)) {
                    return $m[0];
                }

                $src = e(rtrim((string) config('app.url'), '/')."/images/email/{$key}.jpg");

                return '<table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:4px 0 16px;">'
                    .'<img src="'.$src.'" alt="" width="440" style="display:block;width:100%;max-width:440px;height:auto;border-radius:8px;">'
                    .'</td></tr></table>';
            },
            $html,
        );
    }

    private function injectButtons(string $html, string $url): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*button:(.+?)\s*\}\}/u',
            fn (array $m): string => $this->button(trim($m[1]), $url),
            $html,
        );
    }

    /** @param array<string, string> $blocks */
    private function injectBlocks(string $html, array $blocks): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/u',
            fn (array $m): string => $blocks[$m[1]] ?? $m[0],
            $html,
        );
    }

    private function button(string $label, string $url): string
    {
        $label = e($label);
        $url = e($url);

        return <<<HTML
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:14px 0;">
            <a href="{$url}" style="display:inline-block;background:#1800ff;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-family:Arial,Helvetica,sans-serif;font-size:15px;">{$label}</a>
            </td></tr></table>
            HTML;
    }
}
