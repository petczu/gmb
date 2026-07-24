<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Noch keine Automatisierungen',
    'empty_desc' => 'Richte eine Automatisierung ein, um neue Bewertungen automatisch nach Bewertung und Standort zu beantworten.',
    'empty_cta' => 'Neue Automatisierung',

    // Table columns
    'col_rating' => 'Bewertung',
    'rating_any' => 'beliebig',
    'col_reply' => 'Antwort',
    'reply_ai' => 'KI: :agent',
    'reply_default' => 'Standardnachricht',
    'col_mode' => 'Modus',
    'mode_approval' => 'Freigabe',
    'mode_auto' => 'Automatisch veröffentlichen',
    'col_scope' => 'Geltungsbereich',
    'scope_all' => 'Alle Standorte',
    'scope_count' => ':count Standort(e)',

    // Run action
    'run_now' => 'Jetzt ausführen',
    'run_heading' => 'Diese Automatisierung jetzt ausführen',
    'run_desc' => 'Wende diese Automatisierung auf passende unbeantwortete Bewertungen an. Optional auf einen Bewertungszeitraum begrenzen; beide Felder leer lassen für alle.',
    'run_from' => 'Bewertungen ab',
    'run_until' => 'Bewertungen bis',
    'run_title' => '„:name“ ausgeführt',
    'run_queued_title' => '„:name" eingereiht',
    'run_queued_body' => 'Der Lauf passiert im Hintergrund. Neue Entwürfe landen in der Freigabe, automatisch veröffentlichte Antworten erscheinen in den nächsten Minuten an den Bewertungen.',
    'run_body' => ':generated generiert, :published veröffentlicht, :queued in Warteschlange, :skipped übersprungen.',

    // Form — Flow section
    'flow_section' => 'Ablauf',
    'flow_section_desc' => 'Wann die Automatisierung läuft und für welche Bewertungen sie gilt.',
    'trigger' => 'Auslöser',
    'trigger_new_review' => 'Neue Bewertung bei Google',
    'rating_is' => 'Bewertung ist…',
    'rating_helper' => 'Alle leer lassen, um für jede Bewertung zu gelten.',
    'all_locations' => 'Alle Standorte',
    'locations' => 'Standorte',
    'all_locations_helper' => 'Wirkt als Auffangregel: Automatisierungen mit konkreten Standorten haben für ihre Standorte Vorrang.',
    'covered_by' => 'bereits in „:name" (:ratings)',
    'any_rating' => 'jede Bewertung',
    'overlap_title' => 'Überschneidung mit einer anderen Automatisierung',
    'overlap_body' => 'Trifft auf dieselben Bewertungen zu: :list. Jede Bewertung wird von genau einer Automatisierung bearbeitet: konkrete Standorte gewinnen gegen „Alle Standorte", sonst läuft die ältere.',
    'respect_working_hours' => 'Öffnungszeiten beachten',
    'respect_working_hours_helper' => 'Nur während der Öffnungszeiten des Standorts antworten.',
    'reply_to_previous' => 'Auf frühere Bewertungen antworten',
    'reply_to_previous_helper' => 'Auch bestehende unbeantwortete Bewertungen behandeln (zählt zu deinem monatlichen KI-Kontingent).',
    'approve_before_posting' => 'Vor dem Posten freigeben',
    'approve_before_posting_helper' => 'Aus = automatisch bei Google veröffentlichen. An = zuerst zur Freigabe senden.',

    // Form — Timing section
    'timing_section' => 'Zeitplanung',
    'timing_section_desc' => 'Füge eine zufällige Verzögerung (und optional Öffnungszeiten) hinzu, damit Antworten zu natürlichen Zeiten statt sofort gepostet werden.',
    'reply_delay_min' => 'Minimale Verzögerung',
    'reply_delay_max' => 'Maximale Verzögerung',
    'minutes_suffix' => 'Min.',
    'reply_delay_helper' => 'Antworten werden nach einer zufälligen Verzögerung zwischen Minimum und Maximum gepostet, damit sie natürlich wirken. Setze beide auf 0, um sofort zu posten.',
    'reply_delay_max_error' => 'Die maximale Verzögerung muss größer oder gleich der minimalen Verzögerung sein.',
    'working_days' => 'Arbeitstage',
    'working_start' => 'Startzeit',
    'working_end' => 'Endzeit',
    'day_mon' => 'Mo',
    'day_tue' => 'Di',
    'day_wed' => 'Mi',
    'day_thu' => 'Do',
    'day_fri' => 'Fr',
    'day_sat' => 'Sa',
    'day_sun' => 'So',

    // Form — Content section
    'content_section' => 'Inhalt',
    'content_section_desc' => 'Welche Antwort gesendet wird.',
    'content_ai_agent' => 'KI-Agent',
    'content_default_message' => 'Standardnachricht',
    'ai_agent' => 'KI-Agent',
    'default_message' => 'Standardnachricht',

    // i18n label backfill
    'col_name' => 'Name',
    'col_enabled' => 'Aktiv',
    'name' => 'Name',
    'enabled' => 'Aktiv',
];
