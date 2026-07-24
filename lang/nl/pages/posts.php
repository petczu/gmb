<?php

declare(strict_types=1);

return [
    'nav' => 'Berichten',
    'title' => 'Google-berichten',

    'empty' => 'Nog geen berichten.',
    'empty_desc' => 'Maak je eerste bericht om nieuws, aanbiedingen of evenementen op je Google-profiel te tonen.',

    'not_configured_title' => 'Content publiceren is niet geconfigureerd',
    'not_configured_body' => 'Stel ZERNIO_API_KEY in de serveromgeving in om Google-berichten in te schakelen.',

    'col_created' => 'Aangemaakt',
    'col_type' => 'Type',
    'col_caption' => 'Tekst',
    'col_locations' => 'Locaties',
    'col_status' => 'Status',
    'col_scheduled' => 'Gepland voor',

    'type_update' => 'Update',
    'type_offer' => 'Aanbieding',
    'type_event' => 'Evenement',
    'type_photo' => 'Foto',

    'status_published' => 'Gepubliceerd',
    'status_scheduled' => 'Gepland',
    'status_in_progress' => 'Publiceren…',
    'status_failed' => 'Mislukt',
    'status_draft' => 'Concept',

    'create' => 'Nieuw bericht',
    'create_heading' => 'Nieuw Google-bericht',
    'submit' => 'Publiceren',

    'field_type' => 'Berichttype',
    'field_locations' => 'Locaties',
    'field_caption' => 'Tekst',
    'field_image' => 'Afbeelding',
    'field_image_helper' => 'De afbeelding moet openbaar bereikbaar zijn zodat Google deze kan ophalen: uploaden werkt alleen vanaf een openbare server, niet vanaf een lokale machine.',
    'field_photo_category' => 'Fotocategorie',
    'field_title' => 'Titel',
    'field_starts' => 'Start',
    'field_ends' => 'Einde',
    'field_voucher' => 'Kortingscode',
    'field_redeem_url' => 'Inwissellink',
    'field_terms_url' => 'Link naar voorwaarden',
    'field_cta' => 'Actieknop',
    'field_cta_url' => 'Link van de knop',
    'field_schedule' => 'Plannen voor later',
    'field_schedule_helper' => 'Laat leeg om direct te publiceren. Tijden zijn in UTC.',

    'cta_none' => 'Geen knop',
    'cta_book' => 'Boeken',
    'cta_order' => 'Online bestellen',
    'cta_shop' => 'Kopen',
    'cta_learn_more' => 'Meer informatie',
    'cta_sign_up' => 'Aanmelden',
    'cta_call' => 'Nu bellen',

    'no_locations' => 'Kies minstens één locatie.',
    'unmatched' => 'Deze locaties konden nog niet aan een Google-vermelding worden gekoppeld:',
    'publish_failed' => 'Publiceren mislukt',
    'published_ok' => 'Bericht gepubliceerd',
    'scheduled_ok' => 'Bericht gepland',

    'delete' => 'Verwijderen',
    'delete_desc' => 'Dit verwijdert alleen het item uit deze lijst, het verwijdert het bericht niet van Google.',
    'deleted' => 'Item verwijderd',

    // Calendar view
    'view_calendar' => 'Kalender',
    'view_list' => 'Lijst',
    'view_month' => 'Maand',
    'view_week' => 'Week',
    'today' => 'Vandaag',
    'all_locations' => 'Alle locaties',
    'location_plus' => ':name +:count',
    'close' => 'Sluiten',
    'location_count' => '{1} 1 locatie|[2,*] :count locaties',
    'add_post' => 'Bericht',
    'add_note' => 'Notitie',

    // Drafts
    'save_draft' => 'Concept opslaan',

    // Imported Google posts
    'view' => 'Bekijken',
    'duplicate_draft' => 'Dupliceren als concept',
    'duplicated_draft' => 'Concept aangemaakt',
    'draft_heading' => 'Concept bewerken',
    'draft_saved' => 'Concept opgeslagen',
    'draft_delete' => 'Concept verwijderen',
    'draft_delete_desc' => 'Het concept wordt verwijderd. Er is niets naar Google gepubliceerd.',
    'draft_deleted' => 'Concept verwijderd',

    // Live preview
    'preview_label' => 'Voorbeeld',
    'preview_business' => 'Jouw bedrijf',
    'preview_now' => 'zojuist',
    'preview_no_image' => 'Geen afbeelding',
    'preview_placeholder' => 'De tekst van je bericht verschijnt hier.',

    // Sticky notes
    'note_placeholder' => 'Typ een privénotitie…',
    'note_color' => 'Notitiekleur',
    'note_tag' => '# label',
    'note_delete' => 'Notitie verwijderen',
    'note_delete_confirm' => 'Deze notitie verwijderen?',
    'filter' => 'Filteren',
    'notes_filter' => 'Notities',
    'notes_filter_title' => 'Notities per label',
    'notes_filter_hint' => 'Uitgevinkte labels worden verborgen in de kalender.',
    'notes_filter_untagged' => 'Zonder label',

    'color_yellow' => 'Geel',
    'color_orange' => 'Oranje',
    'color_red' => 'Rood',
    'color_pink' => 'Roze',
    'color_purple' => 'Paars',
    'color_blue' => 'Blauw',
    'color_teal' => 'Turquoise',
    'color_green' => 'Groen',
    'color_gray' => 'Grijs',

    // External calendars
    'calendars_button' => '{0} Kalenders|{1} 1 kalender|[2,*] :count kalenders',
    'calendars_connect' => 'Externe kalender',
    'calendars_title' => 'Externe kalenders',
    'calendars_empty' => 'Leg openbare kalenders over deze weergave heen: feestdagen, boekingen of andere contentplanningen.',
    'calendars_synced_ago' => 'Gesynchroniseerd :ago',
    'calendars_refresh' => 'Nu synchroniseren',
    'calendars_synced' => 'Kalenders gesynchroniseerd',
    'calendars_sync_failed' => 'Sommige kalenders konden niet worden gesynchroniseerd',
    'calendar_add' => 'Externe kalender toevoegen',
    'calendar_add_submit' => 'Kalender toevoegen',
    'calendar_name' => 'Naam',
    'calendar_name_placeholder' => 'bijv. Feestdagen Nederland',
    'calendar_url' => 'ICS-link',
    'calendar_url_helper' => 'De URL van een openbare iCal/ICS-feed. In Google Agenda: Instellingen, dan "Agenda integreren", dan "Openbaar adres in iCal-indeling".',
    'calendar_color' => 'Kleur',
    'calendar_added' => 'Kalender toegevoegd',
    'calendar_events_count' => '{0} Geen evenementen gevonden in de feed.|{1} 1 evenement geïmporteerd.|[2,*] :count evenementen geïmporteerd.',
    'calendar_sync_error' => 'Kalender toegevoegd, maar de feed kon niet worden gesynchroniseerd',
    'calendar_delete' => 'Kalender verwijderen',
    'calendar_delete_confirm' => 'Deze kalender en zijn evenementen uit de weergave verwijderen?',
];
