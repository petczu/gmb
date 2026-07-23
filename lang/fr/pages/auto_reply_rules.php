<?php

declare(strict_types=1);

return [
    'title' => 'Règles de réponse automatique IA',
    'section' => ':stars  ·  avis :rating étoiles',
    'enabled' => 'Réponse automatique activée',
    'mode' => 'Mode',
    'mode_auto' => 'Publication automatique',
    'mode_draft' => 'Brouillon à valider',
    'tone' => 'Ton / modèle',
    'tone_placeholder_positive' => 'ex. Chaleureux et reconnaissant.',
    'tone_placeholder_negative' => 'ex. S’excuser et proposer une solution.',
    'instruction' => 'Consigne supplémentaire (facultatif)',
    'language' => 'Langue',
    'language_placeholder' => 'Détecter d’après l’avis',
    'save_rules' => 'Enregistrer les règles',
    'rules_saved' => 'Règles de réponse enregistrées',

    // Blade intro
    'intro' => 'Définissez la façon dont l’IA répond à chaque note. <strong>Publication automatique</strong> envoie la réponse sur Google immédiatement ; <strong>Brouillon à valider</strong> la place d’abord dans la file de validation. Chaque génération est décomptée du quota IA mensuel de votre plan.',
];
