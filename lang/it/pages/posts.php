<?php

declare(strict_types=1);

return [
    'nav' => 'Post',
    'title' => 'Post Google',

    'empty' => 'Ancora nessun post.',
    'empty_desc' => 'Crea il tuo primo post per mostrare novità, offerte o eventi sul tuo profilo Google.',

    'not_configured_title' => 'La pubblicazione dei contenuti non è configurata',
    'not_configured_body' => 'Imposta ZERNIO_API_KEY nell’ambiente del server per abilitare i post Google.',

    'col_created' => 'Creato',
    'col_type' => 'Tipo',
    'col_caption' => 'Testo',
    'col_locations' => 'Sedi',
    'col_status' => 'Stato',
    'col_scheduled' => 'Programmato per',

    'type_update' => 'Novità',
    'type_offer' => 'Offerta',
    'type_event' => 'Evento',
    'type_photo' => 'Foto',

    'status_published' => 'Pubblicato',
    'status_scheduled' => 'Programmato',
    'status_in_progress' => 'Pubblicazione…',
    'status_failed' => 'Non riuscito',
    'status_draft' => 'Bozza',

    'create' => 'Nuovo post',
    'create_heading' => 'Nuovo post Google',
    'submit' => 'Pubblica',

    'field_type' => 'Tipo di post',
    'field_locations' => 'Sedi',
    'field_caption' => 'Testo',
    'field_image' => 'Immagine',
    'field_image_helper' => 'L’immagine deve essere raggiungibile pubblicamente affinché Google possa recuperarla: il caricamento funziona solo da un server pubblico, non da una macchina locale.',
    'field_photo_category' => 'Categoria della foto',
    'field_title' => 'Titolo',
    'field_starts' => 'Inizio',
    'field_ends' => 'Fine',
    'field_voucher' => 'Codice promozionale',
    'field_redeem_url' => 'Link per riscattare',
    'field_terms_url' => 'Link ai termini e condizioni',
    'field_cta' => 'Pulsante di azione',
    'field_cta_url' => 'Link del pulsante',
    'field_schedule' => 'Programma per dopo',
    'field_schedule_helper' => 'Lascia vuoto per pubblicare subito. Gli orari sono in UTC.',

    'cta_none' => 'Nessun pulsante',
    'cta_book' => 'Prenota',
    'cta_order' => 'Ordina online',
    'cta_shop' => 'Acquista',
    'cta_learn_more' => 'Scopri di più',
    'cta_sign_up' => 'Iscriviti',
    'cta_call' => 'Chiama ora',

    'no_locations' => 'Scegli almeno una sede.',
    'unmatched' => 'Queste sedi non sono ancora state associate a una scheda Google:',
    'publish_failed' => 'Pubblicazione non riuscita',
    'published_ok' => 'Post pubblicato',
    'scheduled_ok' => 'Post programmato',

    'delete' => 'Rimuovi',
    'delete_desc' => 'Questo rimuove solo la voce da questo elenco, non elimina il post da Google.',
    'deleted' => 'Voce rimossa',

    // Calendar view
    'view_calendar' => 'Calendario',
    'view_list' => 'Elenco',
    'view_month' => 'Mese',
    'view_week' => 'Settimana',
    'today' => 'Oggi',
    'all_locations' => 'Tutte le sedi',
    'location_plus' => ':name +:count',
    'close' => 'Chiudi',
    'location_count' => '{1} 1 sede|[2,*] :count sedi',
    'add_post' => 'Post',
    'add_note' => 'Nota',

    // Drafts
    'save_draft' => 'Salva bozza',

    // Imported Google posts
    'view' => 'Visualizza',
    'duplicate_draft' => 'Duplica come bozza',
    'duplicated_draft' => 'Bozza creata',
    'draft_heading' => 'Modifica bozza',
    'draft_saved' => 'Bozza salvata',
    'draft_delete' => 'Elimina bozza',
    'draft_delete_desc' => 'La bozza verrà rimossa. Non è stato pubblicato nulla su Google.',
    'draft_deleted' => 'Bozza eliminata',

    // Live preview
    'preview_label' => 'Anteprima',
    'preview_business' => 'La tua attività',
    'preview_now' => 'proprio ora',
    'preview_no_image' => 'Nessuna immagine',
    'preview_placeholder' => 'Il testo del tuo post apparirà qui.',

    // Sticky notes
    'note_placeholder' => 'Scrivi una nota privata…',
    'note_color' => 'Colore della nota',
    'note_tag' => '# etichetta',
    'note_delete' => 'Elimina nota',
    'note_delete_confirm' => 'Eliminare questa nota?',
    'filter' => 'Filtra',
    'notes_filter' => 'Note',
    'notes_filter_title' => 'Note per etichetta',
    'notes_filter_hint' => 'Le etichette deselezionate sono nascoste dal calendario.',
    'notes_filter_untagged' => 'Senza etichetta',

    'color_yellow' => 'Giallo',
    'color_orange' => 'Arancione',
    'color_red' => 'Rosso',
    'color_pink' => 'Rosa',
    'color_purple' => 'Viola',
    'color_blue' => 'Blu',
    'color_teal' => 'Verde acqua',
    'color_green' => 'Verde',
    'color_gray' => 'Grigio',

    // External calendars
    'calendars_button' => '{0} Calendari|{1} 1 calendario|[2,*] :count calendari',
    'calendars_connect' => 'Calendario esterno',
    'calendars_title' => 'Calendari esterni',
    'calendars_empty' => 'Sovrapponi calendari pubblici a questa vista: festività, prenotazioni o altri piani di contenuti.',
    'calendars_synced_ago' => 'Sincronizzato :ago',
    'calendars_refresh' => 'Sincronizza ora',
    'calendars_synced' => 'Calendari sincronizzati',
    'calendars_sync_failed' => 'Alcuni calendari non sono stati sincronizzati',
    'calendar_add' => 'Aggiungi calendario esterno',
    'calendar_add_submit' => 'Aggiungi calendario',
    'calendar_name' => 'Nome',
    'calendar_name_placeholder' => 'es. Festività Italia',
    'calendar_url' => 'Link ICS',
    'calendar_url_helper' => 'L’URL di un feed iCal/ICS pubblico. In Google Calendar: Impostazioni, poi "Integra calendario", poi "Indirizzo pubblico in formato iCal".',
    'calendar_color' => 'Colore',
    'calendar_added' => 'Calendario aggiunto',
    'calendar_events_count' => '{0} Nessun evento trovato nel feed.|{1} 1 evento importato.|[2,*] :count eventi importati.',
    'calendar_sync_error' => 'Calendario aggiunto, ma il feed non è stato sincronizzato',
    'calendar_delete' => 'Rimuovi calendario',
    'calendar_delete_confirm' => 'Rimuovere questo calendario e i suoi eventi dalla vista?',
];
