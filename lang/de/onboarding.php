<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Unternehmensdaten hinzufügen',
    'step_company_hint' => 'Land und Rechnungsdaten für Rechnungen und Berichte.',
    'step_plan_label' => 'Tarif wählen',
    'step_plan_hint' => 'Starte deine 14-tägige kostenlose Testphase, keine Karte nötig.',
    'step_location_label' => 'Ersten Standort verbinden',
    'step_location_hint' => 'Verknüpfe ein Google Business-Profil, um Bewertungen abzurufen.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Workspace einrichten',
    'wiz_plan_done' => '✓ Dein Tarif ist aktiv. Weiter zum nächsten Schritt.',
    'wiz_plan_pick' => 'Tarif wählen',
    'wiz_interval' => 'Abrechnungsintervall',
    'wiz_monthly' => 'Monatlich',
    'wiz_yearly' => 'Jährlich',
    'wiz_start_trial' => '14 Tage kostenlos testen',
    'wiz_trial_note' => 'Deine 14-tägige Testphase startet, sobald du fortfährst. Keine Karte nötig.',
    'wiz_go_checkout' => 'Weiter zur Kasse',
    'wiz_plan_required' => 'Wähle einen Tarif und schließe die Kasse ab, um fortzufahren.',
    'wiz_location_body' => 'Verknüpfe dein Google Business-Profil, damit wir deine Bewertungen abrufen können. Du wirst zu Google weitergeleitet, um den Zugriff zu erlauben, und wählst dann den Standort aus.',
    'wiz_connect_google' => 'Google Business-Profil verbinden',
    'wiz_skip_location' => 'Vorerst überspringen',
    'skipped_title' => 'Alles bereit',
    'skipped_body' => 'Du kannst dein Google Business-Profil jederzeit auf der Standorte-Seite verbinden.',
    'wiz_per_location' => 'pro Standort / Monat',
    'wiz_plan_desc_starter' => 'Bewertungs-Inbox, manuelle Antworten und Basis-Berichte.',
    'wiz_plan_desc_growth' => 'Plus KI-Auto-Antworten, geplante Berichte und Vergleiche.',
    'wiz_plan_desc_pro' => 'Alles, plus White Label, API, MCP und Kundenzugang.',

    // Onboarding overlay
    'welcome_title' => 'Willkommen, lass uns dein Konto einrichten',
    'welcome_subtitle' => 'Ein paar kurze Schritte und du bist startklar.',
    'continue_step' => 'Weiter: :label',
    'enter_app' => 'Zur App →',
    'sign_out' => 'Abmelden',

    // Pending-deletion overlay
    'deletion_title' => 'Dieser Workspace ist zur Löschung vorgemerkt',
    'deletion_body' => 'Alle Daten werden am <strong>:date</strong> endgültig gelöscht. Du kannst dies noch abbrechen und deinen Workspace behalten.',
    'cancel_deletion' => 'Löschung abbrechen',

    // Grace banner
    'grace_banner' => '⚠️ Wir konnten deine letzte Zahlung nicht verarbeiten. Dein Service bleibt bis <strong>:date</strong> aktiv, bitte',
    'update_your_billing' => 'aktualisiere deine Zahlungsdaten',

    // Paywall overlay
    'payment_problem_title' => 'Es gibt ein Problem mit deiner Zahlung',
    'needs_plan_title' => 'Wähle einen Tarif, um loszulegen',
    'payment_problem_body' => 'Dein Zugang ist pausiert, weil wir die Zahlung nicht verarbeiten konnten. Aktualisiere deine Zahlungsdaten, um fortzufahren.',
    'needs_plan_body' => 'Wähle einen Tarif, um Bewertungen, KI-Antworten und Berichte für deine Standorte zu aktivieren. 14-tägige kostenlose Testphase.',
    'update_billing' => 'Zahlungsdaten aktualisieren',
    'view_plans' => 'Tarife ansehen',

    // Connect-select-location page
    'connecting_location' => 'Standort wird verbunden…',
    'choose_location' => 'Wähle, welcher Google Business-Standort mit diesem Workspace verbunden werden soll.',
    'could_not_load' => 'Standorte konnten nicht geladen werden',
    'pending_expired_title' => 'Google-Sitzung abgelaufen',
    'pending_expired' => 'Die Google-Autorisierung ist nur kurz gültig und diese ist abgelaufen. Verbinde dich neu und wähle deine Standorte noch einmal, das dauert nur einen Moment.',
    'reconnect_google' => 'Google neu verbinden',
    'back' => 'Zurück',
    'no_locations_available' => 'Keine Standorte verfügbar',
    'no_locations_body' => 'Es wurden keine Google Business-Standorte zurückgegeben. Sie werden möglicherweise noch auf Googles Seite geladen, versuche es gleich erneut.',
    'connect_then_done' => 'Verbinde einen oder mehrere Standorte und klicke dann auf „Fertig“.',
    'done' => 'Fertig',
    'connected' => 'Verbunden',
    'connect' => 'Verbinden',
    'connecting' => 'Wird verbunden…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Unternehmensstandort auswählen',
    'connect_failed' => 'Standort konnte nicht verbunden werden',
    'connected_title' => 'Verbunden: :name',
    'connected_body' => 'Bewertungen werden im Hintergrund synchronisiert, sie erscheinen in Kürze auf der Standorte-Seite.',
    'location_fallback' => 'Standort',
    'trial_started_title' => 'Deine 14-tägige Testphase hat begonnen',
    'trial_started_body' => 'Voller Zugriff bis :date, keine Karte nötig. Viel Erfolg!',
];
