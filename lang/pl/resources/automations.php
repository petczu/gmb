<?php

declare(strict_types=1);

return [
    'col_name' => 'Nazwa',
    'col_enabled' => 'Włączona',
    'name' => 'Nazwa',
    'enabled' => 'Włączona',

    // Empty state
    'empty_heading' => 'Nie masz jeszcze automatyzacji',
    'empty_desc' => 'Skonfiguruj automatyzację, aby automatycznie odpowiadać na nowe opinie według oceny i lokalizacji.',
    'empty_cta' => 'Nowa automatyzacja',

    // Table columns
    'col_rating' => 'Ocena',
    'rating_any' => 'dowolna',
    'col_reply' => 'Odpowiedź',
    'reply_ai' => 'AI: :agent',
    'reply_default' => 'Wiadomość domyślna',
    'col_mode' => 'Tryb',
    'mode_approval' => 'Zatwierdzanie',
    'mode_auto' => 'Automatyczna publikacja',
    'col_scope' => 'Zakres',
    'scope_all' => 'Wszystkie lokalizacje',
    'scope_count' => ':count lokalizacji',

    // Run action
    'run_now' => 'Uruchom teraz',
    'run_heading' => 'Uruchom tę automatyzację teraz',
    'run_desc' => 'Zastosuj tę automatyzację do pasujących opinii bez odpowiedzi. Opcjonalnie ogranicz ją do okresu według daty opinii; zostaw oba pola puste, aby uwzględnić wszystkie.',
    'run_from' => 'Opinie od',
    'run_until' => 'Opinie do',
    'run_title' => 'Uruchomiono „:name”',
    'run_queued_title' => '„:name” w kolejce',
    'run_queued_body' => 'Uruchomienie odbywa się w tle. Nowe wersje robocze trafiają do Zatwierdzeń, a automatycznie opublikowane odpowiedzi pojawiają się przy opiniach w ciągu najbliższych minut.',
    'run_body' => 'Wygenerowano :generated, opublikowano :published, w kolejce :queued, pominięto :skipped.',

    // Form — Flow section
    'flow_section' => 'Przepływ',
    'flow_section_desc' => 'Kiedy automatyzacja się uruchamia i których opinii dotyczy.',
    'trigger' => 'Wyzwalacz',
    'trigger_new_review' => 'Nowa opinia w Google',
    'rating_is' => 'Ocena wynosi…',
    'rating_helper' => 'Zostaw wszystkie odznaczone, aby dotyczyło dowolnej oceny.',
    'all_locations' => 'Wszystkie lokalizacje',
    'locations' => 'Lokalizacje',
    'all_locations_helper' => 'Działa jako reguła zbiorcza: automatyzacje ograniczone do konkretnych lokalizacji mają pierwszeństwo dla swoich lokalizacji.',
    'covered_by' => 'już w „:name” (:ratings)',
    'any_rating' => 'dowolna ocena',
    'overlap_title' => 'Nakłada się z inną automatyzacją',
    'overlap_body' => 'Pasuje też do tych samych opinii: :list. Każda opinia jest obsługiwana przez dokładnie jedną automatyzację: konkretne lokalizacje wygrywają z „Wszystkie lokalizacje”, w przeciwnym razie uruchamia się starsza.',
    'respect_working_hours' => 'Przestrzegaj godzin pracy',
    'respect_working_hours_helper' => 'Odpowiadaj tylko w godzinach otwarcia lokalizacji.',
    'reply_to_previous' => 'Odpowiadaj na wcześniejsze opinie',
    'reply_to_previous_helper' => 'Obsłuż także istniejące opinie bez odpowiedzi (wlicza się do miesięcznego limitu AI).',
    'approve_before_posting' => 'Zatwierdź przed publikacją',
    'approve_before_posting_helper' => 'Wyłączone = automatyczna publikacja w Google. Włączone = najpierw wyślij do Zatwierdzeń.',

    // Form — Timing section
    'timing_section' => 'Czas',
    'timing_section_desc' => 'Dodaj losowe opóźnienie (i opcjonalnie godziny pracy), aby odpowiedzi publikowały się w naturalnym, ludzkim tempie, a nie natychmiast.',
    'reply_delay_min' => 'Minimalne opóźnienie',
    'reply_delay_max' => 'Maksymalne opóźnienie',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'Odpowiedzi są publikowane po losowym opóźnieniu między minimum a maksimum, aby wyglądały organicznie. Ustaw oba na 0, aby publikować natychmiast.',
    'reply_delay_max_error' => 'Maksymalne opóźnienie musi być większe lub równe minimalnemu opóźnieniu.',
    'working_days' => 'Dni robocze',
    'working_start' => 'Godzina rozpoczęcia',
    'working_end' => 'Godzina zakończenia',
    'day_mon' => 'Pon',
    'day_tue' => 'Wt',
    'day_wed' => 'Śr',
    'day_thu' => 'Czw',
    'day_fri' => 'Pt',
    'day_sat' => 'Sob',
    'day_sun' => 'Niedz',

    // Form — Content section
    'content_section' => 'Treść',
    'content_section_desc' => 'Jaką odpowiedź wysłać.',
    'content_ai_agent' => 'Agent AI',
    'content_default_message' => 'Wiadomość domyślna',
    'ai_agent' => 'Agent AI',
    'default_message' => 'Wiadomość domyślna',
];
