<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Ristorante dimostrativo',
    'demo_period' => 'Report sul rendimento · ultimi 30 giorni',
    'demo_five_star' => 'Quota di 5 stelle',
    'demo_summary_label' => 'Riepilogo esecutivo',
    'demo_summary' => 'Il Ristorante dimostrativo ha ricevuto 38 recensioni negli ultimi 30 giorni (+9 rispetto al periodo precedente), con una media di 4,60★. L’84% delle recensioni era positivo e il tasso di risposta ha raggiunto il 92%. I clienti hanno elogiato più volte la cordialità del team e la rapidità del servizio.',

    'location' => 'Sede',
    'business_multi' => ':name + altri :count',
    'compare' => 'Confronta',
    'compare_options' => [
        'none' => 'Non confrontare',
        'previous' => 'Periodo precedente',
        'custom' => 'Intervallo personalizzato…',
    ],
    'compare_from' => 'Confronta dal',
    'compare_to' => 'Confronta al',
    'report_language' => 'Lingua del report',

    'content_section' => 'Contenuto del report',
    'content_section_desc' => 'Scegli un modello, poi affina quali blocchi appaiono nel report.',
    'preset' => 'Modello',
    'blocks' => 'Blocchi',
    'competitors_block_hint' => 'Ancora nessun concorrente monitorato. Aggiungili prima in Schede > Concorrenti.',
    'ai_instructions' => 'Istruzioni per l’IA',
    'ai_instructions_help' => 'Indicazioni facoltative per il testo redatto dall’IA. Molto utili per i nomi del personale: elenca il tuo team e gli eventuali soprannomi affinché le menzioni siano attribuite alla persona giusta. Salvate una volta e applicate a tutti i report futuri, compresi quelli programmati.',
    'ai_instructions_placeholder' => 'Il nostro personale: Eva, Alette, Suleyman (scritto anche Suly), Lisa. Unisci i soprannomi al nome completo.',
    'ai_improve' => 'Migliora con l’IA',
    'ai_improve_empty' => 'Scrivi prima qualche nota, poi miglioratela.',
    'ai_improve_rate_limited' => 'Troppi tentativi, riprova più tardi.',
    'ai_improve_done' => 'Istruzioni migliorate',
    'ai_improve_failed' => 'Impossibile migliorare le istruzioni, riprova.',

    'schedule_report' => 'Invia in modo programmato',
    'schedule_heading' => 'Programma questo report',
    'schedule_desc' => 'La selezione attuale (periodo, sede, confronto, blocchi) verrà inviata via e-mail in formato PDF in modo ricorrente.',
    'schedule_submit' => 'Crea programmazione',
    'schedule_created' => 'Programmazione creata',
    'schedule_created_body' => 'Gestiscila in Report → Report programmati.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'Ti restano :left report IA su :cap questo mese',

    // Generate modal
    'generate_heading' => 'Generare il report con l’IA?',
    'generate_desc' => 'Genera il riepilogo esecutivo con l’IA per la selezione attuale.',
    'generate_desc_left' => 'Consuma 1 dei tuoi report IA mensili, te ne restano :left.',
    'generate_submit' => 'Genera',

    // Generate notifications
    'report_generated' => 'Report generato',
    'report_generated_body' => 'Il riepilogo IA è pronto, l’anteprima è stata aggiornata. Usa Scarica per salvare il PDF.',
    'limit_reached' => 'Limite mensile di report raggiunto',
    'limit_reached_body' => 'Viene mostrato un report semplice senza IA. Passa a un piano superiore per un limite mensile più alto.',

    // Blade view
    'generate_report' => 'Genera report',
    'generating' => 'Generazione…',
    'download_pdf' => 'Scarica PDF',
    'download_first_tooltip' => 'Genera prima il report',
    'building' => 'Creazione del report…',
    'preview_title' => 'Anteprima del report',
];
