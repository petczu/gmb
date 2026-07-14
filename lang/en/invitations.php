<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Invitation',

    // Accept page
    'youre_invited' => "You're invited",
    'join_title' => 'Join :workspace',
    'join_body' => "You've been invited to :workspace on Repunio as :role.",
    'accept_button' => 'Accept & join',

    // Invalid / expired page
    'invalid_title' => 'Invitation unavailable',
    'invalid_body' => 'This invitation link is no longer valid. It may have expired or already been used. Please ask whoever invited you for a new one.',
    'go_to_app' => 'Go to Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'This invite is for someone else',
    'wrong_body' => "It was sent to :invited, but you're signed in as :current.",
    'wrong_hint' => 'Forward the link to them, or sign out and sign in with that email to accept it yourself.',
    'back_to_app' => 'Back to the app',
    'sign_out' => 'Sign out',

    'roles' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'member' => 'Member',
        'viewer' => 'Viewer',
        'guest' => 'Guest',
    ],
];
