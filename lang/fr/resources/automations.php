<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Aucune automatisation pour l’instant',
    'empty_desc' => 'Mettez en place une automatisation pour répondre automatiquement aux nouveaux avis, selon la note et l’établissement.',
    'empty_cta' => 'Nouvelle automatisation',

    // Table columns
    'col_rating' => 'Note',
    'rating_any' => 'toutes',
    'col_reply' => 'Réponse',
    'reply_ai' => 'IA : :agent',
    'reply_default' => 'Message par défaut',
    'col_mode' => 'Mode',
    'mode_approval' => 'Avec validation',
    'mode_auto' => 'Publication automatique',
    'col_scope' => 'Portée',
    'scope_all' => 'Tous les établissements',
    'scope_count' => ':count établissement(s)',

    // Run action
    'run_now' => 'Exécuter maintenant',
    'run_heading' => 'Exécuter cette automatisation maintenant',
    'run_desc' => 'Applique cette automatisation aux avis sans réponse correspondants. Vous pouvez la limiter à une période par date d’avis ; laissez les deux champs vides pour tout inclure.',
    'run_from' => 'Avis à partir du',
    'run_until' => 'Avis jusqu’au',
    'run_title' => '« :name » exécutée',
    'run_queued_title' => '« :name » en file d’attente',
    'run_queued_body' => 'L’exécution se fait en arrière-plan. Les nouveaux brouillons arrivent dans Validations et les réponses publiées automatiquement apparaissent sur les avis dans les prochaines minutes.',
    'run_body' => 'Générées :generated, publiées :published, en file d’attente :queued, ignorées :skipped.',

    // Form — Flow section
    'flow_section' => 'Déroulement',
    'flow_section_desc' => 'Quand l’automatisation s’exécute et à quels avis elle s’applique.',
    'trigger' => 'Déclencheur',
    'trigger_new_review' => 'Nouvel avis sur Google',
    'rating_is' => 'La note est…',
    'rating_helper' => 'Ne cochez rien pour l’appliquer à toutes les notes.',
    'all_locations' => 'Tous les établissements',
    'locations' => 'Établissements',
    'all_locations_helper' => 'Sert de règle générale : les automatisations limitées à des établissements précis ont la priorité pour ceux-ci.',
    'covered_by' => 'déjà dans « :name » (:ratings)',
    'any_rating' => 'toutes les notes',
    'overlap_title' => 'Chevauchement avec une autre automatisation',
    'overlap_body' => 'Correspond aussi aux mêmes avis : :list. Chaque avis est traité par une seule automatisation : les établissements précis l’emportent sur « Tous les établissements », sinon c’est la plus ancienne qui s’applique.',
    'respect_working_hours' => 'Respecter les horaires d’ouverture',
    'respect_working_hours_helper' => 'Répondre uniquement pendant les horaires d’ouverture de l’établissement.',
    'reply_to_previous' => 'Répondre aux avis précédents',
    'reply_to_previous_helper' => 'Traiter aussi les avis existants sans réponse (décompté de votre quota IA mensuel).',
    'approve_before_posting' => 'Valider avant publication',
    'approve_before_posting_helper' => 'Désactivé = publication automatique sur Google. Activé = passage par les Validations.',

    // Form — Timing section
    'timing_section' => 'Délais',
    'timing_section_desc' => 'Ajoutez un délai aléatoire (et éventuellement des horaires) pour que les réponses soient publiées à un rythme humain et naturel, plutôt qu’instantanément.',
    'reply_delay_min' => 'Délai minimum',
    'reply_delay_max' => 'Délai maximum',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'Les réponses sont publiées après un délai aléatoire compris entre le minimum et le maximum, pour paraître naturelles. Mettez les deux à 0 pour publier immédiatement.',
    'reply_delay_max_error' => 'Le délai maximum doit être supérieur ou égal au délai minimum.',
    'working_days' => 'Jours ouvrés',
    'working_start' => 'Heure de début',
    'working_end' => 'Heure de fin',
    'day_mon' => 'Lun',
    'day_tue' => 'Mar',
    'day_wed' => 'Mer',
    'day_thu' => 'Jeu',
    'day_fri' => 'Ven',
    'day_sat' => 'Sam',
    'day_sun' => 'Dim',

    // Form — Content section
    'content_section' => 'Contenu',
    'content_section_desc' => 'Quelle réponse envoyer.',
    'content_ai_agent' => 'Agent IA',
    'content_default_message' => 'Message par défaut',
    'ai_agent' => 'Agent IA',
    'default_message' => 'Message par défaut',
];
