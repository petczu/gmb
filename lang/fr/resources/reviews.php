<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Établissement',
    'col_author' => 'Auteur',
    'col_review' => 'Avis',
    'only_rating' => 'Note seule',
    'col_reply' => 'Réponse',
    'col_status' => 'Statut',
    'col_replied_by' => 'Répondu par',
    'col_date' => 'Date',
    'replied_ai' => 'IA',
    'replied_human' => 'Équipe',
    'replied_assistant' => 'Assistant',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— pas de réponse —',
    'status_replied' => 'Répondu',
    'status_pending' => 'En attente',
    'status_scheduled' => 'Programmé',
    'scheduled_for' => 'Publication le :datetime',
    'replied_at' => 'Répondu le :datetime',
    'status_failed' => 'Échec',

    // Filters
    'review_date' => 'Date de l’avis',
    'filter_from' => 'Du :date',
    'filter_to' => 'Au :date',
    'reply_status' => 'Statut de la réponse',
    'review_text' => 'Texte de l’avis',
    'with_text' => 'Avec texte',
    'rating_only' => 'Note seule',
    'photos' => 'Photos',
    'with_photos' => 'Avec photos',
    'without_photos' => 'Sans photos',

    // Reply action
    'edit_reply' => 'Modifier la réponse',
    'save_reply' => 'Enregistrer',
    'reply' => 'Répondre',
    'reply_to_review' => 'Répondre à l’avis',
    'no_written_review' => 'Pas de texte, uniquement une note.',
    'translated_by_google' => '🌐 Traduit par Google',
    'ai_agent' => 'Agent IA',
    'default_agent' => 'Agent par défaut',
    'your_reply' => 'Votre réponse',
    'generate_with_ai' => 'Générer avec l’IA',
    'generate' => 'Générer',
    'generating' => 'Génération de votre réponse…',
    'cancel' => 'Annuler',
    'add_emoji' => 'Ajouter un emoji',
    'show_translation' => 'Afficher la traduction (:language)',
    'translation_label' => 'Traduction (:language)',
    'translation_failed' => 'Échec de la traduction',
    'hide_emoji' => 'Masquer les emojis',
    'delete_reply' => 'Supprimer la réponse',
    'delete_reply_desc' => 'Cela retire la réponse de Google. L’avis lui-même n’est pas affecté.',
    'delete_confirm' => 'Supprimer',
    'submit_heading' => 'Publier votre réponse ?',
    'submit_desc' => 'Cela publie votre réponse publiquement sur Google, visible par toute personne qui voit l’avis.',
    'submit_confirm' => 'Publier',

    // AI cost hints
    'cost_generic' => 'Cela génère une réponse avec l’IA.',
    'cost_all_used' => 'Vous avez utilisé toutes vos réponses IA ce mois-ci. Rechargez un pack, changez de plan ou rédigez la réponse à la main.',
    'cost_credit' => 'Cela consomme 1 crédit (:count restants).',
    'cost_monthly' => 'Cela consomme 1 de vos réponses IA mensuelles, :count restantes.',

    // Notifications
    'reply_deleted' => 'Réponse supprimée',
    'no_changes' => 'Aucune modification à enregistrer',
    'reply_published' => 'Réponse publiée',
    'reply_failed' => 'La réponse n’a pas pu être publiée',
    'ai_limit_reached' => 'Limite IA atteinte',
    'ai_limit_body' => 'Vous avez utilisé toutes vos réponses IA ce mois-ci. Modifiez à la main ou changez de plan pour une limite plus élevée.',
    'generation_failed' => 'Échec de la génération',
    'reply_generated' => 'Réponse générée',
    'retry' => 'Réessayer',
    'retry_heading' => 'Réessayer cette réponse ?',
    'retry_desc' => 'Nous réessayons : republier le brouillon, ou le régénérer si l’étape IA a échoué.',
    'retry_queued' => 'Réponse remise en file d’attente',
    'retry_nothing' => 'Rien à réessayer. Répondez plutôt à la main.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Tous',
    'tab_needs_approval' => 'À valider',
    'tab_scheduled' => 'Programmés',
    'tab_published' => 'Publiés',
    'tab_failed' => 'En échec',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Affichage d’1 avis issu de votre notification par e-mail|[2,*] Affichage de :count avis issus de votre notification par e-mail',
    'from_email_clear' => 'Afficher tous les avis',

    // i18n label backfill
    'col_rating' => 'Note',
];
