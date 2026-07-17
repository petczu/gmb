<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Standort',
    'col_author' => 'Autor',
    'col_review' => 'Bewertung',
    'col_reply' => 'Antwort',
    'col_status' => 'Status',
    'col_replied_by' => 'Beantwortet von',
    'col_date' => 'Datum',
    'replied_ai' => 'KI',
    'replied_human' => 'Mensch',
    'replied_assistant' => 'Assistent',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— keine Antwort —',
    'status_replied' => 'Beantwortet',
    'status_pending' => 'Ausstehend',
    'status_failed' => 'Fehlgeschlagen',

    // Filters
    'review_date' => 'Bewertungsdatum',
    'filter_from' => 'Von :date',
    'filter_to' => 'Bis :date',
    'reply_status' => 'Antwortstatus',
    'review_text' => 'Bewertungstext',
    'with_text' => 'Mit Text',
    'rating_only' => 'Nur Sterne',
    'photos' => 'Fotos',
    'with_photos' => 'Mit Fotos',
    'without_photos' => 'Ohne Fotos',

    // Reply action
    'edit_reply' => 'Antwort bearbeiten',
    'save_reply' => 'Speichern',
    'reply' => 'Antworten',
    'reply_to_review' => 'Auf Bewertung antworten',
    'no_written_review' => 'Keine schriftliche Bewertung, nur Sterne.',
    'translated_by_google' => '🌐 Von Google übersetzt',
    'ai_agent' => 'KI-Agent',
    'default_agent' => 'Standard-Agent',
    'your_reply' => 'Deine Antwort',
    'generate_with_ai' => 'Mit KI generieren',
    'generate' => 'Generieren',
    'generating' => 'Antwort wird generiert…',
    'cancel' => 'Abbrechen',
    'add_emoji' => 'Emoji hinzufügen',
    'show_translation' => 'Übersetzung anzeigen (:language)',
    'translation_label' => 'Übersetzung (:language)',
    'translation_failed' => 'Übersetzung fehlgeschlagen',
    'hide_emoji' => 'Emoji ausblenden',
    'delete_reply' => 'Antwort löschen',
    'delete_reply_desc' => 'Dies entfernt die Antwort von Google. Die Bewertung selbst bleibt unberührt.',
    'delete_confirm' => 'Löschen',
    'submit_heading' => 'Antwort veröffentlichen?',
    'submit_desc' => 'Dies veröffentlicht deine Antwort öffentlich auf Google, sichtbar für alle, die die Bewertung sehen.',
    'submit_confirm' => 'Veröffentlichen',

    // AI cost hints
    'cost_generic' => 'Dies generiert eine Antwort mit KI.',
    'cost_all_used' => 'Du hast alle KI-Antworten diesen Monat aufgebraucht. Lade ein Paket auf, upgrade oder schreibe die Antwort manuell.',
    'cost_credit' => 'Dies verbraucht 1 Credit (noch :count übrig).',
    'cost_monthly' => 'Dies verbraucht 1 deiner monatlichen KI-Antworten, noch :count übrig.',

    // Notifications
    'reply_deleted' => 'Antwort gelöscht',
    'no_changes' => 'Keine Änderungen zum Speichern',
    'reply_published' => 'Antwort veröffentlicht',
    'ai_limit_reached' => 'KI-Limit erreicht',
    'ai_limit_body' => 'Du hast alle KI-Antworten diesen Monat aufgebraucht. Bearbeite manuell oder upgrade für ein höheres Limit.',
    'generation_failed' => 'Generierung fehlgeschlagen',
    'reply_generated' => 'Antwort generiert',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Alle',
    'tab_needs_approval' => 'Freigabe nötig',
    'tab_scheduled' => 'Geplant',
    'tab_published' => 'Veröffentlicht',
    'tab_failed' => 'Fehlgeschlagen',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} 1 Bewertung aus deiner E-Mail-Benachrichtigung|[2,*] :count Bewertungen aus deiner E-Mail-Benachrichtigung',
    'from_email_clear' => 'Alle Bewertungen anzeigen',
];
