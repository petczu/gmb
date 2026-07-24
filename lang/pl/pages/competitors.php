<?php

declare(strict_types=1);

return [
    'nav' => 'Konkurenci',
    'title' => 'Konkurenci',
    'intro' => 'Śledź pobliskie firmy i porównuj ich ocenę Google oraz liczbę opinii z Twoimi lokalizacjami. Dane odświeżają się automatycznie każdego dnia.',

    'empty' => 'Brak konkurentów.',
    'empty_desc' => 'Dodaj konkurenta, aby śledzić jego ocenę Google i wzrost liczby opinii.',

    'not_configured_title' => 'Śledzenie konkurentów nie jest skonfigurowane',
    'not_configured_body' => 'Ustaw GOOGLE_PLACES_API_KEY w środowisku serwera (klucz API Google Places), aby włączyć porównywanie z konkurencją.',

    'col_battle' => 'Konkurent',
    'col_name' => 'Konkurent',
    'col_rating' => 'Ocena',
    'col_reviews' => 'Opinie',
    'filter_location' => 'Lokalizacja',
    'filter_city' => 'Miasto',
    'col_vs' => 'Względem Ciebie',
    'col_location' => 'Twoja strona',
    'col_checked' => 'Zaktualizowano',

    'untitled_battle' => 'Porównanie bez nazwy',
    'default_battle_name' => '{1} :location vs 1 konkurent|[2,*] :location vs :count konkurentów',
    'own_locations_count' => ':count lokalizacji',
    'rating_weighted_hint' => 'Ocena uśredniona wśród konkurentów, ważona ich liczbą opinii.',

    'vs_ahead' => 'Prowadzisz o :delta ★',
    'vs_behind' => 'Prowadzą o :delta ★',
    'vs_tied' => 'Remis',
    'vs_unknown' => '—',

    'add' => 'Dodaj konkurenta',
    'add_heading' => 'Dodaj konkurenta',
    'edit' => 'Edytuj',
    'edit_heading' => 'Edytuj konkurentów',
    'field_name' => 'Nazwa porównania',
    'field_name_placeholder' => 'np. Główna ulica kontra dzielnica',
    'field_your_locations' => 'Twoje lokalizacje',
    'field_your_locations_helper' => 'Wybierz jedną lub więcej swoich lokalizacji dla swojej strony.',
    'field_place' => 'Konkurent',
    'field_places' => 'Konkurenci',
    'field_places_helper' => 'Wpisz nazwę firmy (i miasto), aby wyszukać w Google Places.',
    'already_tracked' => 'Już śledzisz tego konkurenta.',
    'saved' => 'Konkurent zapisany',
    'some_failed' => 'Nie udało się pobrać :count konkurentów i zostali pominięci.',

    'duplicate' => 'Duplikuj',
    'duplicate_heading' => 'Duplikuj konkurenta',
    'copy_name' => ':name (kopia)',
    'remove' => 'Usuń',
    'removed' => 'Konkurent usunięty',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Utwórz grupę',
    'group_heading' => 'Grupuj konkurentów',
    'group_need_two' => 'Wybierz co najmniej dwóch konkurentów do zgrupowania.',
    'group_created' => 'Grupa utworzona',
    'group_removed' => 'Grupa usunięta',
    'ungroup' => 'Usuń z grupy',
    'ungrouped' => 'Usunięto z grupy',
    'field_group_name' => 'Nazwa grupy',
    'field_group_competitors' => 'Konkurenci',
    'field_group_competitors_helper' => 'Ci konkurenci łączą się w jedną linię na wykresie wzrostu, a ich opinie są sumowane.',
    'col_group' => 'Grupa',

    'col_new_reviews' => 'Nowe opinie',
    'col_rating_trend' => 'Zmiana oceny',
    'col_trend' => 'Trend',
    'you_delta' => 'Ty: :delta',
    'trend_hint' => 'Nowe opinie w wybranym okresie.',
    'trend_collecting' => 'zbieranie…',
    'period_4w' => '4 tygodnie',
    'period_12w' => '3 miesiące',

    'collecting' => 'zbieranie…',
    'prev_delta' => 'poprz.: :delta',
    'period_7d' => '7 dni',
    'period_6m' => '6 miesięcy',
    'no_change' => 'bez zmian',
    'search_failed' => 'Wyszukiwanie konkurentów jest tymczasowo niedostępne',

    // Competitor detail modal
    'view' => 'Zobacz szczegóły',
    'close' => 'Zamknij',
    'you' => 'Ty',
    'reviews_count' => '{1} 1 opinia|[2,*] :count opinii',
    'no_distribution' => 'Rozkład ocen w gwiazdkach nie jest jeszcze dostępny (aktualizacja przy następnym odświeżeniu).',
];
