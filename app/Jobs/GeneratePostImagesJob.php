<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Location;
use App\Models\Post;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use App\Services\Zernio\ZernioRestClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Image as ImageEditor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Image as AiImageFile;
use Laravel\Ai\Image;
use Throwable;

/**
 * Generates one image PER configured image provider (Gemini and OpenAI) for
 * a draft post, so the user can pick the one they like. Off the request:
 * gpt-image-1 alone can take a minute. Candidates land on the draft's
 * image_candidates and a bell notification tells the requester.
 */
class GeneratePostImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** provider key => image model. Only configured providers actually run. */
    public const PROVIDERS = [
        'gemini' => 'gemini-2.5-flash-image',
        'openai' => 'gpt-image-2',
    ];

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public string $workspaceId,
        public int $postId,
        public string $prompt,
        public ?int $userId = null,
    ) {}

    public function handle(): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $post = Post::query()->whereKey($this->postId)->where('status', 'draft')->first();
            if ($post === null) {
                return;
            }

            // The subject comes from the user/caption; the frame around it
            // enforces the house style: photorealistic scenes at THIS kind of
            // business, and never paragraphs of rendered text. Category and
            // description come from the Google listing itself when available.
            $locations = Location::query()
                ->whereIn('id', array_map('intval', $post->location_ids ?? []))
                ->get();
            $business = $locations->pluck('name')->implode(', ') ?: (string) $workspace->name;

            [$category, $description] = $this->listingContext($locations->first());

            // Workspace-configurable style (Company page): base look, brand
            // notes, headline rules and up to three reference designs that are
            // attached to the model as images to imitate.
            $refs = collect((array) ($workspace->ai_image_refs ?? []))
                ->filter(fn ($path): bool => is_string($path) && Storage::disk('uploads')->exists($path))
                ->values();
            $baseStyle = (string) ($workspace->ai_image_base_style ?? 'photo');
            $headline = (bool) ($workspace->ai_image_headline ?? true);
            $headlineWords = max(2, min(8, (int) ($workspace->ai_image_headline_words ?? 5)));

            $styleLine = match ($refs->isNotEmpty() ? 'reference' : $baseStyle) {
                'reference' => 'Style: the attached image(s) are STYLE references only: reproduce their color palette, typography style, lighting and overall mood. You MUST invent a completely NEW scene for the subject: different people, different poses, different setting and camera angle. Never reuse the people, objects, layout or text of the references.',
                'illustration' => 'Style: high-quality flat illustration with the brand mood, clean shapes, consistent palette.',
                'minimal' => 'Style: minimal and typographic, lots of negative space, one strong visual element, brand colors.',
                default => 'Style: photorealistic, like a real photo: real people, natural light, candid, high detail. NOT an illustration, NOT a cartoon, NOT flat vector art.',
            };

            $prompt = implode("\n", array_filter([
                'Image for a Google Business Profile post.',
                'Business: '.$business.($category !== null ? ' ('.$category.')' : '').'. Show a fitting scene for exactly this kind of business.',
                $description !== null ? 'About the business: '.$description : null,
                filled($workspace->ai_image_notes ?? null) ? 'Brand style notes: '.trim((string) $workspace->ai_image_notes) : null,
                'Subject: '.$this->prompt,
                $styleLine,
                $headline
                    ? 'Text in the image: exactly ONE short headline of '.$headlineWords.' words maximum about the occasion, in the same language as the subject, integrated tastefully. No other text: never sentences, paragraphs or lists.'
                    : 'Text in the image: none at all. No words, letters or logos.',
                // The output is center-cropped to 4:3 for Google afterwards.
                'Composition: keep all text and key elements inside the central 80% of the frame (safe area); the sides will be cropped.',
            ]));

            $attachments = $refs
                ->map(fn (string $path) => AiImageFile::fromStorage($path, 'uploads'))
                ->all();

            $candidates = [];
            foreach (self::PROVIDERS as $provider => $model) {
                if (blank(config('ai.providers.'.$provider.'.key'))) {
                    continue;
                }

                try {
                    $image = Image::of($prompt)
                        ->attachments($attachments)
                        ->landscape()
                        ->timeout(240)
                        ->generate(provider: $provider, model: $model);

                    // Google post media must be 4:3 (1200x900), JPG/PNG, 10KB-5MB:
                    // center-crop and recompress so every candidate is ready as-is.
                    $path = ImageEditor::fromBytes((string) $image)
                        ->cover(1200, 900)
                        ->toJpeg()
                        ->quality(88)
                        ->storePubliclyAs('posts', 'ai-'.$provider.'-'.Str::random(12).'.jpg', 'uploads');

                    if (is_string($path)) {
                        $candidates[] = ['provider' => $provider, 'path' => $path];
                    }

                    // Ledger entry so image generations show up in the AI
                    // usage admin (token counts when the provider reports them).
                    app(AiCreditService::class)->logUsage(
                        $workspace,
                        'post_image',
                        $model,
                        (int) ($image->usage->promptTokens ?? 0),
                        (int) ($image->usage->completionTokens ?? 0),
                        0,
                        'post',
                        (string) $post->id,
                    );
                } catch (Throwable $e) {
                    report($e);
                    Log::warning('AI image generation failed', ['provider' => $provider, 'post' => $post->id, 'error' => $e->getMessage()]);
                }
            }

            // Always resolve the pending ([]) marker: real candidates, or
            // null so the composer offers the prompt again after a failure.
            $post->forceFill(['image_candidates' => $candidates !== [] ? $candidates : null])->save();

        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }

    /**
     * The Google listing's own primary category and (shortened) description,
     * best-effort: the stored listing_data copy first, then a live lookup.
     *
     * @return array{0: ?string, 1: ?string} [category, description]
     */
    private function listingContext(?Location $location): array
    {
        if ($location === null) {
            return [null, null];
        }

        $description = (string) (((array) ($location->listing_data ?? []))['description'] ?? '');
        $category = null;

        if (filled($location->zernio_account_id)) {
            try {
                $details = app(ZernioRestClient::class)->locationDetails(
                    (string) $location->zernio_account_id,
                    (string) $location->external_id,
                    'title,profile,categories',
                );
                $category = $details['categories']['primaryCategory']['displayName'] ?? null;
                $description = $description !== '' ? $description : (string) ($details['profile']['description'] ?? '');
            } catch (Throwable $e) {
                Log::info('Listing context lookup skipped', ['location' => $location->id, 'error' => $e->getMessage()]);
            }
        }

        return [
            filled($category) ? (string) $category : null,
            $description !== '' ? Str::limit($description, 300) : null,
        ];
    }
}
