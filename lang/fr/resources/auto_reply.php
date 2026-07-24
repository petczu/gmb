<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Rien à valider',
    'empty_desc' => 'Quand les automatisations rédigent des réponses à valider, elles apparaissent ici.',

    // Columns
    'col_location' => 'Établissement',
    'col_author' => 'Auteur',
    'col_rating' => 'Note',
    'col_review' => 'Avis',
    'col_ai_reply' => 'Réponse IA',
    'col_status' => 'Statut',
    'col_source' => 'Source',
    'col_generated' => 'Générée',
    'source_ai' => 'IA',
    'source_template' => 'Modèle',

    // Statuses
    'status_pending' => 'En attente',
    'status_scheduled' => 'Programmée',
    'status_published' => 'Publiée',
    'status_skipped' => 'Ignorée',
    'status_failed' => 'En échec',
    'status_indicator' => 'Statut : :status',
    'scheduled_for' => 'Publication :time',

    // Actions
    'approve' => 'Valider et publier',
    'approve_publish' => 'Valider et publier',
    'edit_publish' => 'Modifier et publier',
    'review_reply' => 'Relire et répondre',
    'reply' => 'Répondre',
    'reject' => 'Rejeter',

    // Filters
    'filter_date' => 'Date de l’avis',
    'filter_from' => 'Du :date',
    'filter_to' => 'Jusqu’au :date',

    // Notifications
    'reply_published' => 'Réponse publiée',

    'approve_selected' => 'Valider et publier la sélection',
    'reject_selected' => 'Rejeter la sélection',
    'bulk_approve_confirm' => 'Publier sur Google toutes les réponses sélectionnées ? Elles sont mises en file d’attente et partent automatiquement dans les prochaines minutes.',
    'bulk_reject_confirm' => 'Rejeter tous les brouillons sélectionnés ?',
    'bulk_queued' => ':count réponses en file d’attente pour publication',
    'bulk_queued_body' => 'Elles se publient automatiquement dans les prochaines minutes. En cas d’échec, elles apparaissent dans le filtre En échec avec le motif.',
    'bulk_rejected' => ':count brouillons rejetés',
    'publish_failed_title' => 'Échec de la publication',
    'publish_not_found' => 'Google indique que cet avis n’existe plus. Il a peut-être été supprimé par son auteur, ou l’établissement a été reconnecté sous un autre compte. Le brouillon a été marqué en échec.',
    'publish_error' => 'La réponse n’a pas pu être publiée. Le brouillon a été marqué en échec : :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google n’a pas trouvé cet avis ou cet établissement pour y répondre. Il a peut-être été supprimé, ou les réponses ne sont pas disponibles pour cet établissement.',
    'error_rate_limited' => 'Google limite la cadence de publication des réponses. Une nouvelle tentative aura lieu automatiquement.',
    'error_unauthorized' => 'La connexion Google n’est pas autorisée à répondre ici. Reconnectez le compte et réessayez.',
    'error_generic' => 'La réponse n’a pas pu être publiée. Réessayez plus tard.',
    'draft_rejected' => 'Brouillon rejeté',

    // Scheduled items
    'post_now' => 'Publier maintenant',
    'post_now_confirm' => 'La réponse est publiée sur Google immédiatement, sans attendre son heure programmée.',
    'post_now_queued' => 'Réponse en file d’attente pour publication',
    'post_now_queued_body' => 'Elle part dans les prochaines minutes.',
    'cancel_scheduled' => 'Annuler',
    'cancel_scheduled_confirm' => 'Annuler cette réponse programmée ? Elle ne sera pas publiée.',
    'schedule_cancelled' => 'Réponse programmée annulée',

    // List tabs
    'tab_pending' => 'À valider',
    'tab_all' => 'Toutes',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Publier la sélection maintenant',
    'bulk_publish_now_confirm' => 'Les réponses sélectionnées ne tiennent pas compte de leur heure programmée et partent dans les prochaines minutes.',
    'cancel_scheduled_selected' => 'Annuler la programmation',
];
