<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Niets om goed te keuren',
    'empty_desc' => 'Wanneer automatiseringen reacties opstellen die goedkeuring vereisen, verschijnen ze hier.',

    // Columns
    'col_location' => 'Locatie',
    'col_author' => 'Auteur',
    'col_rating' => 'Beoordeling',
    'col_review' => 'Review',
    'col_ai_reply' => 'AI-reactie',
    'col_status' => 'Status',
    'col_source' => 'Bron',
    'col_generated' => 'Gegenereerd',
    'source_ai' => 'AI',
    'source_template' => 'Sjabloon',

    // Statuses
    'status_pending' => 'In behandeling',
    'status_scheduled' => 'Ingepland',
    'status_published' => 'Gepubliceerd',
    'status_skipped' => 'Overgeslagen',
    'status_failed' => 'Mislukt',
    'status_indicator' => 'Status: :status',
    'scheduled_for' => 'Wordt geplaatst :time',

    // Actions
    'approve' => 'Goedkeuren en publiceren',
    'approve_publish' => 'Goedkeuren en publiceren',
    'edit_publish' => 'Bewerken en publiceren',
    'review_reply' => 'Nakijken en reageren',
    'reply' => 'Reageren',
    'reject' => 'Afwijzen',

    // Filters
    'filter_date' => 'Reviewdatum',
    'filter_from' => 'Vanaf :date',
    'filter_to' => 'Tot :date',

    // Notifications
    'reply_published' => 'Reactie gepubliceerd',

    'approve_selected' => 'Selectie goedkeuren en publiceren',
    'reject_selected' => 'Selectie afwijzen',
    'bulk_approve_confirm' => 'Alle geselecteerde reacties op Google publiceren? Ze worden in de wachtrij geplaatst en gaan de komende minuten automatisch de deur uit.',
    'bulk_reject_confirm' => 'Alle geselecteerde concepten afwijzen?',
    'bulk_queued' => ':count reacties in de wachtrij voor publicatie',
    'bulk_queued_body' => 'Ze worden de komende minuten automatisch gepubliceerd. Mislukkingen verschijnen onder het filter Mislukt, met de reden.',
    'bulk_rejected' => ':count concepten afgewezen',
    'publish_failed_title' => 'Publicatie mislukt',
    'publish_not_found' => 'Google geeft aan dat deze review niet meer bestaat. Mogelijk is deze door de auteur verwijderd, of is de locatie opnieuw verbonden onder een ander account. Het concept is als mislukt gemarkeerd.',
    'publish_error' => 'De reactie kon niet worden gepubliceerd. Het concept is als mislukt gemarkeerd: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google kon deze review of locatie niet vinden om op te reageren. Mogelijk is deze verwijderd, of zijn reacties niet beschikbaar voor deze locatie.',
    'error_rate_limited' => 'Google beperkt hoe snel reacties kunnen worden geplaatst. Het wordt automatisch opnieuw geprobeerd.',
    'error_unauthorized' => 'De Google-verbinding is niet gemachtigd om hier te reageren. Verbind het account opnieuw en probeer het nogmaals.',
    'error_generic' => 'De reactie kon niet worden geplaatst. Probeer het later opnieuw.',
    'draft_rejected' => 'Concept afgewezen',

    // Scheduled items
    'post_now' => 'Nu plaatsen',
    'post_now_confirm' => 'De reactie wordt meteen op Google gepubliceerd, zonder de ingeplande tijd af te wachten.',
    'post_now_queued' => 'Reactie in de wachtrij voor publicatie',
    'post_now_queued_body' => 'Deze gaat binnen de komende minuten de deur uit.',
    'cancel_scheduled' => 'Annuleren',
    'cancel_scheduled_confirm' => 'Deze ingeplande reactie annuleren? Ze wordt niet geplaatst.',
    'schedule_cancelled' => 'Ingeplande reactie geannuleerd',

    // List tabs
    'tab_pending' => 'Vereist goedkeuring',
    'tab_all' => 'Alle',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Selectie nu publiceren',
    'bulk_publish_now_confirm' => 'De geselecteerde reacties houden geen rekening met hun ingeplande tijd en gaan binnen de komende minuten de deur uit.',
    'cancel_scheduled_selected' => 'Inplanning annuleren',
];
