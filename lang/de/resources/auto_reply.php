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
    'status_scheduled' => 'Geplant',
    'status_published' => 'Veröffentlicht',
    'status_skipped' => 'Übersprungen',
    'status_failed' => 'Fehlgeschlagen',
    'status_indicator' => 'Status: :status',
    'scheduled_for' => 'Geht raus: :time',

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
    'bulk_approve_confirm' => 'Alle ausgewählten Antworten bei Google veröffentlichen? Sie werden eingereiht und gehen in den nächsten Minuten automatisch raus.',
    'bulk_reject_confirm' => 'Alle ausgewählten Entwürfe ablehnen?',
    'bulk_queued' => ':count Antworten zur Veröffentlichung eingereiht',
    'bulk_queued_body' => 'Sie werden in den nächsten Minuten automatisch veröffentlicht. Fehlschläge erscheinen mit Begründung im Filter „Fehlgeschlagen".',
    'bulk_rejected' => ':count Entwürfe abgelehnt',
    'publish_failed_title' => 'Veröffentlichen fehlgeschlagen',
    'publish_not_found' => 'Google meldet, dass diese Bewertung nicht mehr existiert. Sie wurde eventuell vom Autor gelöscht, oder der Standort wurde unter einem neuen Konto neu verbunden. Der Entwurf wurde als fehlgeschlagen markiert.',
    'publish_error' => 'Die Antwort konnte nicht veröffentlicht werden. Der Entwurf wurde als fehlgeschlagen markiert: :message',
    'draft_rejected' => 'Entwurf abgelehnt',

    // Scheduled items
    'post_now' => 'Jetzt veröffentlichen',
    'post_now_confirm' => 'Die Antwort wird sofort auf Google veröffentlicht und überspringt ihre geplante Zeit.',
    'post_now_queued' => 'Antwort zur Veröffentlichung eingereiht',
    'post_now_queued_body' => 'Sie geht in den nächsten Minuten raus.',
    'cancel_scheduled' => 'Abbrechen',
    'cancel_scheduled_confirm' => 'Diese geplante Antwort abbrechen? Sie wird nicht veröffentlicht.',
    'schedule_cancelled' => 'Geplante Antwort abgebrochen',

    // List tabs
    'tab_pending' => 'Wartet auf Freigabe',
    'tab_all' => 'Alle',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Auswahl jetzt veröffentlichen',
    'bulk_publish_now_confirm' => 'Die ausgewählten Antworten überspringen ihre geplante Zeit und gehen in den nächsten Minuten raus.',
    'cancel_scheduled_selected' => 'Planung abbrechen',
];
