<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Noch keine KI-Agenten',
    'empty_desc' => 'Erstelle einen KI-Agenten, der Antworten in deiner Markensprache entwirft und deine automatischen Antwort-Automatisierungen antreibt.',
    'empty_cta' => 'Neuer KI-Agent',

    // Table
    'col_native_lang' => 'Eigene Sprache',
    'col_default' => 'Standard',
    'col_updated' => 'Aktualisiert',
    'test_preview' => 'Testen & Vorschau',
    'test_heading' => 'Antwort testen',
    'close' => 'Schließen',
    'no_reviews_to_test' => 'Noch keine Bewertungen zum Testen, synchronisiere zuerst einige Bewertungen.',
    'generation_failed' => 'Generierung fehlgeschlagen: :error',
    'set_default' => 'Als Standard festlegen',

    // Form
    'section' => 'Dein KI-Agent',
    'section_desc' => 'Gib dem Agenten einen Namen und beschreibe, wie er antworten soll. Wird von Auto-Antwort-Automatisierungen und der Schaltfläche „Mit KI entwerfen“ genutzt.',
    'describe' => 'Beschreibe deinen Agenten',
    'describe_helper' => 'Die vollständigen Anweisungen / Persona, wie die Bewertung einzuordnen ist und wie zu antworten ist, Ton & Stil, Personalisierungsregeln usw.',
    'tone' => 'Tonfall',
    'reply_native' => 'In der Sprache der Bewertung antworten',
    'reply_native_helper' => 'Der Agent antwortet in derselben Sprache wie die Bewertung.',
    'default_agent' => 'Standard-Agent',
    'default_agent_helper' => 'Wird genutzt, wenn eine Automatisierung keinen Agenten angibt.',

    // Wissensdatenbank
    'knowledge' => 'Wissensdatenbank (optional)',
    'knowledge_helper' => 'Fakten zum Unternehmen, die der Agent in Antworten nutzen kann: Öffnungszeiten, Richtlinien, Raum-/Servicenamen, Angebote, FAQs. Bleibt faktisch, nichts wird darüber hinaus erfunden.',
    'knowledge_ph' => 'z. B. Geöffnet Mo–So 10:00–22:00. Räume: The Heist, Prison Break, Haunted Manor. Gruppen 2–6. Buchung auf example.com oder +43 ...',

    // Test-Panel
    'test_section' => 'An einer Bewertung testen',
    'test_section_desc' => 'Wähle eine echte Bewertung und generiere einen Entwurf mit den aktuellen (ungespeicherten) Einstellungen, dann anpassen.',
    'test_pick_review' => 'Bewertung',
    'test_pick_placeholder' => 'Synchronisierte Bewertung wählen…',
    'test_review_text' => 'Bewertung',
    'test_generate' => 'Entwurf generieren',
    'test_result' => 'Generierter Entwurf',
    'test_need_review' => 'Wähle zuerst eine Bewertung zum Testen.',

    // KI-Beschreibungsgenerator
    'generate_label' => 'Mit KI generieren',
    'generate_heading' => 'Beschreibung mit KI generieren',
    'generate_desc' => 'Gib deine Website und/oder ein paar Worte zum Unternehmen an, und die KI entwirft die Agenten-Anweisungen für dich. Das Ergebnis kannst du danach bearbeiten.',
    'generate_submit' => 'Generieren',
    'generate_url' => 'Website-URL',
    'generate_notes' => 'Etwas hinzufügen (optional)',
    'generate_notes_ph' => 'z. B. familiengeführtes italienisches Restaurant, Fokus auf freundlichen Service, im Sommer unsere Terrasse erwähnen',
    'generate_need_input' => 'Gib zuerst eine Website-URL oder eine kurze Beschreibung an.',
    'generate_rate_limited' => 'Zu viele Generierungen. Bitte warte kurz und versuche es erneut.',
    'generate_done' => 'Beschreibung generiert – prüfe und passe sie bei Bedarf an.',
    'generate_failed' => 'Beschreibung konnte nicht generiert werden. Bitte erneut versuchen oder manuell schreiben.',

    // Gemeinsame Antwort-Regeln (Workspace-weit, gelten für jeden Agenten)
    'shared_rules' => 'Gemeinsame Regeln',
    'shared_rules_heading' => 'Gemeinsame Antwort-Regeln',
    'shared_rules_desc' => 'Diese Regeln gelten zusätzlich zu jedem Agenten, in jeder KI-Antwort. Ideal für Stil-Korrekturen, die du nicht pro Agent wiederholen willst.',
    'shared_rules_placeholder' => "z. B.\nIn deutschen Antworten \"Raum\" oder \"Escape Room\" sagen, nie \"Room\" als deutsches Substantiv.\nNie Rabatte oder Erstattungen versprechen.\nAntworten ohne Namen unterschreiben.",
    'shared_rules_save' => 'Regeln speichern',
    'shared_rules_saved' => 'Gemeinsame Regeln gespeichert',

    // i18n label backfill
    'col_name' => 'Name',
    'col_tone' => 'Ton',
    'name' => 'Name',

    // i18n label backfill (batch 2)
    'preview_reply' => 'Antwort des Agenten',
    'anonymous' => 'Anonym',
];
