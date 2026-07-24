<?php

declare(strict_types=1);

return [
    'nav' => 'Posts',
    'title' => 'Posts Google',

    'empty' => 'Aucun post pour l’instant.',
    'empty_desc' => 'Créez votre premier post pour afficher des actualités, des offres ou des événements sur votre fiche Google.',

    'not_configured_title' => 'La publication de contenu n’est pas configurée',
    'not_configured_body' => 'Définissez ZERNIO_API_KEY dans l’environnement du serveur pour activer les posts Google.',

    'col_created' => 'Créé',
    'col_type' => 'Type',
    'col_caption' => 'Texte',
    'col_locations' => 'Établissements',
    'col_status' => 'Statut',
    'col_scheduled' => 'Programmé pour',

    'type_update' => 'Actualité',
    'type_offer' => 'Offre',
    'type_event' => 'Événement',
    'type_photo' => 'Photo',

    'status_published' => 'Publié',
    'status_scheduled' => 'Programmé',
    'status_in_progress' => 'Publication…',
    'status_failed' => 'Échec',
    'status_draft' => 'Brouillon',

    'create' => 'Nouveau post',
    'create_heading' => 'Nouveau post Google',
    'submit' => 'Publier',

    'field_type' => 'Type de post',
    'field_locations' => 'Établissements',
    'field_caption' => 'Texte',
    'field_image' => 'Image',
    'field_image_helper' => 'L’image doit être accessible publiquement pour que Google puisse la récupérer : l’envoi ne fonctionne que depuis un serveur public, pas depuis une machine locale.',
    'field_photo_category' => 'Catégorie de photo',
    'field_title' => 'Titre',
    'field_starts' => 'Début',
    'field_ends' => 'Fin',
    'field_voucher' => 'Code promo',
    'field_redeem_url' => 'Lien pour en profiter',
    'field_terms_url' => 'Lien vers les conditions',
    'field_cta' => 'Bouton d’action',
    'field_cta_url' => 'Lien du bouton',
    'field_schedule' => 'Programmer pour plus tard',
    'field_schedule_helper' => 'Laissez vide pour publier immédiatement. Les heures sont en UTC.',

    'cta_none' => 'Aucun bouton',
    'cta_book' => 'Réserver',
    'cta_order' => 'Commander en ligne',
    'cta_shop' => 'Acheter',
    'cta_learn_more' => 'En savoir plus',
    'cta_sign_up' => 'S’inscrire',
    'cta_call' => 'Appeler',

    'no_locations' => 'Choisissez au moins un établissement.',
    'unmatched' => 'Ces établissements n’ont pas encore pu être associés à une fiche Google :',
    'publish_failed' => 'Échec de la publication',
    'published_ok' => 'Post publié',
    'scheduled_ok' => 'Post programmé',

    'delete' => 'Retirer',
    'delete_desc' => 'Cela retire uniquement l’entrée de cette liste, cela ne supprime pas le post de Google.',
    'deleted' => 'Entrée retirée',

    // Calendar view
    'view_calendar' => 'Calendrier',
    'view_list' => 'Liste',
    'view_month' => 'Mois',
    'view_week' => 'Semaine',
    'today' => 'Aujourd’hui',
    'all_locations' => 'Tous les établissements',
    'location_plus' => ':name +:count',
    'close' => 'Fermer',
    'location_count' => '{1} 1 établissement|[2,*] :count établissements',
    'add_post' => 'Post',
    'add_note' => 'Note',

    // Drafts
    'save_draft' => 'Enregistrer le brouillon',

    // Imported Google posts
    'view' => 'Voir',
    'duplicate_draft' => 'Dupliquer en brouillon',
    'duplicated_draft' => 'Brouillon créé',
    'draft_heading' => 'Modifier le brouillon',
    'draft_saved' => 'Brouillon enregistré',
    'draft_delete' => 'Supprimer le brouillon',
    'draft_delete_desc' => 'Le brouillon sera supprimé. Rien n’a été publié sur Google.',
    'draft_deleted' => 'Brouillon supprimé',

    // Live preview
    'preview_label' => 'Aperçu',
    'preview_business' => 'Votre établissement',
    'preview_now' => 'à l’instant',
    'preview_no_image' => 'Pas d’image',
    'preview_placeholder' => 'Le texte de votre post apparaîtra ici.',

    // Sticky notes
    'note_placeholder' => 'Saisissez une note privée…',
    'note_color' => 'Couleur de la note',
    'note_tag' => '# étiquette',
    'note_delete' => 'Supprimer la note',
    'note_delete_confirm' => 'Supprimer cette note ?',
    'filter' => 'Filtrer',
    'notes_filter' => 'Notes',
    'notes_filter_title' => 'Notes par étiquette',
    'notes_filter_hint' => 'Les étiquettes décochées sont masquées du calendrier.',
    'notes_filter_untagged' => 'Sans étiquette',

    'color_yellow' => 'Jaune',
    'color_orange' => 'Orange',
    'color_red' => 'Rouge',
    'color_pink' => 'Rose',
    'color_purple' => 'Violet',
    'color_blue' => 'Bleu',
    'color_teal' => 'Turquoise',
    'color_green' => 'Vert',
    'color_gray' => 'Gris',

    // External calendars
    'calendars_button' => '{0} Calendriers|{1} 1 calendrier|[2,*] :count calendriers',
    'calendars_connect' => 'Calendrier externe',
    'calendars_title' => 'Calendriers externes',
    'calendars_empty' => 'Superposez des calendriers publics à cette vue : jours fériés, réservations ou autres plans de contenu.',
    'calendars_synced_ago' => 'Synchronisé :ago',
    'calendars_refresh' => 'Synchroniser maintenant',
    'calendars_synced' => 'Calendriers synchronisés',
    'calendars_sync_failed' => 'Certains calendriers n’ont pas pu être synchronisés',
    'calendar_add' => 'Ajouter un calendrier externe',
    'calendar_add_submit' => 'Ajouter le calendrier',
    'calendar_name' => 'Nom',
    'calendar_name_placeholder' => 'ex. Jours fériés France',
    'calendar_url' => 'Lien ICS',
    'calendar_url_helper' => 'L’URL d’un flux iCal/ICS public. Dans Google Agenda : Paramètres, puis « Intégrer l’agenda », puis « Adresse publique au format iCal ».',
    'calendar_color' => 'Couleur',
    'calendar_added' => 'Calendrier ajouté',
    'calendar_events_count' => '{0} Aucun événement trouvé dans le flux.|{1} 1 événement importé.|[2,*] :count événements importés.',
    'calendar_sync_error' => 'Calendrier ajouté, mais le flux n’a pas pu être synchronisé',
    'calendar_delete' => 'Retirer le calendrier',
    'calendar_delete_confirm' => 'Retirer ce calendrier et ses événements de la vue ?',
];
