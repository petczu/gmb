<?php

declare(strict_types=1);

return [
    'nav' => 'Concurrenten',
    'title' => 'Concurrenten',
    'intro' => 'Volg bedrijven in de buurt en vergelijk hun Google-beoordeling en aantal reviews met je locaties. De cijfers worden elke dag automatisch vernieuwd.',

    'empty' => 'Nog geen concurrenten.',
    'empty_desc' => 'Voeg een concurrent toe om hun Google-beoordeling en reviewgroei te volgen.',

    'not_configured_title' => 'Concurrenten volgen is niet geconfigureerd',
    'not_configured_body' => 'Stel GOOGLE_PLACES_API_KEY in de serveromgeving in (een Google Places API-sleutel) om benchmarking van concurrenten in te schakelen.',

    'col_battle' => 'Concurrent',
    'col_name' => 'Concurrent',
    'col_rating' => 'Beoordeling',
    'col_reviews' => 'Reviews',
    'filter_location' => 'Locatie',
    'filter_city' => 'Stad',
    'col_vs' => 'Vs. jou',
    'col_location' => 'Jouw kant',
    'col_checked' => 'Bijgewerkt',

    'untitled_battle' => 'Naamloze vergelijking',
    'default_battle_name' => '{1} :location vs 1 concurrent|[2,*] :location vs :count concurrenten',
    'own_locations_count' => ':count locaties',
    'rating_weighted_hint' => 'Beoordeling gemiddeld over de concurrenten, gewogen naar hun aantal reviews.',

    'vs_ahead' => 'Je leidt met :delta ★',
    'vs_behind' => 'Zij leiden met :delta ★',
    'vs_tied' => 'Gelijk',
    'vs_unknown' => '—',

    'add' => 'Concurrent toevoegen',
    'add_heading' => 'Concurrent toevoegen',
    'edit' => 'Bewerken',
    'edit_heading' => 'Concurrenten bewerken',
    'field_name' => 'Naam van de vergelijking',
    'field_name_placeholder' => 'bijv. Hoofdstraat vs de buurt',
    'field_your_locations' => 'Jouw locaties',
    'field_your_locations_helper' => 'Kies een of meer van je locaties voor jouw kant.',
    'field_place' => 'Concurrent',
    'field_places' => 'Concurrenten',
    'field_places_helper' => 'Typ een bedrijfsnaam (en stad) om Google Places te doorzoeken.',
    'already_tracked' => 'Je volgt deze concurrent al.',
    'saved' => 'Concurrent opgeslagen',
    'some_failed' => ':count concurrent(en) kon(den) niet worden opgehaald en zijn overgeslagen.',

    'duplicate' => 'Dupliceren',
    'duplicate_heading' => 'Concurrent dupliceren',
    'copy_name' => ':name (kopie)',
    'remove' => 'Verwijderen',
    'removed' => 'Concurrent verwijderd',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Groep aanmaken',
    'group_heading' => 'Concurrenten groeperen',
    'group_need_two' => 'Kies minstens twee concurrenten om te groeperen.',
    'group_created' => 'Groep aangemaakt',
    'group_removed' => 'Groep verwijderd',
    'ungroup' => 'Uit groep verwijderen',
    'ungrouped' => 'Uit groep verwijderd',
    'field_group_name' => 'Groepsnaam',
    'field_group_competitors' => 'Concurrenten',
    'field_group_competitors_helper' => 'Deze concurrenten worden samengevoegd tot één lijn op de groeigrafiek, met hun reviews bij elkaar opgeteld.',
    'col_group' => 'Groep',

    'col_new_reviews' => 'Nieuwe reviews',
    'col_rating_trend' => 'Verandering beoordeling',
    'col_trend' => 'Trend',
    'you_delta' => 'jij: :delta',
    'trend_hint' => 'Nieuwe reviews in de geselecteerde periode.',
    'trend_collecting' => 'verzamelen…',
    'period_4w' => '4 weken',
    'period_12w' => '3 maanden',

    'collecting' => 'verzamelen…',
    'prev_delta' => 'vorige: :delta',
    'period_7d' => '7 dagen',
    'period_6m' => '6 maanden',
    'no_change' => 'geen verandering',
    'search_failed' => 'Zoeken naar concurrenten is tijdelijk niet beschikbaar',

    // Competitor detail modal
    'view' => 'Details bekijken',
    'close' => 'Sluiten',
    'you' => 'Jij',
    'reviews_count' => '{1} 1 review|[2,*] :count reviews',
    'no_distribution' => 'Verdeling van sterren nog niet beschikbaar (wordt bij de volgende vernieuwing bijgewerkt).',
];
