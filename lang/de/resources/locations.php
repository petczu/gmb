<?php

declare(strict_types=1);

return [
    'empty_heading' => 'Keine Standorte verbunden',
    'empty_desc' => 'Verbinde einen Google-Unternehmensprofil-Standort, um seine Bewertungen abzurufen.',
    'empty_cta' => 'Standort verbinden',

    'col_location' => 'Standort',
    'col_reviews' => 'Bewertungen',
    'col_last_synced' => 'Zuletzt synchronisiert',
    'syncing' => 'Bewertungen werden importiert…',
    'syncing_hint' => 'Der erste Import von Google kann ein paar Minuten dauern. Du bekommst eine E-Mail, sobald er fertig ist.',
    'sync_failed' => 'Sync fehlgeschlagen',

    'disconnect' => 'Trennen',
    'disconnect_heading' => 'Standort trennen',
    'disconnect_desc' => 'Diesen Standort nicht mehr verfolgen und seine synchronisierten Bewertungen aus diesem Workspace entfernen.',
    'disconnected' => 'Standort getrennt',

    // ListLocations header actions
    'add_location' => 'Standort hinzufügen',
    'add_demo_data' => 'Demodaten hinzufügen',
    'demo_added' => 'Demodaten hinzugefügt',
    'edit_info' => 'Info bearbeiten',

    // Massenbearbeitung der Öffnungszeiten
    'bulk_hours' => 'Zeiten bearbeiten',

    // Standortgruppen (Filter + Organisation)
    'group' => 'Gruppe',
    'groups' => 'Gruppen',
    'create_group' => 'Gruppe erstellen',
    'group_heading' => 'Standortgruppe erstellen',
    'group_create' => 'Gruppe erstellen',
    'group_name' => 'Gruppenname',
    'group_locations' => 'Standorte',
    'group_locations_helper' => 'Wähle mindestens zwei Standorte. Jeder Standort gehört zu genau einer Gruppe.',
    'group_need_two' => 'Wähle einen Namen und mindestens zwei Standorte.',
    'group_created' => 'Gruppe erstellt',
    'ungroup' => 'Aus Gruppe entfernen',
    'ungrouped' => 'Aus Gruppe entfernt',
    'bulk_hours_heading' => 'Zeiten für ausgewählte Standorte bearbeiten',
    'bulk_hours_desc' => 'Die unten aktivierten Bereiche werden auf jedes ausgewählte Google-Profil übertragen. Ein übertragener Bereich ersetzt die dort hinterlegten Zeiten, deaktivierte Bereiche bleiben unberührt.',
    'bulk_hours_submit' => 'Auf Auswahl anwenden',
    'bulk_hours_apply' => 'Diesen Bereich anwenden',
    'bulk_hours_regular' => 'Öffnungszeiten',
    'bulk_hours_regular_desc' => 'Der Wochenplan. Tage ohne Eintrag erscheinen auf Google als geschlossen.',
    'bulk_hours_special' => 'Spezielle Öffnungszeiten',
    'bulk_hours_special_desc' => 'Ausnahmen für bestimmte Tage: Feiertage, verkürzte Tage oder zusätzliche Schließtage.',
    'bulk_hours_add_row' => 'Tag hinzufügen',
    'bulk_hours_holidays' => 'Aus deinen externen Kalendern übernehmen',
    'bulk_hours_holidays_help' => 'Wähle Termine aus den auf der Posts-Seite verbundenen Kalendern, jeder wird zum Schließtag.',
    'bulk_hours_locations' => 'Standorte',
    'bulk_hours_apply_on' => 'Anwenden ab Datum',
    'bulk_hours_apply_on_help' => 'Leer lassen, um sofort anzuwenden. Mit Datum wird die Änderung am frühen Morgen dieses Tages (UTC) an Google übertragen.',
    'bulk_hours_scheduled' => 'Zeiten-Update geplant für :date',
    'bulk_hours_scheduled_body' => '{1} Es wird automatisch auf 1 Standort angewendet.|[2,*] Es wird automatisch auf :count Standorte angewendet.',
    'bulk_hours_nothing' => 'Nichts anzuwenden: aktiviere mindestens einen Bereich und füge Einträge hinzu.',
    'bulk_hours_unmatched' => 'keinem Google-Eintrag zugeordnet',
    'bulk_hours_done' => '{1} Zeiten bei 1 Standort aktualisiert.|[2,*] Zeiten bei :count Standorten aktualisiert.',
    'bulk_hours_partial' => '{0} Zeiten konnten nicht aktualisiert werden.|{1} Zeiten bei 1 Standort aktualisiert, mit Problemen:|[2,*] Zeiten bei :count Standorten aktualisiert, mit Problemen:',
];
