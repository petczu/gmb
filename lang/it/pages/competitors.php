<?php

declare(strict_types=1);

return [
    'nav' => 'Concorrenti',
    'title' => 'Concorrenti',
    'intro' => 'Monitora le attività vicine e confronta la loro valutazione Google e il numero di recensioni con le tue sedi. I dati si aggiornano automaticamente ogni giorno.',

    'empty' => 'Ancora nessun concorrente.',
    'empty_desc' => 'Aggiungi un concorrente per monitorare la sua valutazione Google e la crescita delle recensioni.',

    'not_configured_title' => 'Il monitoraggio dei concorrenti non è configurato',
    'not_configured_body' => 'Imposta GOOGLE_PLACES_API_KEY nell’ambiente del server (una chiave API di Google Places) per abilitare il confronto con i concorrenti.',

    'col_battle' => 'Concorrente',
    'col_name' => 'Concorrente',
    'col_rating' => 'Valutazione',
    'col_reviews' => 'Recensioni',
    'filter_location' => 'Sede',
    'filter_city' => 'Città',
    'col_vs' => 'Rispetto a te',
    'col_location' => 'Il tuo lato',
    'col_checked' => 'Aggiornato',

    'untitled_battle' => 'Confronto senza nome',
    'default_battle_name' => '{1} :location contro 1 concorrente|[2,*] :location contro :count concorrenti',
    'own_locations_count' => ':count sedi',
    'rating_weighted_hint' => 'Valutazione media dei concorrenti, ponderata in base al loro numero di recensioni.',

    'vs_ahead' => 'Sei in vantaggio di :delta ★',
    'vs_behind' => 'Sono in vantaggio di :delta ★',
    'vs_tied' => 'Pari',
    'vs_unknown' => '—',

    'add' => 'Aggiungi concorrente',
    'add_heading' => 'Aggiungi concorrente',
    'edit' => 'Modifica',
    'edit_heading' => 'Modifica i concorrenti',
    'field_name' => 'Nome del confronto',
    'field_name_placeholder' => 'es. Via principale contro il quartiere',
    'field_your_locations' => 'Le tue sedi',
    'field_your_locations_helper' => 'Scegli una o più delle tue sedi per il tuo lato.',
    'field_place' => 'Concorrente',
    'field_places' => 'Concorrenti',
    'field_places_helper' => 'Digita il nome di un’attività (e la città) per cercare in Google Places.',
    'already_tracked' => 'Stai già monitorando questo concorrente.',
    'saved' => 'Concorrente salvato',
    'some_failed' => ':count concorrente/i non è stato possibile recuperare e sono stati saltati.',

    'duplicate' => 'Duplica',
    'duplicate_heading' => 'Duplica concorrente',
    'copy_name' => ':name (copia)',
    'remove' => 'Rimuovi',
    'removed' => 'Concorrente rimosso',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Crea gruppo',
    'group_heading' => 'Raggruppa concorrenti',
    'group_need_two' => 'Scegli almeno due concorrenti da raggruppare.',
    'group_created' => 'Gruppo creato',
    'group_removed' => 'Gruppo rimosso',
    'ungroup' => 'Rimuovi dal gruppo',
    'ungrouped' => 'Rimosso dal gruppo',
    'field_group_name' => 'Nome del gruppo',
    'field_group_competitors' => 'Concorrenti',
    'field_group_competitors_helper' => 'Questi concorrenti vengono combinati in un’unica linea sul grafico di crescita, con le loro recensioni sommate.',
    'col_group' => 'Gruppo',

    'col_new_reviews' => 'Nuove recensioni',
    'col_rating_trend' => 'Variazione della valutazione',
    'col_trend' => 'Andamento',
    'you_delta' => 'tu: :delta',
    'trend_hint' => 'Nuove recensioni nel periodo selezionato.',
    'trend_collecting' => 'raccolta in corso…',
    'period_4w' => '4 settimane',
    'period_12w' => '3 mesi',

    'collecting' => 'raccolta in corso…',
    'prev_delta' => 'prec.: :delta',
    'period_7d' => '7 giorni',
    'period_6m' => '6 mesi',
    'no_change' => 'nessuna variazione',
    'search_failed' => 'La ricerca dei concorrenti è temporaneamente non disponibile',

    // Competitor detail modal
    'view' => 'Vedi dettagli',
    'close' => 'Chiudi',
    'you' => 'Tu',
    'reviews_count' => '{1} 1 recensione|[2,*] :count recensioni',
    'no_distribution' => 'La ripartizione per stelle non è ancora disponibile (si aggiorna al prossimo aggiornamento).',
];
