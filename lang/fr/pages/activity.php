<?php

declare(strict_types=1);

return [
    'nav' => 'Activité',
    'title' => 'Activité',
    'intro' => 'Tout ce qui s’est passé dans cet espace : réponses, rapports, changements d’équipe, connexions et intégrations.',

    'empty' => 'Aucune activité pour l’instant.',
    'empty_desc' => 'Les actions effectuées dans cet espace apparaîtront ici.',
    'system' => 'Système',

    'col_when' => 'Quand',
    'col_who' => 'Qui',
    'col_what' => 'Ce qui s’est passé',
    'col_category' => 'Catégorie',

    'cat_reviews' => 'Avis',
    'cat_posts' => 'Posts',
    'cat_reports' => 'Rapports',
    'cat_team' => 'Équipe',
    'cat_locations' => 'Établissements',
    'cat_integrations' => 'Intégrations',

    'action_reply_published' => 'A publié une réponse à l’avis :rating étoiles de :author sur :location',
    'action_report_generated' => 'A généré un rapport pour :period',
    'action_schedule_created' => 'A créé la programmation de rapport « :name » (:frequency)',
    'action_schedule_deleted' => 'A supprimé la programmation de rapport « :name »',
    'action_team_member_invited' => 'A invité :email en tant que :role',
    'action_team_guest_added' => 'A ajouté l’invité :member (:email)',
    'action_team_member_removed' => 'A retiré :member de l’équipe',
    'action_team_role_changed' => 'A changé le rôle de :member en :role',
    'action_location_connected' => 'A connecté l’établissement :location',
    'action_location_disconnected' => 'A déconnecté l’établissement :location',
    'action_apikey_created' => 'A créé la clé API « :name » (:scopes)',
    'action_apikey_revoked' => 'A révoqué la clé API « :name »',
    'action_webhook_created' => 'A ajouté le webhook :url',
    'action_webhook_deleted' => 'A supprimé le webhook :url',
    'action_review_page_updated' => 'A mis à jour la page de collecte d’avis (/r/:slug)',
    'action_post_published' => 'A publié un post Google de type :type sur :locations établissement(s)',
    'action_post_scheduled' => 'A programmé un post Google de type :type pour :locations établissement(s)',
    'action_listing_updated' => 'A mis à jour la fiche d’établissement Google de :location',
    'action_competitor_added' => 'A commencé à suivre le concurrent :name',
    'action_competitor_removed' => 'A cessé de suivre le concurrent :name',
    'action_competitor_group_created' => 'A créé le groupe de concurrents « :name »',
];
