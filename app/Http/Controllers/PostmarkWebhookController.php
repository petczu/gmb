<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailSuppression;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Receives Postmark webhooks. We only act on Bounce and Spam Complaint events:
 * hard/inactive bounces and complaints add the address to the suppression list
 * so we stop emailing it (protects sender reputation). The {secret} path segment
 * authenticates the request (Postmark URL is otherwise public).
 */
class PostmarkWebhookController extends Controller
{
    /** Bounce types that mean the address is permanently bad. */
    private const HARD_BOUNCES = ['HardBounce', 'Blocked', 'BadEmailAddress', 'SpamNotification', 'ManuallyDeactivated'];

    public function handle(Request $request, string $secret): Response
    {
        $expected = (string) config('services.postmark.webhook_secret');
        if ($expected === '' || ! hash_equals($expected, $secret)) {
            abort(403);
        }

        $email = mb_strtolower(trim((string) $request->input('Email')));
        if ($email === '') {
            return response('', 200);
        }

        switch ($request->input('RecordType')) {
            case 'SpamComplaint':
                EmailSuppression::suppress($email, 'spam_complaint');
                break;

            case 'Bounce':
                $type = (string) $request->input('Type');
                if ($request->boolean('Inactive') || in_array($type, self::HARD_BOUNCES, true)) {
                    EmailSuppression::suppress($email, 'bounce', $type);
                }
                break;
        }

        return response('', 200);
    }
}
