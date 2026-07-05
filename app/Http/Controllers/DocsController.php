<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Public developer documentation: markdown pages from resources/docs/*.md and
 * the interactive API reference (Scalar over public/api-spec/openapi.yaml).
 */
class DocsController extends Controller
{
    public function show(?string $slug = null): View
    {
        $slug = $slug ?? 'getting-started';

        // Only allow slugs that resolve inside resources/docs.
        abort_unless((bool) preg_match('#^[a-z0-9/-]+$#', $slug), 404);

        $path = resource_path("docs/{$slug}.md");

        if (! file_exists($path)) {
            abort(404);
        }

        $markdown = (string) file_get_contents($path);
        [$meta, $body] = $this->parseFrontmatter($markdown);

        $html = Str::markdown($body, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return view('docs.page', [
            'title' => $meta['title'] ?? Str::headline($slug),
            'description' => $meta['description'] ?? '',
            'content' => $html,
            'currentSlug' => $slug,
            'navigation' => $this->navigation(),
        ]);
    }

    public function apiReference(): View
    {
        return view('docs.api-reference', [
            'title' => 'API Reference',
            'description' => 'Interactive Repunio API explorer',
            'currentSlug' => 'api-reference',
            'navigation' => $this->navigation(),
        ]);
    }

    public function changelog(): View
    {
        return $this->show('changelog');
    }

    /**
     * @return array<int, array{group: ?string, items: array<int, array<string, string>>}>
     */
    private function navigation(): array
    {
        return [
            [
                'group' => 'Getting Started',
                'items' => [
                    ['slug' => 'getting-started', 'title' => 'Introduction'],
                    ['slug' => 'authentication', 'title' => 'Authentication'],
                ],
            ],
            [
                'group' => 'Endpoints',
                'items' => [
                    ['slug' => 'locations', 'title' => 'Locations'],
                    ['slug' => 'reviews', 'title' => 'Reviews'],
                    ['slug' => 'stats', 'title' => 'Stats'],
                ],
            ],
            [
                'group' => 'Webhooks',
                'items' => [
                    ['slug' => 'webhooks', 'title' => 'Events & signatures'],
                ],
            ],
            [
                'group' => 'MCP',
                'items' => [
                    ['slug' => 'mcp', 'title' => 'MCP server'],
                ],
            ],
            [
                'group' => 'Reference',
                'items' => [
                    ['slug' => 'errors', 'title' => 'Error Codes'],
                    ['slug' => 'api-reference', 'title' => 'API Reference', 'route' => 'docs.api-reference'],
                    ['slug' => 'changelog', 'title' => 'Changelog', 'route' => 'docs.changelog'],
                ],
            ],
        ];
    }

    /**
     * @return array{0: array<string, string>, 1: string}
     */
    private function parseFrontmatter(string $markdown): array
    {
        if (! str_starts_with(ltrim($markdown), '---')) {
            return [[], $markdown];
        }

        $parts = preg_split('/^---\s*$/m', $markdown, 3);

        if ($parts === false || count($parts) < 3) {
            return [[], $markdown];
        }

        $meta = [];
        foreach (explode("\n", trim($parts[1])) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $meta[trim($key)] = trim($value);
            }
        }

        return [$meta, trim($parts[2])];
    }
}
