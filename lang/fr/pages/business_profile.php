<?php

declare(strict_types=1);

return [
    'nav' => 'Informations',
    'title' => 'Informations de l’établissement',

    'not_configured_title' => 'La gestion des fiches n’est pas configurée',
    'not_configured_body' => 'Définissez ZERNIO_API_KEY dans l’environnement du serveur pour modifier les fiches d’établissement Google.',

    'pick_location' => 'Établissement',
    'status_live' => 'En ligne sur Google',
    'status_suspended' => 'Suspendu par Google',
    'status_disabled' => 'Désactivé',
    'status_unverified' => 'Non validé',

    'section_basics' => 'Fiche',
    'field_logo' => 'Logo de l’établissement',
    'field_logo_helper' => 'Affiché dans l’aperçu des posts Google. À défaut, le logo de l’espace est utilisé.',
    'field_description' => 'Description de l’établissement',
    'field_description_helper' => 'Affichée sur votre fiche Google. Jusqu’à 750 caractères. Le formulaire charge les valeurs actuellement en ligne sur Google.',
    'field_phone' => 'Numéro de téléphone',
    'field_website' => 'Site web',

    'section_hours' => 'Horaires d’ouverture',
    'section_hours_desc' => 'Une ligne par plage horaire. Ajoutez deux lignes le même jour pour une coupure (par exemple la pause déjeuner).',
    'add_hours' => 'Ajouter une plage horaire',
    'field_day' => 'Jour',
    'field_open' => 'Ouverture',
    'field_close' => 'Fermeture',

    'day_monday' => 'Lundi',
    'day_tuesday' => 'Mardi',
    'day_wednesday' => 'Mercredi',
    'day_thursday' => 'Jeudi',
    'day_friday' => 'Vendredi',
    'day_saturday' => 'Samedi',
    'day_sunday' => 'Dimanche',

    'section_special' => 'Horaires exceptionnels',
    'section_special_desc' => 'Jours fériés et exceptions : ils remplacent les horaires habituels aux dates indiquées.',

    'section_socials' => 'Réseaux sociaux',
    'section_socials_desc' => 'Liens vers vos profils sur les réseaux sociaux, affichés sur votre fiche Google. Seuls les champs remplis sont publiés ; laissez un champ vide pour conserver la valeur actuelle sur Google.',
    'add_special' => 'Ajouter un horaire exceptionnel',
    'field_start_date' => 'Du',
    'field_end_date' => 'Au',
    'field_closed' => 'Fermé ces jours-là',

    'save' => 'Publier sur Google',
    'saved' => 'Mise à jour de la fiche envoyée à Google',
    'save_failed' => 'Échec de la mise à jour',
    'unmatched' => 'Cet établissement n’a pas encore pu être associé à une fiche Google.',

    'field_additional_phones' => 'Numéros de téléphone supplémentaires',
    'field_additional_phones_placeholder' => 'ajouter un numéro + Entrée',
    'field_additional_phones_help' => 'Jusqu’à deux numéros supplémentaires affichés sur la fiche.',
    'field_timezone' => 'Fuseau horaire',
    'field_timezone_helper' => 'Les horaires des réponses automatiques sont interprétés dans ce fuseau. Détecté à la connexion ; corrigez-le ici si besoin.',
    'loading_live' => 'Chargement des données actuelles de la fiche depuis Google…',
];
