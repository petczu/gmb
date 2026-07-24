<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Zaproszenie',

    // Accept page
    'youre_invited' => 'Masz zaproszenie',
    'join_title' => 'Dołącz do :workspace',
    'join_body' => 'Otrzymałeś zaproszenie do :workspace w Repunio jako :role.',
    'accept_button' => 'Przyjmij i dołącz',

    // Invalid / expired page
    'invalid_title' => 'Zaproszenie niedostępne',
    'invalid_body' => 'Ten link z zaproszeniem nie jest już ważny. Mógł wygasnąć lub został już użyty. Poproś osobę, która Cię zaprosiła, o nowe zaproszenie.',
    'go_to_app' => 'Przejdź do Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'To zaproszenie jest dla kogoś innego',
    'wrong_body' => 'Zostało wysłane do :invited, ale jesteś zalogowany jako :current.',
    'wrong_hint' => 'Przekaż im link albo wyloguj się i zaloguj z tym adresem e-mail, aby przyjąć zaproszenie samodzielnie.',
    'back_to_app' => 'Powrót do aplikacji',
    'sign_out' => 'Wyloguj się',

    'roles' => [
        'owner' => 'Właściciel',
        'admin' => 'Administrator',
        'manager' => 'Menedżer',
        'member' => 'Członek',
        'viewer' => 'Obserwator',
        'guest' => 'Gość',
    ],
];
