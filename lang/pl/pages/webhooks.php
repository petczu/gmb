<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhooki to funkcja Pro',
    'pro_only_body' => 'Otrzymuj podpisany HTTP POST w momencie, gdy pojawi się opinia, zostanie opublikowana odpowiedź, osiągnięty cel lub wykryta anomalia. Przejdź na plan Pro, aby dodawać endpointy.',
    'see_plans' => 'Zobacz plany',

    'intro' => 'Wysyłamy podpisany ładunek JSON metodą POST do Twojego endpointu przy każdym subskrybowanym zdarzeniu, z ponawianiem prób. Zweryfikuj nagłówek X-Webhook-Signature względem sekretu swojego endpointu.',

    'docs_link' => 'Dokumentacja webhooków',
    'empty' => 'Brak jeszcze endpointów webhooków.',
    'col_url' => 'URL',
    'col_events' => 'Zdarzenia',
    'col_active' => 'Aktywny',
    'col_last' => 'Ostatnie uruchomienie',

    'create' => 'Dodaj endpoint',
    'create_heading' => 'Dodaj endpoint webhooka',
    'edit' => 'Edytuj',
    'delete' => 'Usuń',
    'saved' => 'Zapisano endpoint',
    'created' => 'Dodano endpoint',
    'deleted' => 'Usunięto endpoint',

    'field_name' => 'Nazwa (opcjonalnie)',
    'field_url' => 'URL endpointu',
    'field_events' => 'Zdarzenia',
    'field_active' => 'Aktywny',

    'secret' => 'Sekret',
    'secret_heading' => 'Sekret podpisu',
    'secret_desc' => 'Użyj go do weryfikacji podpisu ładunku.',
    'signature_hint' => 'Każde żądanie jest podpisane:',

    'deliveries' => 'Dostarczenia',
    'deliveries_heading' => 'Ostatnie dostarczenia',
    'no_deliveries' => 'Brak jeszcze dostarczeń.',
    'attempts' => 'prób',
    'resend' => 'Wyślij ponownie',
    'resent' => 'Ponownie skolejkowano dostarczenie',
    'status_pending' => 'Oczekujące',
    'status_success' => 'Dostarczone',
    'status_failed' => 'Nieudane',

    'event_review_created' => 'Nowa opinia',
    'event_reply_published' => 'Opublikowano odpowiedź',
    'event_goal_reached' => 'Osiągnięto cel',
    'event_anomaly_detected' => 'Wykryto anomalię',
];
