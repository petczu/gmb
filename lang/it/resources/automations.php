<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Ancora nessuna automazione',
    'empty_desc' => 'Configura un\'automazione per rispondere automaticamente alle nuove recensioni, in base a valutazione e sede.',
    'empty_cta' => 'Nuova automazione',

    // Table columns
    'col_rating' => 'Valutazione',
    'rating_any' => 'qualsiasi',
    'col_reply' => 'Risposta',
    'reply_ai' => 'IA: :agent',
    'reply_default' => 'Messaggio predefinito',
    'col_mode' => 'Modalità',
    'mode_approval' => 'Con approvazione',
    'mode_auto' => 'Pubblicazione automatica',
    'col_scope' => 'Ambito',
    'scope_all' => 'Tutte le sedi',
    'scope_count' => ':count sede/i',

    // Run action
    'run_now' => 'Esegui ora',
    'run_heading' => 'Esegui subito questa automazione',
    'run_desc' => 'Applica questa automazione alle recensioni senza risposta corrispondenti. Puoi limitarla a un periodo per data della recensione; lascia entrambi i campi vuoti per includere tutto.',
    'run_from' => 'Recensioni dal',
    'run_until' => 'Recensioni fino al',
    'run_title' => '«:name» eseguita',
    'run_queued_title' => '«:name» in coda',
    'run_queued_body' => 'L\'esecuzione avviene in background. Le nuove bozze arrivano nelle Approvazioni e le risposte pubblicate automaticamente appaiono sulle recensioni nei prossimi minuti.',
    'run_body' => 'Generate :generated, pubblicate :published, in coda :queued, ignorate :skipped.',

    // Form — Flow section
    'flow_section' => 'Svolgimento',
    'flow_section_desc' => 'Quando l\'automazione viene eseguita e a quali recensioni si applica.',
    'trigger' => 'Attivatore',
    'trigger_new_review' => 'Nuova recensione su Google',
    'rating_is' => 'La valutazione è…',
    'rating_helper' => 'Non selezionare nulla per applicarla a qualsiasi valutazione.',
    'all_locations' => 'Tutte le sedi',
    'locations' => 'Sedi',
    'all_locations_helper' => 'Funge da regola generale: le automazioni limitate a sedi specifiche hanno la precedenza per quelle sedi.',
    'covered_by' => 'già in «:name» (:ratings)',
    'any_rating' => 'qualsiasi valutazione',
    'overlap_title' => 'Sovrapposizione con un\'altra automazione',
    'overlap_body' => 'Corrisponde anche alle stesse recensioni: :list. Ogni recensione è gestita da una sola automazione: le sedi specifiche prevalgono su «Tutte le sedi», altrimenti viene eseguita la più vecchia.',
    'respect_working_hours' => 'Rispetta gli orari di apertura',
    'respect_working_hours_helper' => 'Rispondi solo durante gli orari di apertura della sede.',
    'reply_to_previous' => 'Rispondi alle recensioni precedenti',
    'reply_to_previous_helper' => 'Gestisci anche le recensioni esistenti senza risposta (conteggiate nel tuo limite IA mensile).',
    'approve_before_posting' => 'Approva prima di pubblicare',
    'approve_before_posting_helper' => 'Disattivato = pubblicazione automatica su Google. Attivato = invio prima alle Approvazioni.',

    // Form — Timing section
    'timing_section' => 'Tempistica',
    'timing_section_desc' => 'Aggiungi un ritardo casuale (ed eventualmente orari di lavoro) così le risposte vengono pubblicate a ritmi umani e organici invece che all\'istante.',
    'reply_delay_min' => 'Ritardo minimo',
    'reply_delay_max' => 'Ritardo massimo',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'Le risposte vengono pubblicate dopo un ritardo casuale tra il minimo e il massimo, così sembrano organiche. Imposta entrambi a 0 per pubblicare immediatamente.',
    'reply_delay_max_error' => 'Il ritardo massimo deve essere maggiore o uguale al ritardo minimo.',
    'working_days' => 'Giorni lavorativi',
    'working_start' => 'Ora di inizio',
    'working_end' => 'Ora di fine',
    'day_mon' => 'Lun',
    'day_tue' => 'Mar',
    'day_wed' => 'Mer',
    'day_thu' => 'Gio',
    'day_fri' => 'Ven',
    'day_sat' => 'Sab',
    'day_sun' => 'Dom',

    // Form — Content section
    'content_section' => 'Contenuto',
    'content_section_desc' => 'Quale risposta inviare.',
    'content_ai_agent' => 'Agente IA',
    'content_default_message' => 'Messaggio predefinito',
    'ai_agent' => 'Agente IA',
    'default_message' => 'Messaggio predefinito',

    'col_name' => 'Nome',
    'col_enabled' => 'Attiva',
    'name' => 'Nome',
    'enabled' => 'Attiva',
];
