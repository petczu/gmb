<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Invitation',

    // Accept page
    'youre_invited' => 'Vous êtes invité',
    'join_title' => 'Rejoindre :workspace',
    'join_body' => 'Vous avez été invité à rejoindre :workspace sur Repunio en tant que :role.',
    'accept_button' => 'Accepter et rejoindre',

    // Invalid / expired page
    'invalid_title' => 'Invitation indisponible',
    'invalid_body' => 'Ce lien d’invitation n’est plus valide. Il a peut-être expiré ou déjà été utilisé. Demandez-en un nouveau à la personne qui vous a invité.',
    'go_to_app' => 'Aller sur Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'Cette invitation est destinée à quelqu’un d’autre',
    'wrong_body' => 'Elle a été envoyée à :invited, mais vous êtes connecté en tant que :current.',
    'wrong_hint' => 'Transférez-lui le lien, ou déconnectez-vous et connectez-vous avec cette adresse pour l’accepter vous-même.',
    'back_to_app' => 'Retour à l’application',
    'sign_out' => 'Se déconnecter',

    'roles' => [
        'owner' => 'Propriétaire',
        'admin' => 'Administrateur',
        'manager' => 'Responsable',
        'member' => 'Membre',
        'viewer' => 'Lecteur',
        'guest' => 'Invité',
    ],
];
