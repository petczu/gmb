<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhooks zijn een Pro-functie',
    'pro_only_body' => 'Ontvang een ondertekende HTTP POST op het moment dat een review binnenkomt, een reactie wordt gepubliceerd, een doel wordt bereikt of een afwijking wordt gedetecteerd. Upgrade naar Pro om endpoints toe te voegen.',
    'see_plans' => 'Bekijk abonnementen',

    'intro' => 'We versturen bij elke geabonneerde gebeurtenis een ondertekende JSON-payload naar je endpoint, met herhaalpogingen. Verifieer de X-Webhook-Signature header aan de hand van je endpoint-secret.',

    'docs_link' => 'Webhook-documentatie',
    'empty' => 'Nog geen webhook-endpoints.',
    'col_url' => 'URL',
    'col_events' => 'Gebeurtenissen',
    'col_active' => 'Actief',
    'col_last' => 'Laatst geactiveerd',

    'create' => 'Endpoint toevoegen',
    'create_heading' => 'Webhook-endpoint toevoegen',
    'edit' => 'Bewerken',
    'delete' => 'Verwijderen',
    'saved' => 'Endpoint opgeslagen',
    'created' => 'Endpoint toegevoegd',
    'deleted' => 'Endpoint verwijderd',

    'field_name' => 'Naam (optioneel)',
    'field_url' => 'Endpoint-URL',
    'field_events' => 'Gebeurtenissen',
    'field_active' => 'Actief',

    'secret' => 'Secret',
    'secret_heading' => 'Ondertekeningssecret',
    'secret_desc' => 'Gebruik dit om de handtekening van de payload te verifiëren.',
    'signature_hint' => 'Elk verzoek wordt ondertekend:',

    'deliveries' => 'Bezorgingen',
    'deliveries_heading' => 'Recente bezorgingen',
    'no_deliveries' => 'Nog geen bezorgingen.',
    'attempts' => 'pogingen',
    'resend' => 'Opnieuw versturen',
    'resent' => 'Bezorging opnieuw in wachtrij geplaatst',
    'status_pending' => 'In behandeling',
    'status_success' => 'Bezorgd',
    'status_failed' => 'Mislukt',

    'event_review_created' => 'Nieuwe review',
    'event_reply_published' => 'Reactie gepubliceerd',
    'event_goal_reached' => 'Doel bereikt',
    'event_anomaly_detected' => 'Afwijking gedetecteerd',
];
