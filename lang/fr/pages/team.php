<?php

declare(strict_types=1);

return [
    'col_name' => 'Nom',
    'col_email' => 'E-mail',
    'col_role' => 'Rôle',

    'edit' => 'Modifier',
    'location_access' => 'Accès aux établissements',
    'location_access_helper' => 'Laissez vide pour donner accès à tous les établissements.',
    'guest_location_helper' => 'Laissez vide pour notifier sur tous les établissements, ou choisissez-en certains.',

    'change_role' => 'Changer le rôle',
    'remove' => 'Retirer',
    'add_member' => 'Ajouter un membre',
    'add_member_email_helper' => 'Nous lui enverrons une invitation par e-mail à rejoindre cet espace.',
    'add_guest' => 'Ajouter un invité',
    'add_guest_helper' => 'Un invité reçoit uniquement les notifications que vous lui adressez. Pas de connexion, pas d’accès à l’espace.',
    'guest_language' => 'Langue',
    'guest_language_helper' => 'Les notifications et rapports destinés à ce contact sont envoyés dans cette langue.',
    'name' => 'Nom',

    // Notifications
    'member_updated' => 'Membre mis à jour',
    'role_updated' => 'Rôle changé en :role',
    'member_removed' => 'Membre retiré',
    'invitation_sent' => 'Invitation envoyée',
    'guest_added' => 'Invité ajouté',

    // Pending invitations
    'pending_hint' => 'Envoyées mais pas encore acceptées. Renvoyez l’e-mail ou révoquez l’invitation si elle est partie à la mauvaise adresse.',
    'invite_resend' => 'Renvoyer',
    'invite_revoke' => 'Révoquer',
    'invite_revoke_desc' => 'Le lien d’invitation cesse de fonctionner immédiatement. La personne n’est pas prévenue.',
    'invite_revoked' => 'Invitation révoquée',
    'col_status' => 'Statut',
    'status_active' => 'Actif',
    'status_pending' => 'En attente',
    'role_hint_member' => 'Reçoit une invitation par e-mail et se connecte avec son propre compte.',
    'role_hint_guest' => 'Un invité ne peut pas se connecter. Il reçoit uniquement les notifications que vous lui adressez (nouveaux avis, rapports).',

    // i18n label backfill
    'email' => 'E-mail',
    'role' => 'Rôle',
];
