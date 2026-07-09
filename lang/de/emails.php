<?php

declare(strict_types=1);

return [
    'greeting' => 'Hallo :name,',
    'signoff' => 'Danke',
    'team' => 'Dein Repunio-Team',

    'drip_competitors' => [
        'subject' => 'Weißt du, wie es dem Geschäft nebenan geht?',
        'intro' => 'Deine eigenen Bewertungen sind unter Kontrolle. Die nächste Frage, die sich jeder Inhaber stellt: liege ich vor der Konkurrenz oder falle ich zurück? Repunio beobachtet das für dich, mit täglichen Ratings und Bewertungszahlen für jedes Unternehmen auf Google.',
        'tip' => 'Dauert zwei Minuten: öffne Wettbewerber, such den Namen, füge ihn hinzu. Ab dann siehst du, wer davonzieht, um wie viel, und ob dein Rating mithält.',
        'cta' => 'Ersten Wettbewerber hinzufügen',
    ],

    'location_connected' => [
        'subject' => ':location ist verbunden',
        'intro' => 'Dein Standort :location ist jetzt verbunden. Wir importieren gerade seine Bewertungen von Google; je nach Menge kann das ein paar Minuten dauern.',
        'note' => 'Du bekommst eine weitere E-Mail, sobald die Bewertungen da sind.',
        'cta' => 'Standorte ansehen',
    ],

    'location_synced' => [
        'subject' => 'Deine Bewertungen sind da',
        'intro' => 'Der erste Import ist abgeschlossen. Das ist angekommen:',
        'note' => 'Ab jetzt kommen neue Bewertungen automatisch an, und deine Automatisierungsregeln greifen.',
        'cta' => 'Bewertungs-Posteingang öffnen',
    ],

    'drip_connect' => [
        'subject' => 'Dein Konto ist bereit. Ein Schritt fehlt noch',
        'intro' => 'Dein Repunio-Workspace ist eingerichtet, aber noch leer: Bewertungen, Ratings und Berichte kommen aus deinem Google Business-Profil, und es ist noch keines verbunden.',
        'tip' => 'Dauert etwa zwei Minuten: öffne Standorte, klicke auf Verbinden, melde dich bei Google an und wähle dein Unternehmen. Deine Bewertungen laufen sofort ein.',
        'cta' => 'Standort verbinden',
    ],

    'signup_code' => [
        'subject' => ':code ist dein Repunio-Anmeldecode',
        'intro' => 'Gib diesen Code auf der Registrierungsseite ein, um deine E-Mail-Adresse zu bestätigen:',
        'note' => 'Der Code ist :minutes Minuten gültig. Falls du ihn nicht angefordert hast, kannst du diese E-Mail einfach ignorieren.',
    ],

    'beta_received' => [
        'subject' => 'Danke! Deine Zugangsanfrage ist eingegangen',
        'intro' => 'Danke für deine Registrierung! Repunio ist derzeit in einer privaten Beta und wir schalten neue Konten in kleinen Wellen frei.',
        'note' => 'Wir melden uns per E-Mail, sobald dein Zugang bereit ist. Du musst im Moment nichts weiter tun.',
    ],

    'beta_approved' => [
        'subject' => 'Dein Repunio-Zugang ist bereit',
        'intro' => 'Gute Neuigkeiten: dein Konto wurde freigeschaltet. Du kannst dich jetzt anmelden und alles einrichten.',
        'note' => 'Verbinde zuerst dein Google-Unternehmensprofil, deine Bewertungen werden innerhalb weniger Minuten importiert.',
        'cta' => 'Repunio öffnen',
    ],

    'welcome' => [
        'subject' => 'Willkommen bei Repunio',
        'intro' => 'Dein Konto ist bereit. Mit Repunio sammelst du Google-Bewertungen, beantwortest sie und erstellst Berichte, alles an einem Ort.',
        'next' => 'Als Nächstes: verbinde deinen ersten Standort und wähle einen Tarif, um deine 14-tägige Testphase zu starten.',
        'cta' => 'Repunio öffnen',
    ],

    'trial_ending' => [
        'subject' => 'Deine Testphase endet in :days Tagen',
        'intro' => 'Deine kostenlose Repunio-Testphase endet am :date. Hinterlege jetzt eine Zahlungsmethode, damit nichts stoppt: Bewertungen werden weiter synchronisiert und KI-Antworten funktionieren weiter.',
        'note' => 'Es wird erst nach Ablauf der Testphase abgebucht, und du kannst jederzeit kündigen.',
        'cta' => 'Zahlungsmethode hinzufügen',
    ],

    'payment_succeeded' => [
        'subject' => 'Zahlung erhalten',
        'intro' => 'Wir haben deine Zahlung über :amount erhalten. Dein Repunio-Abo ist aktiv.',
        'cta' => 'Zur Abrechnung',
    ],

    'payment_failed' => [
        'subject' => 'Zahlung fehlgeschlagen, Aktion erforderlich',
        'intro' => 'Wir konnten deine letzte Zahlung nicht verarbeiten. Dein Konto funktioniert noch :days Tage, bitte aktualisiere deine Zahlungsdaten, um eine Unterbrechung zu vermeiden.',
        'cta' => 'Zahlungsdaten aktualisieren',
    ],

    'subscription_canceled' => [
        'subject' => 'Dein Abo wird gekündigt',
        'intro' => 'Dein Repunio-Abo wurde gekündigt. Du behältst vollen Zugriff bis zum :date, danach wird es nicht verlängert.',
        'note' => 'Anders überlegt? Du kannst bis dahin jederzeit fortsetzen, ohne Abbuchung.',
        'cta' => 'Abo fortsetzen',
    ],

    'subscription_resumed' => [
        'subject' => 'Dein Abo ist wieder aktiv',
        'intro' => 'Dein Repunio-Abo wurde fortgesetzt und verlängert sich wie gewohnt weiter. Es ist nichts weiter zu tun.',
        'cta' => 'Abrechnung ansehen',
    ],

    'ai_limit' => [
        'subject' => 'Du hast diesen Monat alle KI-Antworten aufgebraucht',
        'intro' => 'Du hast dein monatliches Limit für KI-Antworten im Tarif :plan erreicht. Erweitere den Tarif für ein höheres Limit oder antworte bis zum nächsten Monat manuell.',
        'cta' => 'Tarife ansehen',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Automatische KI-Aufladung fehlgeschlagen',
        'intro' => 'Wir wollten deine KI-Antworten automatisch aufladen, aber die Zahlung ist fehlgeschlagen. Bitte aktualisiere deine Karte, damit die automatische Aufladung weiter funktioniert.',
        'cta' => 'Abrechnung aktualisieren',
    ],

    'new_reviews' => [
        'subject' => ':count neue Bewertung(en) für dein Unternehmen',
        'intro' => 'Du hast :count neue Bewertung(en) für :location.',
        'col_author' => 'Autor',
        'col_rating' => 'Sterne',
        'col_location' => 'Standort',
        'col_review' => 'Bewertung',
        'cta' => 'Bewertungen ansehen',
    ],

    'account_disconnected' => [
        'subject' => 'Aktion erforderlich: Deine Google-Verbindung funktioniert nicht mehr',
        'intro' => 'Die Google-Verbindung für ":account" funktioniert nicht mehr, daher werden deine Bewertungen nicht mehr synchronisiert.',
        'detail' => 'Verbinde das Konto erneut, um die Synchronisierung von Bewertungen und das Veröffentlichen von Antworten fortzusetzen.',
        'cta' => 'Erneut verbinden',
    ],

    'sync_restored' => [
        'subject' => 'Deine Google-Verbindung ist wiederhergestellt',
        'intro' => 'Gute Nachrichten: Die Verbindung für ":account" ist wiederhergestellt und die Synchronisierung läuft wieder. Deine Bewertungen sind wieder aktuell.',
        'cta' => 'Repunio öffnen',
    ],

    'negative_review' => [
        'subject' => ':rating★-Bewertung erfordert deine Aufmerksamkeit',
        'intro' => 'Eine neue Bewertung für :business erfordert deine Aufmerksamkeit.',
        'col_author' => 'Autor',
        'col_rating' => 'Bewertung',
        'col_review' => 'Bewertung',
        'cta' => 'Jetzt antworten',
    ],

    'reply_failed' => [
        'subject' => 'Deine Antwort konnte nicht veröffentlicht werden',
        'intro' => 'Wir haben versucht, eine Antwort auf eine Bewertung für :business zu veröffentlichen, aber es ist fehlgeschlagen.',
        'col_author' => 'Autor',
        'col_review' => 'Bewertung',
        'detail' => 'Bitte versuche, die Antwort erneut über die App zu veröffentlichen.',
        'cta' => 'Bewertungen öffnen',
    ],

    'approvals_pending' => [
        'subject' => ':count Antwort(en) warten auf Freigabe',
        'intro' => 'Du hast :count Antwort(en), die auf deine Freigabe warten. Prüfe und genehmige sie, damit sie veröffentlicht werden können.',
        'cta' => 'Freigaben prüfen',
    ],

    'review_goal' => [
        'subject_mid' => 'Dein Bewertungsziel: So läuft der Monat',
        'subject_recap' => 'Bewertungs-Rückblick für :month',
        'intro_mid_ahead' => 'Starkes Tempo! Du hast diesen Monat :actual neue Bewertungen, mehr als die bis jetzt erwarteten :expected (Ziel :goal). Weiter so.',
        'intro_mid_on_track' => 'Du bist im Plan: :actual neue Bewertungen diesen Monat, genau um die bis jetzt erwarteten :expected (Ziel :goal).',
        'intro_mid_behind' => 'Ein kleiner Schubs: du hast diesen Monat :actual neue Bewertungen, unter den bis jetzt erwarteten :expected (Ziel :goal). Etwas Nachdruck hilft.',
        'intro_recap' => 'So endete :month: :actual neue Bewertungen bei einem Ziel von :goal.',
        'col_location' => 'Standort',
        'col_goal' => 'Ziel',
        'col_so_far' => 'Bisher',
        'col_projected' => 'Hochrechnung',
        'col_pace' => 'Tempo',
        'col_got' => 'Erhalten',
        'col_vs_goal' => 'vs Ziel',
        'col_vs_prev' => 'vs Vormonat',
        'status_ahead' => 'Voraus',
        'status_on_track' => 'Im Plan',
        'status_behind' => 'Zurück',
        'cta' => 'Bewertungen ansehen',
    ],

    'coaching' => [
        'subject' => 'Dein Bewertungsziel: bleiben wir dran',
        'intro_almost' => 'Fast geschafft! Nur noch :remaining bis zu deinem Ziel von :goal diesen Monat. Das schaffst du!',
        'intro_behind' => 'Du bist bei :actual von :goal diesen Monat. Ein gleichmäßiger Schub diese Woche bringt dich zurück in den Plan. Hier ein paar Ideen.',
        'intro_on_track' => 'Gut gemacht! :actual von :goal und genau im Plan. Ein paar Anfragen diese Woche halten das Momentum.',
        'intro_ahead' => 'Tolles Tempo! :actual von :goal, dem Plan voraus. Halte es mit diesen Ideen am Laufen.',
        'steady' => 'Eine Sache: Verteile die Anfragen über die Tage. Eine plötzliche Flut von Bewertungen wirkt für Google verdächtig und kann gefiltert werden. Gleichmäßig gewinnt.',
        'cta' => 'Bewertungen öffnen',
    ],

    'goal_reached' => [
        'subject' => 'Ziel erreicht! :goal Bewertungen diesen Monat! 🎉',
        'intro' => 'Glückwunsch! Du hast dein Ziel von :goal neuen Bewertungen diesen Monat erreicht! Das ist echtes Momentum für deine Reputation.',
        'note' => 'Bleib mit gleichmäßigem Tempo dran, dann wird der nächste Monat noch leichter.',
        'cta' => 'Bewertungen öffnen',
    ],

    'review_anomaly' => [
        'subject' => 'Achtung: :count Sache(n) bei deinen Bewertungen prüfen',
        'intro' => 'Uns ist etwas aufgefallen, das einen Blick wert ist:',
        'stalled' => 'seit :days Tagen keine neuen Bewertungen, obwohl normalerweise aktiv.',
        'negative_streak' => ':count Bewertungen mit wenig Sternen innerhalb von 3 Tagen. Antworte schnell, um den Schaden zu begrenzen.',
        'spike' => 'ungewöhnlicher Anstieg: :recent Bewertungen in 7 Tagen (normalerweise etwa :baseline pro Woche). Gute Nachricht, oder auf Spam prüfen.',
        'rating_drop' => 'Bewertung sinkt: :recent★ zuletzt vs :prior★ davor.',
        'cta' => 'Bewertungen öffnen',
    ],

    'invite' => [
        'subject' => 'Du wurdest eingeladen, :workspace bei Repunio beizutreten',
        'greeting' => 'Hallo,',
        'intro' => ':inviter hat dich eingeladen, :workspace bei Repunio als :role beizutreten.',
        'note' => 'Diese Einladung läuft in 14 Tagen ab. Wenn du sie nicht erwartet hast, kannst du diese E-Mail ignorieren.',
        'cta' => 'Einladung annehmen',
    ],

    // Onboarding-Serie (Produkt-Einführung)
    'drip_inbox' => [
        'subject' => 'Jede Bewertung, ein Posteingang',
        'intro' => 'Alle Bewertungen deiner Standorte landen in einem Posteingang. Filtere nach Sternen, Standort oder unbeantwortet und antworte in zwei Klicks.',
        'tip' => 'Probiere es gleich aus: öffne eine Bewertung und klicke auf Mit KI generieren. Du bekommst einen fertigen Entwurf in deinem Ton, den du vor dem Veröffentlichen anpassen kannst.',
        'cta' => 'Bewertungen öffnen',
    ],
    'drip_automation' => [
        'subject' => 'Antworten auf Autopilot',
        'intro' => 'Erstelle einen KI-Agenten, der dein Unternehmen und deinen Ton kennt, und lass Auto-Antwort-Regeln Routine-Bewertungen für dich beantworten.',
        'tip' => 'Noch nicht bereit für vollen Autopilot? Nutze die Freigabe-Warteschlange: die KI entwirft, du gibst mit einem Klick frei.',
        'cta' => 'Automatisierungen einrichten',
    ],
    'drip_growth' => [
        'subject' => 'Diesen Monat mehr Bewertungen sammeln',
        'intro' => 'Setze ein monatliches Bewertungsziel pro Standort. Wir verfolgen das Tempo, feiern Meilensteine und warnen bei Anomalien.',
        'tip' => 'Erstelle deine Bewertungsseite: ein kurzer Link und QR-Code, die zufriedene Kunden direkt zum Google- oder TripAdvisor-Formular führen.',
        'cta' => 'Bewertungsseite erstellen',
    ],
    'drip_reports' => [
        'subject' => 'Berichte, die deine Kunden wirklich lesen',
        'intro' => 'Baue einen Performance-Bericht aus Blöcken: KPIs, KI-Zusammenfassung, Mitarbeiter-Erwähnungen, Themen. Als PDF oder per Link teilen.',
        'tip' => 'Einmal einrichten, monatlich senden: plane den Bericht und er landet automatisch im Postfach, auf Deutsch oder Englisch.',
        'cta' => 'Bericht erstellen',
    ],
    'drip_team' => [
        'subject' => 'Hol dein Team an Bord',
        'intro' => 'Lade Teammitglieder mit Rollen ein oder füge Gäste hinzu, die nur Benachrichtigungen und Berichte erhalten, ganz ohne Login.',
        'tip' => 'Lege in den Einstellungen fest, wer welche E-Mail bekommt, und leite Bewertungs-Alerts an die richtigen Personen.',
        'cta' => 'Team einladen',
    ],
    'drip_member' => [
        'subject' => 'So findest du dich in Repunio zurecht',
        'intro' => 'Du wurdest zu einem Workspace hinzugefügt. Der Bewertungs-Posteingang ist dein Arbeitsplatz: filtern, antworten, fertig.',
        'tip' => 'Stelle Sprache für Oberfläche und E-Mails in deinem Profil ein, damit alles so ankommt, wie du es magst.',
        'cta' => 'Repunio öffnen',
    ],
    'drip_unsubscribe' => 'Zu viele Tipps? :link',
    'drip_unsubscribe_link' => 'Diese E-Mails abbestellen',

    'unsubscribed_title' => 'Du bist abgemeldet',
    'unsubscribed_body' => 'Du erhältst keine Produkt-Tipps und Onboarding-E-Mails mehr. Wichtige Konto- und Rechnungs-E-Mails kommen weiterhin an. Umentschieden? Aktiviere sie wieder in :link.',
    'unsubscribed_profile' => 'deinem Profil',
];
