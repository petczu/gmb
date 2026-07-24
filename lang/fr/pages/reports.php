<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Restaurant de démonstration',
    'demo_period' => 'Rapport de performance · 30 derniers jours',
    'demo_five_star' => 'Part de 5 étoiles',
    'demo_summary_label' => 'Résumé exécutif',
    'demo_summary' => 'Le Restaurant de démonstration a reçu 38 avis ces 30 derniers jours (+9 par rapport à la période précédente), pour une moyenne de 4,60★. 84 % des avis étaient positifs et le taux de réponse a atteint 92 %. Les clients ont salué à plusieurs reprises la gentillesse de l’équipe et la rapidité du service.',

    'location' => 'Établissement',
    'business_multi' => ':name + :count autres',
    'compare' => 'Comparer',
    'compare_options' => [
        'none' => 'Ne pas comparer',
        'previous' => 'Période précédente',
        'custom' => 'Période personnalisée…',
    ],
    'compare_from' => 'Comparer du',
    'compare_to' => 'Comparer au',
    'report_language' => 'Langue du rapport',

    'content_section' => 'Contenu du rapport',
    'content_section_desc' => 'Choisissez un modèle, puis ajustez les blocs qui apparaissent dans le rapport.',
    'preset' => 'Modèle',
    'blocks' => 'Blocs',
    'competitors_block_hint' => 'Aucun concurrent suivi pour l’instant. Ajoutez-en d’abord dans Fiches > Concurrents.',
    'ai_instructions' => 'Consignes pour l’IA',
    'ai_instructions_help' => 'Indications facultatives pour le texte rédigé par l’IA. Très utile pour les noms du personnel : listez votre équipe et les surnoms afin que les mentions soient attribuées à la bonne personne. Enregistrées une fois et appliquées à tous les rapports suivants, y compris programmés.',
    'ai_instructions_placeholder' => 'Notre équipe : Eva, Alette, Suleyman (parfois écrit Suly), Lisa. Regrouper les surnoms sous le nom complet.',
    'ai_improve' => 'Améliorer avec l’IA',
    'ai_improve_empty' => 'Écrivez d’abord quelques notes, puis améliorez-les.',
    'ai_improve_rate_limited' => 'Trop de tentatives, réessayez plus tard.',
    'ai_improve_done' => 'Consignes améliorées',
    'ai_improve_failed' => 'Impossible d’améliorer les consignes, réessayez.',

    'schedule_report' => 'Envoyer de façon programmée',
    'schedule_heading' => 'Programmer ce rapport',
    'schedule_desc' => 'La sélection actuelle (période, établissement, comparaison, blocs) sera envoyée par e-mail au format PDF de façon récurrente.',
    'schedule_submit' => 'Créer la programmation',
    'schedule_created' => 'Programmation créée',
    'schedule_created_body' => 'Gérez-la dans Rapports → Rapports programmés.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'Il vous reste :left rapports IA sur :cap ce mois-ci',

    // Generate modal
    'generate_heading' => 'Générer le rapport avec l’IA ?',
    'generate_desc' => 'Générer le résumé exécutif par IA pour la sélection actuelle.',
    'generate_desc_left' => 'Cela consomme 1 de vos rapports IA mensuels, il vous en reste :left.',
    'generate_submit' => 'Générer',

    // Generate notifications
    'report_generated' => 'Rapport généré',
    'report_generated_body' => 'Le résumé IA est prêt, l’aperçu a été mis à jour. Utilisez Télécharger pour enregistrer le PDF.',
    'limit_reached' => 'Limite mensuelle de rapports atteinte',
    'limit_reached_body' => 'Affichage d’un rapport simple sans IA. Changez de plan pour une limite mensuelle plus élevée.',

    // Blade view
    'generate_report' => 'Générer le rapport',
    'generating' => 'Génération…',
    'download_pdf' => 'Télécharger le PDF',
    'download_first_tooltip' => 'Générez d’abord le rapport',
    'building' => 'Création du rapport…',
    'preview_title' => 'Aperçu du rapport',
];
