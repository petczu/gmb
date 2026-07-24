<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nie masz jeszcze agentów AI',
    'empty_desc' => 'Utwórz agenta AI, aby redagować odpowiedzi i zasilać automatyzacje automatycznych odpowiedzi głosem Twojej marki.',
    'empty_cta' => 'Nowy agent AI',

    // Table
    'col_native_lang' => 'Język natywny',
    'col_default' => 'Domyślny',
    'col_updated' => 'Zaktualizowano',
    'test_preview' => 'Testuj i podglądaj',
    'test_heading' => 'Testuj odpowiedź',
    'close' => 'Zamknij',
    'no_reviews_to_test' => 'Nie ma jeszcze opinii do przetestowania, najpierw zsynchronizuj kilka opinii.',
    'generation_failed' => 'Generowanie nie powiodło się: :error',
    'set_default' => 'Ustaw jako domyślny',

    // Form
    'section' => 'Twój agent AI',
    'section_desc' => 'Nadaj agentowi nazwę i opisz, jak ma odpowiadać. Używany przez automatyzacje odpowiedzi i przycisk „redaguj z AI”.',
    'describe' => 'Opisz swojego agenta',
    'describe_helper' => 'Pełne instrukcje / persona: jak sklasyfikować opinię i jak na nią odpowiedzieć, ton i styl, zasady personalizacji itp.',
    'tone' => 'Ton wypowiedzi',
    'reply_native' => 'Odpowiadaj w języku opinii',
    'reply_native_helper' => 'Agent odpowiada w tym samym języku, w którym napisano opinię.',
    'default_agent' => 'Agent domyślny',
    'default_agent_helper' => 'Używany, gdy automatyzacja nie wskazuje agenta.',

    // Knowledge base
    'knowledge' => 'Baza wiedzy (opcjonalnie)',
    'knowledge_helper' => 'Fakty o firmie, których agent może użyć w odpowiedziach: godziny otwarcia, zasady, nazwy pokoi/usług, oferty, najczęstsze pytania. Trzyma się faktów i nigdy nie wymyśla niczego ponad to.',
    'knowledge_ph' => 'np. Otwarte pon.–niedz. 10:00–22:00. Pokoje: Skok, Ucieczka z Więzienia, Nawiedzony Dwór. Grupy 2–6 osób. Rezerwacja na example.com lub +48 ...',

    // Test panel
    'test_section' => 'Testuj na opinii',
    'test_section_desc' => 'Wybierz prawdziwą opinię i wygeneruj wersję roboczą z bieżącymi (niezapisanymi) ustawieniami, a następnie dopracuj.',
    'test_pick_review' => 'Opinia',
    'test_pick_placeholder' => 'Wybierz zsynchronizowaną opinię…',
    'test_review_text' => 'Opinia',
    'test_generate' => 'Wygeneruj wersję roboczą',
    'test_result' => 'Wygenerowana wersja robocza',
    'test_need_review' => 'Najpierw wybierz opinię do testu.',

    // AI description generator
    'generate_label' => 'Wygeneruj z AI',
    'generate_heading' => 'Wygeneruj opis z AI',
    'generate_desc' => 'Podaj swoją stronę internetową i/lub kilka słów o firmie, a AI zredaguje instrukcje agenta. Wynik możesz później edytować.',
    'generate_submit' => 'Wygeneruj',
    'generate_url' => 'Adres URL strony',
    'generate_notes' => 'Coś do dodania (opcjonalnie)',
    'generate_notes_ph' => 'np. rodzinna włoska restauracja, nacisk na przyjazną obsługę, wspomnieć o naszym tarasie latem',
    'generate_need_input' => 'Najpierw dodaj adres URL strony lub krótki opis.',
    'generate_rate_limited' => 'Zbyt wiele generowań. Poczekaj chwilę i spróbuj ponownie.',
    'generate_done' => 'Opis wygenerowany, przejrzyj go i dopracuj w razie potrzeby.',
    'generate_failed' => 'Nie udało się wygenerować opisu. Spróbuj ponownie lub napisz go ręcznie.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Wspólne zasady',
    'shared_rules_heading' => 'Wspólne zasady odpowiadania',
    'shared_rules_desc' => 'Te zasady obowiązują ponad wszystkimi agentami, w każdej odpowiedzi AI. Idealne do poprawek stylu, których nie chcesz powtarzać dla każdego agenta.',
    'shared_rules_placeholder' => "np.\nW odpowiedziach po polsku pisz „pokój” lub „escape room”, nigdy „room” jako polskie słowo.\nNigdy nie obiecuj zniżek ani zwrotów.\nPodpisuj odpowiedzi bez imienia.",
    'shared_rules_save' => 'Zapisz zasady',
    'shared_rules_saved' => 'Wspólne zasady zapisane',
];
