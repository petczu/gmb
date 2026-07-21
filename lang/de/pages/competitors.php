<?php

declare(strict_types=1);

return [
    'nav' => 'Wettbewerber',
    'title' => 'Wettbewerber',
    'intro' => 'Beobachte Unternehmen in deiner Nähe und vergleiche deren Google-Bewertung und Anzahl der Rezensionen mit deinen Standorten. Die Zahlen werden täglich automatisch aktualisiert.',

    'empty' => 'Noch keine Wettbewerber.',
    'empty_desc' => 'Füge einen Wettbewerber hinzu, um seine Google-Bewertung und sein Wachstum zu verfolgen.',

    'not_configured_title' => 'Wettbewerber-Tracking ist nicht konfiguriert',
    'not_configured_body' => 'Setze GOOGLE_PLACES_API_KEY in der Server-Umgebung (ein Google-Places-API-Schlüssel), um den Vergleich zu aktivieren.',

    'col_battle' => 'Wettbewerber',
    'col_name' => 'Wettbewerber',
    'col_rating' => 'Bewertung',
    'col_reviews' => 'Rezensionen',
    'filter_location' => 'Standort',
    'filter_city' => 'Stadt',
    'col_vs' => 'Vergleich',
    'col_location' => 'Deine Seite',
    'col_checked' => 'Aktualisiert',

    'untitled_battle' => 'Vergleich ohne Namen',
    'default_battle_name' => '{1} :location vs. 1 Mitbewerber|[2,*] :location vs. :count Mitbewerber',
    'own_locations_count' => ':count Standorte',
    'rating_weighted_hint' => 'Bewertung über die Wettbewerber gemittelt, gewichtet nach ihrer Anzahl an Rezensionen.',

    'vs_ahead' => 'Du führst mit :delta ★',
    'vs_behind' => 'Sie führen mit :delta ★',
    'vs_tied' => 'Gleichstand',
    'vs_unknown' => '—',

    'add' => 'Wettbewerber hinzufügen',
    'add_heading' => 'Wettbewerber hinzufügen',
    'edit' => 'Bearbeiten',
    'edit_heading' => 'Wettbewerber bearbeiten',
    'field_name' => 'Name des Vergleichs',
    'field_name_placeholder' => 'z. B. Innenstadt vs. Umgebung',
    'field_your_locations' => 'Deine Standorte',
    'field_your_locations_helper' => 'Wähle einen oder mehrere deiner Standorte für deine Seite.',
    'field_place' => 'Wettbewerber',
    'field_places' => 'Wettbewerber',
    'field_places_helper' => 'Gib einen Unternehmensnamen (und Ort) ein, um Google Places zu durchsuchen.',
    'already_tracked' => 'Diesen Wettbewerber verfolgst du bereits.',
    'saved' => 'Wettbewerber gespeichert',
    'some_failed' => ':count Wettbewerber konnten nicht geladen werden und wurden übersprungen.',

    'duplicate' => 'Duplizieren',
    'duplicate_heading' => 'Wettbewerber duplizieren',
    'copy_name' => ':name (Kopie)',
    'remove' => 'Entfernen',
    'removed' => 'Wettbewerber entfernt',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Gruppe erstellen',
    'group_heading' => 'Wettbewerber gruppieren',
    'group_need_two' => 'Wähle mindestens zwei Wettbewerber für eine Gruppe.',
    'group_created' => 'Gruppe erstellt',
    'group_removed' => 'Gruppe entfernt',
    'ungroup' => 'Aus Gruppe entfernen',
    'ungrouped' => 'Aus Gruppe entfernt',
    'field_group_name' => 'Gruppenname',
    'field_group_competitors' => 'Wettbewerber',
    'field_group_competitors_helper' => 'Diese Wettbewerber werden im Wachstumsdiagramm zu einer Linie zusammengefasst, ihre Rezensionen werden summiert.',
    'col_group' => 'Gruppe',

    'col_new_reviews' => 'Neue Bewertungen',
    'col_rating_trend' => 'Rating-Änderung',
    'col_trend' => 'Trend',
    'you_delta' => 'du: :delta',
    'trend_hint' => 'Neue Bewertungen im gewählten Zeitraum.',
    'trend_collecting' => 'wird gesammelt…',
    'period_4w' => '4 Wochen',
    'period_12w' => '3 Monate',

    'collecting' => 'wird gesammelt…',
    'prev_delta' => 'zuvor: :delta',
    'period_7d' => '7 Tage',
    'period_6m' => '6 Monate',
    'no_change' => 'unverändert',
    'search_failed' => 'Die Konkurrenzsuche ist vorübergehend nicht verfügbar',

    // Competitor detail modal
    'view' => 'Details ansehen',
    'close' => 'Schließen',
    'you' => 'Du',
    'reviews_count' => '{1} 1 Bewertung|[2,*] :count Bewertungen',
    'no_distribution' => 'Sternverteilung noch nicht verfügbar (kommt mit der nächsten Aktualisierung).',
];
