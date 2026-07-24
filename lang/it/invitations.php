<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Invito',

    // Accept page
    'youre_invited' => 'Sei stato invitato',
    'join_title' => 'Unisciti a :workspace',
    'join_body' => 'Sei stato invitato a :workspace su Repunio come :role.',
    'accept_button' => 'Accetta e unisciti',

    // Invalid / expired page
    'invalid_title' => 'Invito non disponibile',
    'invalid_body' => 'Questo link di invito non è più valido. Potrebbe essere scaduto o già stato utilizzato. Chiedi un nuovo invito a chi ti ha invitato.',
    'go_to_app' => 'Vai a Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'Questo invito è per un\'altra persona',
    'wrong_body' => 'È stato inviato a :invited, ma hai effettuato l\'accesso come :current.',
    'wrong_hint' => 'Inoltra il link a quella persona, oppure esci e accedi con quell\'email per accettarlo tu stesso.',
    'back_to_app' => 'Torna all\'app',
    'sign_out' => 'Esci',

    'roles' => [
        'owner' => 'Proprietario',
        'admin' => 'Amministratore',
        'manager' => 'Manager',
        'member' => 'Membro',
        'viewer' => 'Visualizzatore',
        'guest' => 'Ospite',
    ],
];
