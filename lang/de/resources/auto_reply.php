<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nichts zu genehmigen',
    'empty_desc' => 'Wenn Automatisierungen Antworten entwerfen, die eine Genehmigung benötigen, erscheinen sie hier.',

    // Columns
    'col_location' => 'Standort',
    'col_author' => 'Autor',
    'col_rating' => 'Bewertung',
    'col_review' => 'Bewertung',
    'col_ai_reply' => 'KI-Antwort',
    'col_status' => 'Status',
    'col_source' => 'Quelle',
    'col_generated' => 'Erstellt',
    'source_ai' => 'KI',
    'source_template' => 'Vorlage',

    // Statuses
    'status_pending' => 'Ausstehend',
    'status_published' => 'Veröffentlicht',
    'status_skipped' => 'Übersprungen',
    'status_failed' => 'Fehlgeschlagen',
    'status_indicator' => 'Status: :status',

    // Actions
    'approve' => 'Freigeben & veröffentlichen',
    'approve_publish' => 'Freigeben & veröffentlichen',
    'edit_publish' => 'Bearbeiten & veröffentlichen',
    'review_reply' => 'Ansehen & antworten',
    'reply' => 'Antwort',
    'reject' => 'Ablehnen',

    // Filters
    'filter_date' => 'Bewertungsdatum',
    'filter_from' => 'Ab :date',
    'filter_to' => 'Bis :date',

    // Notifications
    'reply_published' => 'Antwort veröffentlicht',

    'approve_selected' => 'Auswahl freigeben & veröffentlichen',
    'reject_selected' => 'Auswahl ablehnen',
    'bulk_approve_confirm' => 'Alle ausgewählten Antworten bei Google veröffentlichen?',
    'bulk_reject_confirm' => 'Alle ausgewählten Entwürfe ablehnen?',
    'bulk_approved' => ':count Antworten veröffentlicht',
    'bulk_rejected' => ':count Entwürfe abgelehnt',
    'publish_failed_title' => 'Veröffentlichen fehlgeschlagen',
    'publish_not_found' => 'Google meldet, dass diese Bewertung nicht mehr existiert. Sie wurde eventuell vom Autor gelöscht, oder der Standort wurde unter einem neuen Konto neu verbunden. Der Entwurf wurde als fehlgeschlagen markiert.',
    'publish_error' => 'Die Antwort konnte nicht veröffentlicht werden. Der Entwurf wurde als fehlgeschlagen markiert: :message',
    'bulk_result' => ':published veröffentlicht, :failed fehlgeschlagen. Fehlgeschlagene Entwürfe stehen mit Fehlermeldung im Filter „Fehlgeschlagen".',
    'draft_rejected' => 'Entwurf abgelehnt',
];
