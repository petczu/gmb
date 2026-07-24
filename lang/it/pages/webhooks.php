<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'I webhook sono una funzionalità Pro',
    'pro_only_body' => 'Ricevi un POST HTTP firmato nel momento in cui arriva una recensione, viene pubblicata una risposta, viene raggiunto un obiettivo o viene rilevata un’anomalia. Passa al piano Pro per aggiungere endpoint.',
    'see_plans' => 'Vedi i piani',

    'intro' => 'Inviamo in POST un payload JSON firmato al tuo endpoint a ogni evento sottoscritto, con nuovi tentativi in caso di errore. Verifica l’header X-Webhook-Signature con il secret del tuo endpoint.',

    'docs_link' => 'Documentazione dei webhook',
    'empty' => 'Ancora nessun endpoint webhook.',
    'col_url' => 'URL',
    'col_events' => 'Eventi',
    'col_active' => 'Attivo',
    'col_last' => 'Ultimo invio',

    'create' => 'Aggiungi endpoint',
    'create_heading' => 'Aggiungi endpoint webhook',
    'edit' => 'Modifica',
    'delete' => 'Elimina',
    'saved' => 'Endpoint salvato',
    'created' => 'Endpoint aggiunto',
    'deleted' => 'Endpoint eliminato',

    'field_name' => 'Nome (facoltativo)',
    'field_url' => 'URL dell’endpoint',
    'field_events' => 'Eventi',
    'field_active' => 'Attivo',

    'secret' => 'Secret',
    'secret_heading' => 'Secret di firma',
    'secret_desc' => 'Usalo per verificare la firma del payload.',
    'signature_hint' => 'Ogni richiesta è firmata:',

    'deliveries' => 'Invii',
    'deliveries_heading' => 'Invii recenti',
    'no_deliveries' => 'Ancora nessun invio.',
    'attempts' => 'tentativi',
    'resend' => 'Reinvia',
    'resent' => 'Invio rimesso in coda',
    'status_pending' => 'In attesa',
    'status_success' => 'Consegnato',
    'status_failed' => 'Non riuscito',

    'event_review_created' => 'Nuova recensione',
    'event_reply_published' => 'Risposta pubblicata',
    'event_goal_reached' => 'Obiettivo raggiunto',
    'event_anomaly_detected' => 'Anomalia rilevata',
];
