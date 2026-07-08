<?php

declare(strict_types=1);

return [
    // Beispielbericht (noch keine Standorte verbunden)
    'demo_business' => 'Demo-Restaurant',
    'demo_period' => 'Leistungsbericht · letzte 30 Tage',
    'demo_five_star' => '5-Sterne-Anteil',
    'demo_summary_label' => 'Zusammenfassung',
    'demo_summary' => 'Das Demo-Restaurant erhielt in den letzten 30 Tagen 38 Bewertungen (+9 zum Vorzeitraum) mit durchschnittlich 4,60★. 84% der Bewertungen waren positiv, die Antwortquote lag bei 92%. Gäste lobten wiederholt das freundliche Team und den schnellen Service.',

    'location' => 'Standort',
    'compare' => 'Vergleichen',
    'compare_options' => [
        'none' => 'Nicht vergleichen',
        'previous' => 'Vorheriger Zeitraum',
        'custom' => 'Eigener Zeitraum…',
    ],
    'compare_from' => 'Vergleich von',
    'compare_to' => 'Vergleich bis',
    'report_language' => 'Berichtssprache',

    'content_section' => 'Berichtsinhalt',
    'content_section_desc' => 'Wähle eine Vorlage und passe dann an, welche Blöcke im Bericht erscheinen.',
    'preset' => 'Vorlage',
    'blocks' => 'Blöcke',
    'ai_instructions' => 'KI-Anweisungen',
    'ai_instructions_help' => 'Optionale Hinweise für den KI-Text. Besonders nützlich für Mitarbeiternamen: liste dein Team und Spitznamen auf, damit Erwähnungen der richtigen Person zugeordnet werden. Wird einmal gespeichert und gilt für alle künftigen Berichte, auch geplante.',
    'ai_instructions_placeholder' => 'Unser Team: Eva, Alette, Suleyman (auch Suly geschrieben), Lisa. Spitznamen dem vollen Namen zuordnen.',
    'ai_improve' => 'Mit KI verbessern',
    'ai_improve_empty' => 'Schreibe zuerst ein paar Notizen, dann verbessere sie.',
    'ai_improve_rate_limited' => 'Zu viele Versuche, bitte später erneut.',
    'ai_improve_done' => 'Anweisungen verbessert',
    'ai_improve_failed' => 'Anweisungen konnten nicht verbessert werden, bitte erneut versuchen.',

    'schedule_report' => 'Regelmäßig senden',
    'schedule_heading' => 'Diesen Bericht planen',
    'schedule_desc' => 'Die aktuelle Auswahl (Zeitraum, Standort, Vergleich, Blöcke) wird wiederkehrend als PDF per E-Mail versendet.',
    'schedule_submit' => 'Zeitplan erstellen',
    'schedule_created' => 'Zeitplan erstellt',
    'schedule_created_body' => 'Verwalte ihn unter Berichte → Geplante Berichte.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'Noch :left von :cap KI-Berichten diesen Monat',

    // Generate modal
    'generate_heading' => 'KI-Bericht erstellen?',
    'generate_desc' => 'Erstelle die KI-Zusammenfassung für die aktuelle Auswahl.',
    'generate_desc_left' => 'Dies verbraucht 1 deiner monatlichen KI-Berichte, noch :left übrig.',
    'generate_submit' => 'Erstellen',

    // Generate notifications
    'report_generated' => 'Bericht erstellt',
    'report_generated_body' => 'Die KI-Zusammenfassung ist fertig, die Vorschau wurde aktualisiert. Nutze „Herunterladen“, um das PDF zu speichern.',
    'limit_reached' => 'Monatliches Berichtslimit erreicht',
    'limit_reached_body' => 'Es wird ein einfacher Bericht ohne KI angezeigt. Upgrade für ein höheres monatliches Limit.',

    // Blade view
    'generate_report' => 'Bericht erstellen',
    'generating' => 'Wird erstellt…',
    'download_pdf' => 'PDF herunterladen',
    'download_first_tooltip' => 'Erstelle zuerst den Bericht',
    'building' => 'Bericht wird erstellt…',
    'preview_title' => 'Berichtsvorschau',
];
