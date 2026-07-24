<?php

declare(strict_types=1);

return [
    'nav' => 'Informacje o firmie',
    'title' => 'Informacje o firmie',

    'not_configured_title' => 'Zarządzanie wizytówkami nie jest skonfigurowane',
    'not_configured_body' => 'Ustaw ZERNIO_API_KEY w środowisku serwera, aby edytować Profile Firmy Google.',

    'pick_location' => 'Lokalizacja',
    'status_live' => 'Aktywna w Google',
    'status_suspended' => 'Zawieszona przez Google',
    'status_disabled' => 'Wyłączona',
    'status_unverified' => 'Niezweryfikowana',

    'section_basics' => 'Profil',
    'field_logo' => 'Logo lokalizacji',
    'field_logo_helper' => 'Wyświetlane w podglądzie posta Google. Gdy puste, używane jest logo obszaru roboczego.',
    'field_description' => 'Opis firmy',
    'field_description_helper' => 'Wyświetlany na Twoim Profilu Firmy Google. Do 750 znaków. Formularz wczytuje aktualne wartości z Google.',
    'field_phone' => 'Numer telefonu',
    'field_website' => 'Strona internetowa',

    'section_hours' => 'Godziny otwarcia',
    'section_hours_desc' => 'Jeden wiersz na przedział czasowy. Dodaj dwa wiersze dla tego samego dnia, aby ustawić godziny z przerwą (np. przerwa na lunch).',
    'add_hours' => 'Dodaj przedział czasowy',
    'field_day' => 'Dzień',
    'field_open' => 'Otwarcie',
    'field_close' => 'Zamknięcie',

    'day_monday' => 'Poniedziałek',
    'day_tuesday' => 'Wtorek',
    'day_wednesday' => 'Środa',
    'day_thursday' => 'Czwartek',
    'day_friday' => 'Piątek',
    'day_saturday' => 'Sobota',
    'day_sunday' => 'Niedziela',

    'section_special' => 'Godziny specjalne',
    'section_special_desc' => 'Święta i wyjątki: zastępują one zwykłe godziny w podanych datach.',

    'section_socials' => 'Profile społecznościowe',
    'section_socials_desc' => 'Linki do Twoich profili w mediach społecznościowych, wyświetlane na wizytówce Google. Publikowane są tylko wypełnione pola; pozostaw pole puste, aby zachować obecną wartość w Google.',
    'add_special' => 'Dodaj godziny specjalne',
    'field_start_date' => 'Od',
    'field_end_date' => 'Do',
    'field_closed' => 'Zamknięte w te dni',

    'save' => 'Opublikuj w Google',
    'saved' => 'Aktualizacja profilu wysłana do Google',
    'save_failed' => 'Aktualizacja nie powiodła się',
    'unmatched' => 'Tej lokalizacji nie udało się jeszcze dopasować do wizytówki Google.',

    'field_additional_phones' => 'Dodatkowe numery telefonów',
    'field_additional_phones_placeholder' => 'dodaj numer + Enter',
    'field_additional_phones_help' => 'Do dwóch dodatkowych numerów wyświetlanych na profilu.',
    'field_timezone' => 'Strefa czasowa',
    'field_timezone_helper' => 'Godziny pracy automatycznych odpowiedzi są interpretowane w tej strefie czasowej. Wykrywana automatycznie przy połączeniu; zmień tutaj, jeśli jest błędna.',
    'loading_live' => 'Wczytywanie aktualnych danych profilu z Google…',
];
