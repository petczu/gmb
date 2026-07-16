<?php

declare(strict_types=1);

return [
    'nav' => 'Posts',
    'title' => 'Google-Posts',

    'empty' => 'Noch keine Posts.',
    'empty_desc' => 'Erstelle deinen ersten Post, um Neuigkeiten, Angebote oder Events auf deinem Google-Profil zu zeigen.',

    'not_configured_title' => 'Content-Publishing ist nicht konfiguriert',
    'not_configured_body' => 'Setze ZERNIO_API_KEY in der Server-Umgebung, um Google-Posts zu aktivieren.',

    'col_created' => 'Erstellt',
    'col_type' => 'Typ',
    'col_caption' => 'Text',
    'col_locations' => 'Standorte',
    'col_status' => 'Status',
    'col_scheduled' => 'Geplant für',

    'type_update' => 'Update',
    'type_offer' => 'Angebot',
    'type_event' => 'Event',
    'type_photo' => 'Foto',

    'status_published' => 'Veröffentlicht',
    'status_scheduled' => 'Geplant',
    'status_in_progress' => 'Wird veröffentlicht…',
    'status_failed' => 'Fehlgeschlagen',
    'status_draft' => 'Entwurf',

    'create' => 'Neuer Post',
    'create_heading' => 'Neuer Google-Post',
    'submit' => 'Veröffentlichen',

    'field_type' => 'Post-Typ',
    'field_locations' => 'Standorte',
    'field_caption' => 'Text',
    'field_image' => 'Bild',
    'field_image_helper' => 'Das Bild muss öffentlich erreichbar sein, damit Google es laden kann: Uploads funktionieren nur von einem öffentlichen Server, nicht von einem lokalen Rechner.',
    'field_photo_category' => 'Foto-Kategorie',
    'field_title' => 'Titel',
    'field_starts' => 'Beginn',
    'field_ends' => 'Ende',
    'field_voucher' => 'Gutscheincode',
    'field_redeem_url' => 'Einlöse-Link',
    'field_terms_url' => 'Link zu den Bedingungen',
    'field_cta' => 'Call-to-Action-Button',
    'field_cta_url' => 'Button-Link',
    'field_schedule' => 'Für später planen',
    'field_schedule_helper' => 'Leer lassen, um sofort zu veröffentlichen. Zeiten sind UTC.',

    'cta_none' => 'Kein Button',
    'cta_book' => 'Buchen',
    'cta_order' => 'Online bestellen',
    'cta_shop' => 'Shoppen',
    'cta_learn_more' => 'Mehr erfahren',
    'cta_sign_up' => 'Registrieren',
    'cta_call' => 'Jetzt anrufen',

    'no_locations' => 'Wähle mindestens einen Standort.',
    'unmatched' => 'Diese Standorte konnten noch keinem Google-Eintrag zugeordnet werden:',
    'publish_failed' => 'Veröffentlichung fehlgeschlagen',
    'published_ok' => 'Post veröffentlicht',
    'scheduled_ok' => 'Post geplant',

    'delete' => 'Entfernen',
    'delete_desc' => 'Entfernt nur den Eintrag aus dieser Liste, der Post auf Google wird nicht gelöscht.',
    'deleted' => 'Eintrag entfernt',

    // Kalenderansicht
    'view_calendar' => 'Kalender',
    'view_list' => 'Liste',
    'view_month' => 'Monat',
    'view_week' => 'Woche',
    'today' => 'Heute',
    'all_locations' => 'Alle Standorte',
    'location_plus' => ':name +:count',
    'close' => 'Schließen',
    'location_count' => '{1} 1 Standort|[2,*] :count Standorte',
    'add_post' => 'Post',
    'add_note' => 'Notiz',

    // Entwürfe
    'save_draft' => 'Als Entwurf speichern',

    // Importierte Google-Beiträge
    'view' => 'Ansehen',
    'duplicate_draft' => 'Als Entwurf duplizieren',
    'duplicated_draft' => 'Entwurf erstellt',
    'draft_heading' => 'Entwurf bearbeiten',
    'draft_saved' => 'Entwurf gespeichert',
    'draft_delete' => 'Entwurf löschen',
    'draft_delete_desc' => 'Der Entwurf wird entfernt. Es wurde nichts auf Google veröffentlicht.',
    'draft_deleted' => 'Entwurf gelöscht',

    // Live-Vorschau
    'preview_label' => 'Vorschau',
    'preview_business' => 'Dein Unternehmen',
    'preview_now' => 'gerade eben',
    'preview_no_image' => 'Kein Bild',
    'preview_placeholder' => 'Dein Post-Text erscheint hier.',

    // Notizen
    'note_placeholder' => 'Private Notiz eingeben…',
    'note_color' => 'Notizfarbe',
    'note_tag' => '# Tag',
    'note_delete' => 'Notiz löschen',
    'note_delete_confirm' => 'Diese Notiz löschen?',
    'filter' => 'Filter',
    'notes_filter' => 'Notizen',
    'notes_filter_title' => 'Notizen nach Tag',
    'notes_filter_hint' => 'Abgewählte Tags werden im Kalender ausgeblendet.',
    'notes_filter_untagged' => 'Ohne Tag',

    'color_yellow' => 'Gelb',
    'color_orange' => 'Orange',
    'color_red' => 'Rot',
    'color_pink' => 'Pink',
    'color_purple' => 'Lila',
    'color_blue' => 'Blau',
    'color_teal' => 'Türkis',
    'color_green' => 'Grün',
    'color_gray' => 'Grau',

    // Externe Kalender
    'calendars_button' => '{0} Kalender|{1} 1 Kalender|[2,*] :count Kalender',
    'calendars_connect' => 'Externer Kalender',
    'calendars_title' => 'Externe Kalender',
    'calendars_empty' => 'Blende öffentliche Kalender in dieser Ansicht ein: Feiertage, Buchungen oder andere Content-Pläne.',
    'calendars_synced_ago' => 'Synchronisiert :ago',
    'calendars_refresh' => 'Jetzt synchronisieren',
    'calendars_synced' => 'Kalender synchronisiert',
    'calendars_sync_failed' => 'Einige Kalender konnten nicht synchronisiert werden',
    'calendar_add' => 'Externen Kalender hinzufügen',
    'calendar_add_submit' => 'Kalender hinzufügen',
    'calendar_name' => 'Name',
    'calendar_name_placeholder' => 'z. B. Feiertage Österreich',
    'calendar_url' => 'ICS-Link',
    'calendar_url_helper' => 'Eine öffentliche iCal/ICS-Feed-URL. In Google Kalender: Einstellungen, dann "Kalender integrieren", dann "Öffentliche Adresse im iCal-Format".',
    'calendar_color' => 'Farbe',
    'calendar_added' => 'Kalender hinzugefügt',
    'calendar_events_count' => '{0} Keine Termine im Feed gefunden.|{1} 1 Termin importiert.|[2,*] :count Termine importiert.',
    'calendar_sync_error' => 'Kalender hinzugefügt, aber der Feed konnte nicht synchronisiert werden',
    'calendar_delete' => 'Kalender entfernen',
    'calendar_delete_confirm' => 'Diesen Kalender und seine Termine aus der Ansicht entfernen?',
];
