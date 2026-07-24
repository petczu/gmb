<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Uitnodiging',

    // Accept page
    'youre_invited' => 'Je bent uitgenodigd',
    'join_title' => 'Word lid van :workspace',
    'join_body' => 'Je bent uitgenodigd voor :workspace op Repunio als :role.',
    'accept_button' => 'Accepteren en deelnemen',

    // Invalid / expired page
    'invalid_title' => 'Uitnodiging niet beschikbaar',
    'invalid_body' => 'Deze uitnodigingslink is niet langer geldig. Mogelijk is hij verlopen of al gebruikt. Vraag degene die je heeft uitgenodigd om een nieuwe.',
    'go_to_app' => 'Naar Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'Deze uitnodiging is voor iemand anders',
    'wrong_body' => 'Hij is verstuurd naar :invited, maar je bent ingelogd als :current.',
    'wrong_hint' => 'Stuur de link naar diegene door, of log uit en log in met dat e-mailadres om hem zelf te accepteren.',
    'back_to_app' => 'Terug naar de app',
    'sign_out' => 'Uitloggen',

    'roles' => [
        'owner' => 'Eigenaar',
        'admin' => 'Beheerder',
        'manager' => 'Manager',
        'member' => 'Lid',
        'viewer' => 'Lezer',
        'guest' => 'Gast',
    ],
];
