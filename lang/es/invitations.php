<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Invitación',

    // Accept page
    'youre_invited' => 'Te han invitado',
    'join_title' => 'Únete a :workspace',
    'join_body' => 'Te han invitado a :workspace en Repunio como :role.',
    'accept_button' => 'Aceptar y unirme',

    // Invalid / expired page
    'invalid_title' => 'Invitación no disponible',
    'invalid_body' => 'Este enlace de invitación ya no es válido. Puede que haya caducado o que ya se haya usado. Pide una nueva a quien te invitó.',
    'go_to_app' => 'Ir a Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'Esta invitación es para otra persona',
    'wrong_body' => 'Se envió a :invited, pero has iniciado sesión como :current.',
    'wrong_hint' => 'Reenvíale el enlace o cierra sesión e inicia sesión con ese correo para aceptarla tú.',
    'back_to_app' => 'Volver a la app',
    'sign_out' => 'Cerrar sesión',

    'roles' => [
        'owner' => 'Propietario',
        'admin' => 'Administrador',
        'manager' => 'Gestor',
        'member' => 'Miembro',
        'viewer' => 'Lector',
        'guest' => 'Invitado',
    ],
];
