<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhooks sind eine Pro-Funktion',
    'pro_only_body' => 'Erhalte einen signierten HTTP-POST, sobald eine Bewertung eingeht, eine Antwort veröffentlicht wird, ein Ziel erreicht oder eine Anomalie erkannt wird. Upgrade auf Pro, um Endpunkte hinzuzufügen.',
    'see_plans' => 'Tarife ansehen',

    'intro' => 'Wir senden bei jedem abonnierten Ereignis einen signierten JSON-POST an deinen Endpunkt, mit Wiederholungen. Prüfe den X-Webhook-Signature Header gegen dein Endpunkt-Secret.',

    'empty' => 'Noch keine Webhook-Endpunkte.',
    'col_url' => 'URL',
    'col_events' => 'Ereignisse',
    'col_active' => 'Aktiv',
    'col_last' => 'Zuletzt ausgelöst',

    'create' => 'Endpunkt hinzufügen',
    'create_heading' => 'Webhook-Endpunkt hinzufügen',
    'edit' => 'Bearbeiten',
    'delete' => 'Löschen',
    'saved' => 'Endpunkt gespeichert',
    'created' => 'Endpunkt hinzugefügt',
    'deleted' => 'Endpunkt gelöscht',

    'field_name' => 'Name (optional)',
    'field_url' => 'Endpunkt-URL',
    'field_events' => 'Ereignisse',
    'field_active' => 'Aktiv',

    'secret' => 'Secret',
    'secret_heading' => 'Signatur-Secret',
    'secret_desc' => 'Damit verifizierst du die Payload-Signatur.',
    'signature_hint' => 'Jede Anfrage ist signiert:',

    'deliveries' => 'Zustellungen',
    'deliveries_heading' => 'Letzte Zustellungen',
    'no_deliveries' => 'Noch keine Zustellungen.',
    'attempts' => 'Versuche',
    'resend' => 'Erneut senden',
    'resent' => 'Zustellung erneut eingereiht',
    'status_pending' => 'Ausstehend',
    'status_success' => 'Zugestellt',
    'status_failed' => 'Fehlgeschlagen',

    'event_review_created' => 'Neue Bewertung',
    'event_reply_published' => 'Antwort veröffentlicht',
    'event_goal_reached' => 'Ziel erreicht',
    'event_anomaly_detected' => 'Anomalie erkannt',
];
