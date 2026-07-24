<?php

declare(strict_types=1);

return [
    'nav' => 'Concurrents',
    'title' => 'Concurrents',
    'intro' => 'Suivez les établissements à proximité et comparez leur note Google et leur nombre d’avis avec les vôtres. Les chiffres se mettent à jour automatiquement chaque jour.',

    'empty' => 'Aucun concurrent pour l’instant.',
    'empty_desc' => 'Ajoutez un concurrent pour suivre sa note Google et la croissance de ses avis.',

    'not_configured_title' => 'Le suivi des concurrents n’est pas configuré',
    'not_configured_body' => 'Définissez GOOGLE_PLACES_API_KEY dans l’environnement du serveur (une clé API Google Places) pour activer le comparatif concurrents.',

    'col_battle' => 'Concurrent',
    'col_name' => 'Concurrent',
    'col_rating' => 'Note',
    'col_reviews' => 'Avis',
    'filter_location' => 'Établissement',
    'filter_city' => 'Ville',
    'col_vs' => 'Face à vous',
    'col_location' => 'Votre côté',
    'col_checked' => 'Mis à jour',

    'untitled_battle' => 'Comparatif sans nom',
    'default_battle_name' => '{1} :location face à 1 concurrent|[2,*] :location face à :count concurrents',
    'own_locations_count' => ':count établissements',
    'rating_weighted_hint' => 'Note moyenne des concurrents, pondérée par leur nombre d’avis.',

    'vs_ahead' => 'Vous devancez de :delta ★',
    'vs_behind' => 'Ils devancent de :delta ★',
    'vs_tied' => 'Égalité',
    'vs_unknown' => '—',

    'add' => 'Ajouter un concurrent',
    'add_heading' => 'Ajouter un concurrent',
    'edit' => 'Modifier',
    'edit_heading' => 'Modifier les concurrents',
    'field_name' => 'Nom du comparatif',
    'field_name_placeholder' => 'ex. Rue principale contre le quartier',
    'field_your_locations' => 'Vos établissements',
    'field_your_locations_helper' => 'Choisissez un ou plusieurs de vos établissements pour votre côté.',
    'field_place' => 'Concurrent',
    'field_places' => 'Concurrents',
    'field_places_helper' => 'Saisissez un nom d’établissement (et la ville) pour chercher dans Google Places.',
    'already_tracked' => 'Vous suivez déjà ce concurrent.',
    'saved' => 'Concurrent enregistré',
    'some_failed' => ':count concurrent(s) n’ont pas pu être récupérés et ont été ignorés.',

    'duplicate' => 'Dupliquer',
    'duplicate_heading' => 'Dupliquer le concurrent',
    'copy_name' => ':name (copie)',
    'remove' => 'Retirer',
    'removed' => 'Concurrent retiré',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Créer un groupe',
    'group_heading' => 'Grouper des concurrents',
    'group_need_two' => 'Choisissez au moins deux concurrents à grouper.',
    'group_created' => 'Groupe créé',
    'group_removed' => 'Groupe supprimé',
    'ungroup' => 'Retirer du groupe',
    'ungrouped' => 'Retiré du groupe',
    'field_group_name' => 'Nom du groupe',
    'field_group_competitors' => 'Concurrents',
    'field_group_competitors_helper' => 'Ces concurrents sont regroupés en une seule courbe sur le graphique de croissance, avec leurs avis additionnés.',
    'col_group' => 'Groupe',

    'col_new_reviews' => 'Nouveaux avis',
    'col_rating_trend' => 'Évolution de la note',
    'col_trend' => 'Tendance',
    'you_delta' => 'vous : :delta',
    'trend_hint' => 'Nouveaux avis sur la période sélectionnée.',
    'trend_collecting' => 'collecte en cours…',
    'period_4w' => '4 semaines',
    'period_12w' => '3 mois',

    'collecting' => 'collecte en cours…',
    'prev_delta' => 'précédent : :delta',
    'period_7d' => '7 jours',
    'period_6m' => '6 mois',
    'no_change' => 'aucun changement',
    'search_failed' => 'La recherche de concurrents est temporairement indisponible',

    // Competitor detail modal
    'view' => 'Voir le détail',
    'close' => 'Fermer',
    'you' => 'Vous',
    'reviews_count' => '{1} 1 avis|[2,*] :count avis',
    'no_distribution' => 'La répartition par étoiles n’est pas encore disponible (mise à jour à la prochaine actualisation).',
];
