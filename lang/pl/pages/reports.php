<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Restauracja demonstracyjna',
    'demo_period' => 'Raport skuteczności · ostatnie 30 dni',
    'demo_five_star' => 'Udział ocen 5-gwiazdkowych',
    'demo_summary_label' => 'Podsumowanie zarządcze',
    'demo_summary' => 'Restauracja demonstracyjna otrzymała 38 opinii w ciągu ostatnich 30 dni (+9 w porównaniu z poprzednim okresem), przy średniej 4,60★. 84% opinii było pozytywnych, a wskaźnik odpowiedzi osiągnął 92%. Goście wielokrotnie chwalili przyjazny zespół i szybką obsługę.',

    'location' => 'Lokalizacja',
    'business_multi' => ':name + :count więcej',
    'compare' => 'Porównaj',
    'compare_options' => [
        'none' => 'Nie porównuj',
        'previous' => 'Poprzedni okres',
        'custom' => 'Zakres niestandardowy…',
    ],
    'compare_from' => 'Porównaj od',
    'compare_to' => 'Porównaj do',
    'report_language' => 'Język raportu',

    'content_section' => 'Zawartość raportu',
    'content_section_desc' => 'Wybierz szablon, a następnie dostosuj, które bloki pojawiają się w raporcie.',
    'preset' => 'Szablon',
    'blocks' => 'Bloki',
    'competitors_block_hint' => 'Nie śledzisz jeszcze żadnych konkurentów. Najpierw dodaj ich w Wizytówki > Konkurenci.',
    'ai_instructions' => 'Instrukcje dla AI',
    'ai_instructions_help' => 'Opcjonalne wskazówki do tekstu tworzonego przez AI. Najbardziej przydatne przy nazwiskach pracowników: wypisz swój zespół i wszelkie pseudonimy, aby wzmianki były przypisane do właściwej osoby. Zapisywane raz i stosowane do każdego kolejnego raportu, w tym zaplanowanych.',
    'ai_instructions_placeholder' => 'Nasz zespół: Eva, Alette, Suleyman (pisany też Suly), Lisa. Połącz pseudonimy z pełnym imieniem.',
    'ai_improve' => 'Ulepsz z AI',
    'ai_improve_empty' => 'Najpierw napisz kilka notatek, a potem je ulepsz.',
    'ai_improve_rate_limited' => 'Zbyt wiele prób, spróbuj ponownie później.',
    'ai_improve_done' => 'Instrukcje ulepszone',
    'ai_improve_failed' => 'Nie udało się ulepszyć instrukcji, spróbuj ponownie.',

    'schedule_report' => 'Wysyłaj według harmonogramu',
    'schedule_heading' => 'Zaplanuj ten raport',
    'schedule_desc' => 'Bieżący wybór (okres, lokalizacja, porównanie, bloki) będzie wysyłany e-mailem jako PDF według cyklicznego harmonogramu.',
    'schedule_submit' => 'Utwórz harmonogram',
    'schedule_created' => 'Harmonogram utworzony',
    'schedule_created_body' => 'Zarządzaj nim w Raporty → Zaplanowane raporty.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'Pozostało :left z :cap raportów AI w tym miesiącu',

    // Generate modal
    'generate_heading' => 'Wygenerować raport AI?',
    'generate_desc' => 'Wygeneruj podsumowanie zarządcze AI dla bieżącego wyboru.',
    'generate_desc_left' => 'Zużywa to 1 z Twoich miesięcznych raportów AI, pozostało :left.',
    'generate_submit' => 'Wygeneruj',

    // Generate notifications
    'report_generated' => 'Raport wygenerowany',
    'report_generated_body' => 'Podsumowanie AI jest gotowe, podgląd zaktualizowany. Użyj Pobierz, aby zapisać PDF.',
    'limit_reached' => 'Osiągnięto miesięczny limit raportów',
    'limit_reached_body' => 'Wyświetlany jest podstawowy raport bez AI. Przejdź na wyższy plan, aby uzyskać wyższy limit miesięczny.',

    // Blade view
    'generate_report' => 'Wygeneruj raport',
    'generating' => 'Generowanie…',
    'download_pdf' => 'Pobierz PDF',
    'download_first_tooltip' => 'Najpierw wygeneruj raport',
    'building' => 'Tworzenie raportu…',
    'preview_title' => 'Podgląd raportu',
];
