<?php

declare(strict_types=1);

return [
    // Columns
    'col_rating' => 'Ocena',
    'col_location' => 'Lokalizacja',
    'col_author' => 'Autor',
    'col_review' => 'Opinia',
    'only_rating' => 'Tylko ocena',
    'col_reply' => 'Odpowiedź',
    'col_status' => 'Status',
    'col_replied_by' => 'Odpowiedział',
    'col_date' => 'Data',
    'replied_ai' => 'AI',
    'replied_human' => 'Zespół',
    'replied_assistant' => 'Asystent',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— brak odpowiedzi —',
    'status_replied' => 'Odpowiedziano',
    'status_pending' => 'Oczekuje',
    'status_scheduled' => 'Zaplanowano',
    'scheduled_for' => 'Publikacja :datetime',
    'replied_at' => 'Odpowiedziano :datetime',
    'status_failed' => 'Niepowodzenie',

    // Filters
    'review_date' => 'Data opinii',
    'filter_from' => 'Od :date',
    'filter_to' => 'Do :date',
    'reply_status' => 'Status odpowiedzi',
    'review_text' => 'Treść opinii',
    'with_text' => 'Z treścią',
    'rating_only' => 'Tylko ocena',
    'photos' => 'Zdjęcia',
    'with_photos' => 'Ze zdjęciami',
    'without_photos' => 'Bez zdjęć',

    // Reply action
    'edit_reply' => 'Edytuj odpowiedź',
    'save_reply' => 'Zapisz',
    'reply' => 'Odpowiedz',
    'reply_to_review' => 'Odpowiedz na opinię',
    'no_written_review' => 'Brak treści opinii, tylko ocena.',
    'translated_by_google' => '🌐 Przetłumaczone przez Google',
    'ai_agent' => 'Agent AI',
    'default_agent' => 'Agent domyślny',
    'your_reply' => 'Twoja odpowiedź',
    'generate_with_ai' => 'Wygeneruj z AI',
    'generate' => 'Wygeneruj',
    'generating' => 'Generowanie odpowiedzi…',
    'cancel' => 'Anuluj',
    'add_emoji' => 'Dodaj emoji',
    'show_translation' => 'Pokaż tłumaczenie (:language)',
    'translation_label' => 'Tłumaczenie (:language)',
    'translation_failed' => 'Tłumaczenie nie powiodło się',
    'hide_emoji' => 'Ukryj emoji',
    'delete_reply' => 'Usuń odpowiedź',
    'delete_reply_desc' => 'Usuwa to odpowiedź z Google. Sama opinia pozostaje bez zmian.',
    'delete_confirm' => 'Usuń',
    'submit_heading' => 'Opublikować odpowiedź?',
    'submit_desc' => 'Publikuje to Twoją odpowiedź publicznie w Google, widoczną dla każdego, kto zobaczy opinię.',
    'submit_confirm' => 'Opublikuj',

    // AI cost hints
    'cost_generic' => 'Generuje to odpowiedź za pomocą AI.',
    'cost_all_used' => 'Wykorzystano wszystkie odpowiedzi AI w tym miesiącu. Dokup pakiet, przejdź na wyższy plan lub napisz odpowiedź ręcznie.',
    'cost_credit' => 'Zużywa to 1 kredyt (pozostało :count).',
    'cost_monthly' => 'Zużywa to 1 z Twoich miesięcznych odpowiedzi AI, pozostało :count.',

    // Notifications
    'reply_deleted' => 'Odpowiedź usunięta',
    'no_changes' => 'Brak zmian do zapisania',
    'reply_published' => 'Odpowiedź opublikowana',
    'reply_failed' => 'Nie udało się opublikować odpowiedzi',
    'ai_limit_reached' => 'Osiągnięto limit AI',
    'ai_limit_body' => 'Wykorzystano wszystkie odpowiedzi AI w tym miesiącu. Edytuj ręcznie lub przejdź na wyższy plan, aby zwiększyć limit.',
    'generation_failed' => 'Generowanie nie powiodło się',
    'reply_generated' => 'Odpowiedź wygenerowana',
    'retry' => 'Ponów',
    'retry_heading' => 'Ponowić tę odpowiedź?',
    'retry_desc' => 'Spróbujemy ponownie: opublikujemy przygotowaną odpowiedź lub wygenerujemy ją ponownie, jeśli krok AI się nie powiódł.',
    'retry_queued' => 'Odpowiedź ponownie w kolejce',
    'retry_nothing' => 'Nie ma nic do ponowienia. Odpowiedz ręcznie.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Wszystkie',
    'tab_needs_approval' => 'Wymaga zatwierdzenia',
    'tab_scheduled' => 'Zaplanowane',
    'tab_published' => 'Opublikowane',
    'tab_failed' => 'Nieudane',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Wyświetlanie 1 opinii z powiadomienia e-mail|[2,*] Wyświetlanie :count opinii z powiadomienia e-mail',
    'from_email_clear' => 'Pokaż wszystkie opinie',
];
