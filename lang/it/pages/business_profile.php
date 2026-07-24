<?php

declare(strict_types=1);

return [
    'nav' => 'Informazioni',
    'title' => 'Informazioni sull’attività',

    'not_configured_title' => 'La gestione delle schede non è configurata',
    'not_configured_body' => 'Imposta ZERNIO_API_KEY nell’ambiente del server per modificare le schede Google Business.',

    'pick_location' => 'Sede',
    'status_live' => 'Online su Google',
    'status_suspended' => 'Sospeso da Google',
    'status_disabled' => 'Disattivato',
    'status_unverified' => 'Non verificato',

    'section_basics' => 'Scheda',
    'field_logo' => 'Logo della sede',
    'field_logo_helper' => 'Mostrato nell’anteprima dei post Google. In mancanza, viene usato il logo dello spazio.',
    'field_description' => 'Descrizione dell’attività',
    'field_description_helper' => 'Mostrata sulla tua scheda Google. Fino a 750 caratteri. Il modulo carica i valori attualmente online su Google.',
    'field_phone' => 'Numero di telefono',
    'field_website' => 'Sito web',

    'section_hours' => 'Orari di apertura',
    'section_hours_desc' => 'Una riga per ogni fascia oraria. Aggiungi due righe nello stesso giorno per un orario spezzato (ad esempio la pausa pranzo).',
    'add_hours' => 'Aggiungi fascia oraria',
    'field_day' => 'Giorno',
    'field_open' => 'Apertura',
    'field_close' => 'Chiusura',

    'day_monday' => 'Lunedì',
    'day_tuesday' => 'Martedì',
    'day_wednesday' => 'Mercoledì',
    'day_thursday' => 'Giovedì',
    'day_friday' => 'Venerdì',
    'day_saturday' => 'Sabato',
    'day_sunday' => 'Domenica',

    'section_special' => 'Orari speciali',
    'section_special_desc' => 'Festività ed eccezioni: sostituiscono gli orari abituali nelle date indicate.',

    'section_socials' => 'Profili social',
    'section_socials_desc' => 'Link ai tuoi profili sui social media, mostrati sulla tua scheda Google. Vengono pubblicati solo i campi compilati; lascia un campo vuoto per mantenere il valore attuale su Google.',
    'add_special' => 'Aggiungi orario speciale',
    'field_start_date' => 'Dal',
    'field_end_date' => 'Al',
    'field_closed' => 'Chiuso in questi giorni',

    'save' => 'Pubblica su Google',
    'saved' => 'Aggiornamento della scheda inviato a Google',
    'save_failed' => 'Aggiornamento non riuscito',
    'unmatched' => 'Non è stato ancora possibile associare questa sede a una scheda Google.',

    'field_additional_phones' => 'Numeri di telefono aggiuntivi',
    'field_additional_phones_placeholder' => 'aggiungi numero + Invio',
    'field_additional_phones_help' => 'Fino a due numeri aggiuntivi mostrati sulla scheda.',
    'field_timezone' => 'Fuso orario',
    'field_timezone_helper' => 'Gli orari di lavoro delle risposte automatiche vengono interpretati in questo fuso orario. Rilevato automaticamente alla connessione; correggilo qui se è errato.',
    'loading_live' => 'Caricamento dei dati attuali della scheda da Google…',
];
