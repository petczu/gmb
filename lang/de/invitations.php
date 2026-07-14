<?php

declare(strict_types=1);

// Öffentliche Einladungs-Seiten (annehmen / ungültig / falsches Konto). Die
// Sprache richtet sich nach der Einladung, die der Inhaber verschickt hat, damit
// eine deutsche Einladung diese Seiten ebenfalls auf Deutsch anzeigt.
return [
    'badge' => 'Einladung',

    // Annahme-Seite
    'youre_invited' => 'Du bist eingeladen',
    'join_title' => ':workspace beitreten',
    'join_body' => 'Du wurdest zu :workspace auf Repunio als :role eingeladen.',
    'accept_button' => 'Annehmen & beitreten',

    // Ungültig / abgelaufen
    'invalid_title' => 'Einladung nicht verfügbar',
    'invalid_body' => 'Dieser Einladungslink ist nicht mehr gültig. Er ist möglicherweise abgelaufen oder wurde bereits verwendet. Bitte den Absender um eine neue Einladung.',
    'go_to_app' => 'Zu Repunio',

    // Falsches Konto (mit einem anderen Konto angemeldet)
    'wrong_title' => 'Diese Einladung ist für jemand anderen',
    'wrong_body' => 'Sie wurde an :invited gesendet, aber du bist als :current angemeldet.',
    'wrong_hint' => 'Leite den Link weiter, oder melde dich ab und mit dieser E-Mail an, um die Einladung selbst anzunehmen.',
    'back_to_app' => 'Zurück zur App',
    'sign_out' => 'Abmelden',

    'roles' => [
        'owner' => 'Inhaber',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'member' => 'Mitglied',
        'viewer' => 'Betrachter',
        'guest' => 'Gast',
    ],
];
