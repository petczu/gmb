<?php

declare(strict_types=1);

return [
    'nav' => 'Posty',
    'title' => 'Posty Google',

    'empty' => 'Brak postów.',
    'empty_desc' => 'Utwórz swój pierwszy post, aby pokazać aktualności, oferty lub wydarzenia w swoim profilu Google.',

    'not_configured_title' => 'Publikowanie treści nie jest skonfigurowane',
    'not_configured_body' => 'Ustaw ZERNIO_API_KEY w środowisku serwera, aby włączyć posty Google.',

    'col_created' => 'Utworzono',
    'col_type' => 'Typ',
    'col_caption' => 'Tekst',
    'col_locations' => 'Lokalizacje',
    'col_status' => 'Status',
    'col_scheduled' => 'Zaplanowano na',

    'type_update' => 'Aktualność',
    'type_offer' => 'Oferta',
    'type_event' => 'Wydarzenie',
    'type_photo' => 'Zdjęcie',

    'status_published' => 'Opublikowany',
    'status_scheduled' => 'Zaplanowany',
    'status_in_progress' => 'Publikowanie…',
    'status_failed' => 'Nieudany',
    'status_draft' => 'Szkic',

    'create' => 'Nowy post',
    'create_heading' => 'Nowy post Google',
    'submit' => 'Opublikuj',

    'field_type' => 'Typ postu',
    'field_locations' => 'Lokalizacje',
    'field_caption' => 'Tekst',
    'field_image' => 'Obraz',
    'field_image_helper' => 'Obraz musi być publicznie dostępny, aby Google mógł go pobrać: przesyłanie działa tylko z serwera publicznego, a nie z komputera lokalnego.',
    'field_photo_category' => 'Kategoria zdjęcia',
    'field_title' => 'Tytuł',
    'field_starts' => 'Początek',
    'field_ends' => 'Koniec',
    'field_voucher' => 'Kod rabatowy',
    'field_redeem_url' => 'Link do skorzystania',
    'field_terms_url' => 'Link do regulaminu',
    'field_cta' => 'Przycisk wezwania do działania',
    'field_cta_url' => 'Link przycisku',
    'field_schedule' => 'Zaplanuj na później',
    'field_schedule_helper' => 'Pozostaw puste, aby opublikować od razu. Godziny są w UTC.',

    'cta_none' => 'Bez przycisku',
    'cta_book' => 'Zarezerwuj',
    'cta_order' => 'Zamów online',
    'cta_shop' => 'Kup',
    'cta_learn_more' => 'Dowiedz się więcej',
    'cta_sign_up' => 'Zarejestruj się',
    'cta_call' => 'Zadzwoń teraz',

    'no_locations' => 'Wybierz co najmniej jedną lokalizację.',
    'unmatched' => 'Tych lokalizacji nie udało się jeszcze dopasować do wizytówki Google:',
    'publish_failed' => 'Publikacja nieudana',
    'published_ok' => 'Post opublikowany',
    'scheduled_ok' => 'Post zaplanowany',

    'delete' => 'Usuń',
    'delete_desc' => 'To usuwa tylko wpis z tej listy, nie usuwa postu z Google.',
    'deleted' => 'Wpis usunięty',

    // Calendar view
    'view_calendar' => 'Kalendarz',
    'view_list' => 'Lista',
    'view_month' => 'Miesiąc',
    'view_week' => 'Tydzień',
    'today' => 'Dziś',
    'all_locations' => 'Wszystkie lokalizacje',
    'location_plus' => ':name +:count',
    'close' => 'Zamknij',
    'location_count' => '{1} 1 lokalizacja|[2,*] :count lokalizacji',
    'add_post' => 'Post',
    'add_note' => 'Notatka',

    // Drafts
    'save_draft' => 'Zapisz szkic',

    // Imported Google posts
    'view' => 'Zobacz',
    'duplicate_draft' => 'Powiel jako szkic',
    'duplicated_draft' => 'Szkic utworzony',
    'draft_heading' => 'Edytuj szkic',
    'draft_saved' => 'Szkic zapisany',
    'draft_delete' => 'Usuń szkic',
    'draft_delete_desc' => 'Szkic zostanie usunięty. Nic nie zostało opublikowane w Google.',
    'draft_deleted' => 'Szkic usunięty',

    // Live preview
    'preview_label' => 'Podgląd',
    'preview_business' => 'Twoja firma',
    'preview_now' => 'przed chwilą',
    'preview_no_image' => 'Brak obrazu',
    'preview_placeholder' => 'Tutaj pojawi się tekst Twojego postu.',

    // Sticky notes
    'note_placeholder' => 'Wpisz prywatną notatkę…',
    'note_color' => 'Kolor notatki',
    'note_tag' => '# etykieta',
    'note_delete' => 'Usuń notatkę',
    'note_delete_confirm' => 'Usunąć tę notatkę?',
    'filter' => 'Filtruj',
    'notes_filter' => 'Notatki',
    'notes_filter_title' => 'Notatki według etykiety',
    'notes_filter_hint' => 'Odznaczone etykiety są ukryte w kalendarzu.',
    'notes_filter_untagged' => 'Bez etykiety',

    'color_yellow' => 'Żółty',
    'color_orange' => 'Pomarańczowy',
    'color_red' => 'Czerwony',
    'color_pink' => 'Różowy',
    'color_purple' => 'Fioletowy',
    'color_blue' => 'Niebieski',
    'color_teal' => 'Turkusowy',
    'color_green' => 'Zielony',
    'color_gray' => 'Szary',

    // External calendars
    'calendars_button' => '{0} Kalendarze|{1} 1 kalendarz|[2,*] :count kalendarzy',
    'calendars_connect' => 'Kalendarz zewnętrzny',
    'calendars_title' => 'Kalendarze zewnętrzne',
    'calendars_empty' => 'Nałóż publiczne kalendarze na ten widok: święta, rezerwacje lub inne plany treści.',
    'calendars_synced_ago' => 'Zsynchronizowano :ago',
    'calendars_refresh' => 'Synchronizuj teraz',
    'calendars_synced' => 'Kalendarze zsynchronizowane',
    'calendars_sync_failed' => 'Niektórych kalendarzy nie udało się zsynchronizować',
    'calendar_add' => 'Dodaj kalendarz zewnętrzny',
    'calendar_add_submit' => 'Dodaj kalendarz',
    'calendar_name' => 'Nazwa',
    'calendar_name_placeholder' => 'np. Święta w Polsce',
    'calendar_url' => 'Link ICS',
    'calendar_url_helper' => 'Publiczny adres URL kanału iCal/ICS. W Kalendarzu Google: Ustawienia, następnie „Integracja kalendarza”, następnie „Publiczny adres w formacie iCal”.',
    'calendar_color' => 'Kolor',
    'calendar_added' => 'Kalendarz dodany',
    'calendar_events_count' => '{0} Nie znaleziono wydarzeń w kanale.|{1} Zaimportowano 1 wydarzenie.|[2,*] Zaimportowano :count wydarzeń.',
    'calendar_sync_error' => 'Kalendarz dodany, ale nie udało się zsynchronizować kanału',
    'calendar_delete' => 'Usuń kalendarz',
    'calendar_delete_confirm' => 'Usunąć ten kalendarz i jego wydarzenia z widoku?',
];
