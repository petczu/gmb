<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Uzupełnij dane swojej firmy',
    'step_company_hint' => 'Kraj i dane do rozliczeń używane na fakturach i w raportach.',
    'step_plan_label' => 'Wybierz plan',
    'step_plan_hint' => 'Rozpocznij 14-dniowy bezpłatny okres próbny, bez karty.',
    'step_location_label' => 'Połącz pierwszą lokalizację',
    'step_location_hint' => 'Połącz Profil Firmy Google, aby zacząć pobierać opinie.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Skonfiguruj swój obszar roboczy',
    'wiz_plan_done' => '✓ Twój plan jest aktywny. Przejdź do następnego kroku.',
    'wiz_plan_pick' => 'Wybierz plan',
    'wiz_interval' => 'Okres rozliczeniowy',
    'wiz_monthly' => 'Miesięcznie',
    'wiz_yearly' => 'Rocznie',
    'wiz_start_trial' => 'Rozpocznij 14-dniowy bezpłatny okres próbny',
    'wiz_trial_note' => 'Twój 14-dniowy bezpłatny okres próbny rozpoczyna się, gdy tylko przejdziesz dalej. Bez karty.',
    'wiz_go_checkout' => 'Przejdź do płatności',
    'wiz_plan_required' => 'Wybierz plan i sfinalizuj płatność, aby kontynuować.',
    'wiz_location_body' => 'Połącz swój Profil Firmy Google, abyśmy mogli pobrać Twoje opinie. Zostaniesz przekierowany do Google, aby autoryzować dostęp, a następnie wybierzesz lokalizację do połączenia.',
    'wiz_connect_google' => 'Połącz Profil Firmy Google',
    'wiz_skip_location' => 'Później',
    'skipped_title' => 'Wszystko gotowe',
    'skipped_body' => 'Możesz połączyć swój Profil Firmy Google w dowolnej chwili na stronie Lokalizacje.',
    'wiz_per_location' => 'za lokalizację / miesiąc',
    'wiz_plan_desc_starter' => 'Skrzynka opinii, ręczne odpowiedzi i podstawowe raporty.',
    'wiz_plan_desc_growth' => 'Dodaje odpowiedzi AI, zaplanowane raporty i porównania.',
    'wiz_plan_desc_pro' => 'Wszystko, plus white label, API, MCP i dostęp dla klientów.',

    // Onboarding overlay
    'welcome_title' => 'Witamy, skonfigurujmy Twoje konto',
    'welcome_subtitle' => 'Kilka szybkich kroków i wszystko gotowe.',
    'continue_step' => 'Kontynuuj: :label',
    'enter_app' => 'Wejdź do aplikacji →',
    'sign_out' => 'Wyloguj się',

    // Pending-deletion overlay
    'deletion_title' => 'Ten obszar roboczy jest przeznaczony do usunięcia',
    'deletion_body' => 'Wszystkie dane zostaną trwale usunięte <strong>:date</strong>. Nadal możesz anulować i zachować swój obszar roboczy.',
    'cancel_deletion' => 'Anuluj usunięcie',

    // Grace banner
    'grace_banner' => '⚠️ Nie udało nam się przetworzyć Twojej ostatniej płatności. Usługa pozostaje aktywna do <strong>:date</strong>, prosimy',
    'update_your_billing' => 'zaktualizuj dane rozliczeniowe',

    // Paywall overlay
    'payment_problem_title' => 'Wystąpił problem z Twoją płatnością',
    'needs_plan_title' => 'Wybierz plan, aby zacząć',
    'payment_problem_body' => 'Twój dostęp jest wstrzymany, ponieważ nie udało nam się przetworzyć płatności. Zaktualizuj dane rozliczeniowe, aby kontynuować.',
    'needs_plan_body' => 'Wybierz plan, aby aktywować opinie, odpowiedzi AI i raporty dla swoich lokalizacji. 14-dniowy bezpłatny okres próbny.',
    'update_billing' => 'Zaktualizuj rozliczenia',
    'view_plans' => 'Zobacz plany',

    // Connect-select-location page
    'connecting_location' => 'Łączenie lokalizacji…',
    'choose_location' => 'Wybierz, którą lokalizację Google Business połączyć z tym obszarem roboczym.',
    'could_not_load' => 'Nie udało się wczytać lokalizacji',
    'pending_expired_title' => 'Sesja Google wygasła',
    'pending_expired' => 'Autoryzacja Google jest ważna tylko przez krótki czas i ta już wygasła. Połącz się ponownie i wybierz lokalizacje jeszcze raz, to zajmie tylko chwilę.',
    'reconnect_google' => 'Połącz Google ponownie',
    'back' => 'Wstecz',
    'no_locations_available' => 'Brak dostępnych lokalizacji',
    'no_locations_body' => 'Nie zwrócono żadnych lokalizacji Google Business. Mogą się jeszcze wczytywać po stronie Google, spróbuj ponownie za chwilę.',
    'connect_then_done' => 'Połącz jedną lub więcej lokalizacji, a następnie kliknij Gotowe.',
    'done' => 'Gotowe',
    'connected' => 'Połączono',
    'connect' => 'Połącz',
    'connecting' => 'Łączenie…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Wybierz lokalizację firmy',
    'connect_failed' => 'Nie udało się połączyć lokalizacji',
    'connected_title' => 'Połączono: :name',
    'connected_body' => 'Opinie synchronizują się w tle, wkrótce pojawią się na stronie Lokalizacje.',
    'location_fallback' => 'lokalizacja',
    'trial_started_title' => 'Twój 14-dniowy okres próbny się rozpoczął',
    'trial_started_body' => 'Pełny dostęp do :date, bez karty. Miłego korzystania!',
];
