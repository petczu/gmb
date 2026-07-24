<?php

declare(strict_types=1);

return [
    'company_name' => 'Nazwa firmy',

    // Social login
    'continue_google' => 'Kontynuuj z Google',

    // Auth page footer (login / register)
    'legal' => 'Kontynuując, akceptujesz :terms, :privacy oraz :cookie serwisu Repunio.',
    'terms' => 'Regulamin',
    'privacy' => 'Politykę prywatności',
    'cookie' => 'Politykę plików cookie',
    'continue_linkedin' => 'Kontynuuj z LinkedIn',
    'continue_microsoft' => 'Kontynuuj z Microsoft',

    'hero_badge' => 'Nowość!',
    'hero_update_title' => 'Porównanie z konkurencją i trendy',
    'hero_update_text' => 'Zobacz, jak naprawdę radzą sobie firmy w Twojej okolicy: ich oceny, jak szybko przybywa im opinii i jak wypadasz na ich tle. Posty Google i edycja profilu też się tu znalazły.',
    'hero_register_title' => 'Twoje opinie Google na autopilocie',
    'hero_register_subtitle' => 'Więcej opinii, każda z nich z odpowiedzią i raport na koniec miesiąca, który to potwierdza.',
    'hero_register_points' => [
        'Odpowiedzi pisane Twoim głosem. Pozwól im wychodzić samodzielnie albo najpierw sprawdź każdą z nich',
        'Miesięczne raporty PDF, które naprawdę są czytane',
        'Strona z kodem QR, która przynosi opinie, posty Google i dyskretne oko na konkurencję',
    ],
    'hero_register_footnote' => 'Zajmuje około 10 minut: połącz swój Profil Firmy w Google i gotowe.',
    'continue_with_email' => 'Kontynuuj z adresem e-mail',
    'change_email' => 'Zmień',

    // Invitation-bound sign-up (email locked to the invited address)
    'invite_join_title' => 'Dołączasz do :workspace',
    'invite_join_title_generic' => 'Przyjmujesz zaproszenie',
    'invite_email_locked' => 'Zarejestruj się z adresem :email, aby je przyjąć. Aby użyć innego adresu, poproś o nowe zaproszenie.',
    'invite_email_login' => 'Zaloguj się z adresem :email, aby je przyjąć.',

    // Sign-up code (passwordless email registration)
    'code_label' => 'Kod weryfikacyjny',
    'code_help' => 'Wysłaliśmy 6-cyfrowy kod na adres :email. Wpisz go poniżej.',
    'code_invalid' => 'Ten kod jest nieprawidłowy lub wygasł. Sprawdź e-mail albo poproś o nowy kod.',
    'code_throttled' => 'Zbyt wiele żądań kodu. Odczekaj kilka minut i spróbuj ponownie.',
    'resend_code' => 'Wyślij nowy kod',
    'code_resent' => 'Nowy kod jest już w drodze do Twojej skrzynki.',
    'create_account' => 'Utwórz konto',

    // Terms acceptance (registration step 3: scroll-to-read box)
    'terms_step_label' => 'Jeszcze jedno: nasz Regulamin',
    'terms_scroll_hint' => 'Przewiń do końca Regulaminu, aby kontynuować.',
    'agree_continue' => 'Akceptuję i kontynuuję',
];
