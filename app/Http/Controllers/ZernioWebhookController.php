<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Reviews\ZernioWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives Zernio webhooks (review.new / review.updated → ingest + reply
 * reconciliation; account.connected / account.disconnected → status; anything
 * else — e.g. post.external.created for posts published outside our app — is
 * acknowledged and logged as unhandled). The raw body is HMAC-SHA256 signed
 * with our shared secret via the X-Late-Signature header (X-Zernio-Signature
 * fallback; see config services.reviews.webhook_secret). A bad signature →
 * 403; any other failure is logged and answered 200 so Zernio does not
 * retry-storm.
 */
class ZernioWebhookController extends Controller
{
    public function __construct(private readonly ZernioWebhookHandler $handler) {}

    public function handle(Request $request): Response
    {
        $raw = $request->getContent();

        // Zernio actually signs with X-Late-Signature (their platform's internal
        // name); X-Zernio-Signature is kept as a fallback per the openapi docs.
        $signature = (string) ($request->header('X-Late-Signature') ?: $request->header('X-Zernio-Signature'));

        if (! $this->signatureIsValid($raw, $signature)) {
            abort(403);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            Log::warning('Zernio webhook: non-JSON body ignored');

            return response('', 200);
        }

        try {
            $this->handler->handle($payload);
        } catch (Throwable $e) {
            // Never bubble up — a 500 makes Zernio retry, which would replay the
            // same failure. Log and acknowledge instead.
            Log::error('Zernio webhook handler failed', [
                'event' => $payload['event'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response('', 200);
    }

    /**
     * Hex-encoded HMAC-SHA256 of the RAW body keyed by the shared secret,
     * compared with hash_equals. Tolerates an optional `sha256=` prefix on the
     * header (the openapi only documents "HMAC-SHA256 via X-Zernio-Signature"
     * without pinning the encoding). No secret configured → reject.
     */
    private function signatureIsValid(string $rawBody, string $header): bool
    {
        $secret = (string) config('services.reviews.webhook_secret');
        if ($secret === '' || $header === '') {
            return false;
        }

        $provided = str_starts_with($header, 'sha256=') ? substr($header, 7) : $header;
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $provided);
    }
}
