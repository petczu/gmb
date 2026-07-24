<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Les webhooks sont une fonctionnalité Pro',
    'pro_only_body' => 'Recevez un POST HTTP signé dès qu’un avis arrive, qu’une réponse est publiée, qu’un objectif est atteint ou qu’une anomalie est détectée. Passez au plan Pro pour ajouter des points de terminaison.',
    'see_plans' => 'Voir les plans',

    'intro' => 'Nous envoyons en POST une charge utile JSON signée à votre point de terminaison à chaque événement souscrit, avec de nouvelles tentatives en cas d’échec. Vérifiez l’en-tête X-Webhook-Signature avec le secret de votre point de terminaison.',

    'docs_link' => 'Documentation des webhooks',
    'empty' => 'Aucun point de terminaison pour l’instant.',
    'col_url' => 'URL',
    'col_events' => 'Événements',
    'col_active' => 'Actif',
    'col_last' => 'Dernier envoi',

    'create' => 'Ajouter un point de terminaison',
    'create_heading' => 'Ajouter un point de terminaison webhook',
    'edit' => 'Modifier',
    'delete' => 'Supprimer',
    'saved' => 'Point de terminaison enregistré',
    'created' => 'Point de terminaison ajouté',
    'deleted' => 'Point de terminaison supprimé',

    'field_name' => 'Nom (facultatif)',
    'field_url' => 'URL du point de terminaison',
    'field_events' => 'Événements',
    'field_active' => 'Actif',

    'secret' => 'Secret',
    'secret_heading' => 'Secret de signature',
    'secret_desc' => 'Utilisez-le pour vérifier la signature de la charge utile.',
    'signature_hint' => 'Chaque requête est signée :',

    'deliveries' => 'Envois',
    'deliveries_heading' => 'Envois récents',
    'no_deliveries' => 'Aucun envoi pour l’instant.',
    'attempts' => 'tentatives',
    'resend' => 'Renvoyer',
    'resent' => 'Envoi remis en file d’attente',
    'status_pending' => 'En attente',
    'status_success' => 'Livré',
    'status_failed' => 'Échec',

    'event_review_created' => 'Nouvel avis',
    'event_reply_published' => 'Réponse publiée',
    'event_goal_reached' => 'Objectif atteint',
    'event_anomaly_detected' => 'Anomalie détectée',
];
