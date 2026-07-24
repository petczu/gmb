<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nic do zatwierdzenia',
    'empty_desc' => 'Gdy automatyzacje przygotują odpowiedzi wymagające zatwierdzenia, pojawią się tutaj.',

    // Columns
    'col_location' => 'Lokalizacja',
    'col_author' => 'Autor',
    'col_rating' => 'Ocena',
    'col_review' => 'Opinia',
    'col_ai_reply' => 'Odpowiedź AI',
    'col_status' => 'Status',
    'col_source' => 'Źródło',
    'col_generated' => 'Wygenerowano',
    'source_ai' => 'AI',
    'source_template' => 'Szablon',

    // Statuses
    'status_pending' => 'Oczekuje',
    'status_scheduled' => 'Zaplanowano',
    'status_published' => 'Opublikowano',
    'status_skipped' => 'Pominięto',
    'status_failed' => 'Niepowodzenie',
    'status_indicator' => 'Status: :status',
    'scheduled_for' => 'Publikacja :time',

    // Actions
    'approve' => 'Zatwierdź i opublikuj',
    'approve_publish' => 'Zatwierdź i opublikuj',
    'edit_publish' => 'Edytuj i opublikuj',
    'review_reply' => 'Przejrzyj i odpowiedz',
    'reply' => 'Odpowiedz',
    'reject' => 'Odrzuć',

    // Filters
    'filter_date' => 'Data opinii',
    'filter_from' => 'Od :date',
    'filter_to' => 'Do :date',

    // Notifications
    'reply_published' => 'Odpowiedź opublikowana',

    'approve_selected' => 'Zatwierdź i opublikuj zaznaczone',
    'reject_selected' => 'Odrzuć zaznaczone',
    'bulk_approve_confirm' => 'Opublikować wszystkie zaznaczone odpowiedzi w Google? Trafiają do kolejki i są wysyłane automatycznie w ciągu najbliższych minut.',
    'bulk_reject_confirm' => 'Odrzucić wszystkie zaznaczone wersje robocze?',
    'bulk_queued' => ':count odpowiedzi w kolejce do publikacji',
    'bulk_queued_body' => 'Publikują się automatycznie w ciągu najbliższych minut. Każde niepowodzenie pojawia się w filtrze Niepowodzenia wraz z przyczyną.',
    'bulk_rejected' => ':count wersji roboczych odrzuconych',
    'publish_failed_title' => 'Publikacja nie powiodła się',
    'publish_not_found' => 'Google informuje, że ta opinia już nie istnieje. Mogła zostać usunięta przez autora lub lokalizacja została ponownie połączona z nowym kontem. Wersja robocza została oznaczona jako nieudana.',
    'publish_error' => 'Nie udało się opublikować odpowiedzi. Wersja robocza została oznaczona jako nieudana: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google nie znalazł tej opinii ani lokalizacji, aby na nią odpowiedzieć. Mogła zostać usunięta lub odpowiedzi nie są dostępne dla tej lokalizacji.',
    'error_rate_limited' => 'Google ogranicza tempo publikowania odpowiedzi. Próba zostanie ponowiona automatycznie.',
    'error_unauthorized' => 'Połączenie z Google nie ma uprawnień do odpowiadania tutaj. Połącz ponownie konto i spróbuj jeszcze raz.',
    'error_generic' => 'Nie udało się opublikować odpowiedzi. Spróbuj ponownie później.',
    'draft_rejected' => 'Wersja robocza odrzucona',

    // Scheduled items
    'post_now' => 'Opublikuj teraz',
    'post_now_confirm' => 'Odpowiedź zostaje opublikowana w Google natychmiast, z pominięciem zaplanowanego terminu.',
    'post_now_queued' => 'Odpowiedź w kolejce do publikacji',
    'post_now_queued_body' => 'Zostanie wysłana w ciągu najbliższych kilku minut.',
    'cancel_scheduled' => 'Anuluj',
    'cancel_scheduled_confirm' => 'Anulować tę zaplanowaną odpowiedź? Nie zostanie opublikowana.',
    'schedule_cancelled' => 'Zaplanowana odpowiedź anulowana',

    // List tabs
    'tab_pending' => 'Wymaga zatwierdzenia',
    'tab_all' => 'Wszystkie',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Opublikuj zaznaczone teraz',
    'bulk_publish_now_confirm' => 'Zaznaczone odpowiedzi pomijają zaplanowany termin i są wysyłane w ciągu najbliższych kilku minut.',
    'cancel_scheduled_selected' => 'Anuluj zaplanowanie',
];
