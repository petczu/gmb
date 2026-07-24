<?php

declare(strict_types=1);

return [
    'greeting' => 'Cześć :name,',
    'signoff' => 'Dziękujemy,',
    'team' => 'Zespół Repunio',

    'drip_competitors' => [
        'subject' => 'Czy wiesz, jak radzi sobie firma obok?',
        'intro' => 'Twoje własne opinie masz pod kontrolą. Kolejne pytanie, które zadaje sobie każdy właściciel: czy wyprzedzam konkurencję, czy zostaję w tyle? Repunio może to dla Ciebie śledzić, z codziennymi danymi o ocenie i liczbie opinii dowolnej firmy w Google.',
        'tip' => 'Zajmuje to dwie minuty: otwórz Konkurencję, wyszukaj nazwę i dodaj ją. Od tej chwili zobaczysz, kto wychodzi na prowadzenie, o ile i czy Twoja ocena dotrzymuje kroku.',
        'cta' => 'Dodaj pierwszego konkurenta',
    ],

    'location_connected' => [
        'subject' => ':location została połączona',
        'intro' => 'Twoja lokalizacja :location jest już połączona. Właśnie importujemy jej opinie z Google; w zależności od ich liczby może to zająć kilka minut.',
        'note' => 'Otrzymasz kolejny e-mail, gdy tylko opinie zostaną wczytane.',
        'cta' => 'Zobacz lokalizacje',
    ],

    'location_synced' => [
        'subject' => 'Twoje opinie są już wczytane',
        'intro' => 'Pierwszy import został zakończony. Oto, co napłynęło:',
        'note' => 'Od teraz nowe opinie pojawiają się automatycznie, a Twoje reguły automatyzacji będą do nich stosowane.',
        'cta' => 'Otwórz skrzynkę opinii',
    ],

    'drip_connect' => [
        'subject' => 'Twoje konto jest gotowe. Został jeden krok',
        'intro' => 'Twój obszar roboczy Repunio jest skonfigurowany, ale wciąż pusty: opinie, oceny i raporty pochodzą z Twojego Profilu Firmy w Google, a żaden nie jest jeszcze połączony.',
        'tip' => 'Zajmuje to około dwóch minut: otwórz Lokalizacje, kliknij Połącz, zaloguj się przez Google i wybierz swoją firmę. Twoje opinie zaczną napływać od razu.',
        'cta' => 'Połącz swoją lokalizację',
    ],

    'signup_code' => [
        'subject' => ':code to Twój kod rejestracji w Repunio',
        'intro' => 'Wpisz ten kod na stronie rejestracji, aby potwierdzić swój adres e-mail:',
        'note' => 'Kod jest ważny przez :minutes minut. Jeśli o niego nie prosiłeś, możesz spokojnie zignorować tę wiadomość.',
    ],

    'beta_received' => [
        'subject' => 'Dziękujemy! Twoja prośba o dostęp została przyjęta',
        'intro' => 'Dziękujemy za rejestrację! Repunio jest obecnie w prywatnej wersji beta i aktywujemy nowe konta w małych partiach.',
        'note' => 'Napiszemy do Ciebie, gdy tylko Twój dostęp będzie gotowy. Na razie nie musisz nic robić.',
    ],

    'beta_approved' => [
        'subject' => 'Twój dostęp do Repunio jest gotowy',
        'intro' => 'Dobra wiadomość: Twoje konto zostało aktywowane. Możesz się teraz zalogować i wszystko skonfigurować.',
        'note' => 'Zacznij od połączenia swojego Profilu Firmy w Google, Twoje opinie zostaną zaimportowane w ciągu kilku minut.',
        'cta' => 'Otwórz Repunio',
    ],

    'welcome' => [
        'subject' => 'Witamy w Repunio',
        'intro' => 'Twoje konto jest gotowe. Repunio pomaga zbierać opinie Google, odpowiadać na nie i raportować, wszystko w jednym miejscu.',
        'next' => 'Następnie: połącz swoją pierwszą lokalizację i wybierz plan, aby rozpocząć 14-dniowy bezpłatny okres próbny.',
        'cta' => 'Otwórz Repunio',
    ],

    'trial_ending' => [
        'subject' => 'Twój bezpłatny okres próbny kończy się za :days dni',
        'intro' => 'Twój bezpłatny okres próbny Repunio kończy się :date. Dodaj teraz metodę płatności, aby nic się nie zatrzymało, Twoje opinie nadal się synchronizują, a odpowiedzi AI nadal działają.',
        'note' => 'Nie pobierzemy opłaty przed końcem okresu próbnego, a możesz zrezygnować w każdej chwili.',
        'cta' => 'Dodaj metodę płatności',
    ],

    'payment_succeeded' => [
        'subject' => 'Otrzymaliśmy płatność',
        'intro' => 'Otrzymaliśmy Twoją płatność w wysokości :amount. Twoja subskrypcja Repunio jest aktywna.',
        'cta' => 'Zobacz rozliczenia',
    ],

    'payment_failed' => [
        'subject' => 'Płatność nie powiodła się, wymagane działanie',
        'intro' => 'Nie udało nam się przetworzyć Twojej ostatniej płatności. Twoje konto działa jeszcze przez :days dni, zaktualizuj dane rozliczeniowe, aby uniknąć przerwy.',
        'cta' => 'Zaktualizuj rozliczenia',
    ],

    'subscription_canceled' => [
        'subject' => 'Twoja subskrypcja zostanie anulowana',
        'intro' => 'Twoja subskrypcja Repunio została anulowana. Zachowujesz pełny dostęp do :date, po czym nie zostanie odnowiona.',
        'note' => 'Zmieniłeś zdanie? Możesz wznowić w dowolnej chwili przed tą datą, bez opłat.',
        'cta' => 'Wznów subskrypcję',
    ],

    'subscription_resumed' => [
        'subject' => 'Twoja subskrypcja jest znów aktywna',
        'intro' => 'Twoja subskrypcja Repunio została wznowiona i będzie normalnie się odnawiać. Nic więcej nie musisz robić.',
        'cta' => 'Zobacz rozliczenia',
    ],

    'ai_limit' => [
        'subject' => 'Wykorzystałeś wszystkie odpowiedzi AI w tym miesiącu',
        'intro' => 'Osiągnąłeś miesięczny limit odpowiedzi AI w planie :plan. Przejdź na wyższy plan, aby zwiększyć limit, lub odpowiadaj ręcznie do przyszłego miesiąca.',
        'cta' => 'Zobacz plany',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Płatność za doładowanie AI nie powiodła się',
        'intro' => 'Próbowaliśmy automatycznie doładować Twoje odpowiedzi AI, ale płatność nie przeszła. Zaktualizuj kartę, aby automatyczne doładowanie mogło dalej działać.',
        'cta' => 'Zaktualizuj rozliczenia',
    ],

    'new_reviews' => [
        'subject' => ':count nowa opinia (opinii) dla Twojej firmy',
        'intro' => 'Masz :count nowych opinii dla :location.',
        'col_author' => 'Autor',
        'col_rating' => 'Ocena',
        'col_location' => 'Lokalizacja',
        'col_review' => 'Opinia',
        'cta' => 'Zobacz opinie',
    ],

    'account_disconnected' => [
        'subject' => 'Wymagane działanie: Twoje połączenie z Google przestało działać',
        'intro' => 'Połączenie z Google dla „:account" przestało działać, więc Twoje opinie nie są już synchronizowane.',
        'detail' => 'Połącz konto ponownie, aby wznowić synchronizację opinii i publikowanie odpowiedzi.',
        'cta' => 'Połącz ponownie',
    ],

    'sync_restored' => [
        'subject' => 'Twoje połączenie z Google zostało przywrócone',
        'intro' => 'Dobra wiadomość: połączenie dla „:account" zostało przywrócone i synchronizacja została wznowiona. Twoje opinie są znów aktualne.',
        'cta' => 'Otwórz Repunio',
    ],

    'negative_review' => [
        'subject' => 'Opinia :rating★ wymaga Twojej uwagi',
        'intro' => 'Nowa opinia dla :business wymaga Twojej uwagi.',
        'col_author' => 'Autor',
        'col_rating' => 'Ocena',
        'col_review' => 'Opinia',
        'cta' => 'Odpowiedz teraz',
    ],

    'reply_failed' => [
        'subject' => 'Nie udało nam się opublikować Twojej odpowiedzi',
        'intro' => 'Próbowaliśmy opublikować odpowiedź na opinię dla :business, ale się nie udało.',
        'col_author' => 'Autor',
        'col_review' => 'Opinia',
        'detail' => 'Spróbuj opublikować odpowiedź ponownie z poziomu aplikacji.',
        'detail_retry' => 'Wygląda to na chwilowy problem, więc automatycznie spróbujemy opublikować ją ponownie w ciągu najbliższych godzin. Nie musisz nic robić. Jeśli nadal się nie uda, znajdziesz ją w sekcji Opinie → Nieudane.',
        'detail_not_found' => 'Google zgłasza, że ta opinia już nie istnieje. Mogła zostać usunięta przez autora lub odfiltrowana przez Google. Nic nie trzeba robić: wersja robocza została odłożona i nie będzie ponawiana.',
        'detail_unauthorized' => 'Połączenie z Google nie ma uprawnień do odpowiadania w tej lokalizacji, więc nie będziemy ponawiać próby. Połącz konto ponownie, a następnie opublikuj odpowiedź z poziomu aplikacji.',
        'cta' => 'Otwórz zatwierdzenia',
    ],

    'post_failed' => [
        'subject' => 'Nie udało nam się opublikować Twojego posta Google',
        'intro' => 'Próbowaliśmy opublikować post Google dla :business, ale się nie udało. Post jest w Twoim kalendarzu wraz z błędem.',
        'detail' => 'Spróbuj opublikować post ponownie z poziomu aplikacji.',
        'detail_reason' => 'Powód: :reason',
        'cta' => 'Otwórz posty',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies czeka na zatwierdzenie',
        'intro' => 'Masz :count :replies czekających na Twoje zatwierdzenie. Sprawdź i zatwierdź, aby zostały opublikowane.',
        'reply_word' => '{1}odpowiedź|[2,*]odpowiedzi',
        'reply_label' => 'Sugerowana odpowiedź',
        'cta' => 'Przejrzyj zatwierdzenia',
    ],

    'review_goal' => [
        'subject_mid' => 'Twój cel opinii: jak idzie ten miesiąc',
        'subject_recap' => 'Podsumowanie opinii za :month',
        'intro_mid_ahead' => 'Świetne tempo! Masz :actual nowych opinii w tym miesiącu, powyżej :expected oczekiwanych na teraz (cel :goal). Tak trzymaj.',
        'intro_mid_on_track' => 'Jesteś na dobrej drodze: :actual nowych opinii w tym miesiącu, w okolicy :expected oczekiwanych na teraz (cel :goal).',
        'intro_mid_behind' => 'Mała zachęta: masz :actual nowych opinii w tym miesiącu, poniżej :expected oczekiwanych na teraz (cel :goal). Trochę wysiłku pomoże.',
        'intro_recap' => 'Oto jak zakończył się :month: :actual nowych opinii wobec celu :goal.',
        'col_location' => 'Lokalizacja',
        'col_goal' => 'Cel',
        'col_so_far' => 'Dotychczas',
        'col_projected' => 'Prognoza',
        'col_pace' => 'Tempo',
        'col_got' => 'Uzyskano',
        'col_vs_goal' => 'wobec celu',
        'col_vs_prev' => 'wobec zeszłego miesiąca',
        'status_ahead' => 'Powyżej',
        'status_on_track' => 'Na dobrej drodze',
        'status_behind' => 'Poniżej',
        'cta' => 'Zobacz opinie',
    ],

    'coaching' => [
        'subject' => 'Twój cel opinii: nie zwalniaj tempa',
        'intro_almost' => 'Już blisko! Jeszcze tylko :remaining, aby osiągnąć cel :goal w tym miesiącu. Dasz radę!',
        'intro_behind' => 'Jesteś na poziomie :actual z :goal w tym miesiącu. Konsekwentny wysiłek w tym tygodniu przywróci Cię do tempa. Oto kilka pomysłów.',
        'intro_on_track' => 'Dobra robota! :actual z :goal i dokładnie w tempie. Kilka próśb w tym tygodniu podtrzyma rozpęd.',
        'intro_ahead' => 'Świetny rozpęd! :actual z :goal, powyżej planu. Kontynuuj z tymi pomysłami.',
        'steady' => 'Jedna rzecz: rozłóż prośby na kolejne dni. Nagły zalew opinii wygląda dla Google podejrzanie i może zostać odfiltrowany. Stałe tempo wygrywa.',
        'cta' => 'Otwórz opinie',
    ],

    'goal_reached' => [
        'subject' => 'Cel osiągnięty! :goal opinii w tym miesiącu! 🎉',
        'intro' => 'Gratulacje! Osiągnąłeś swój cel :goal nowych opinii w tym miesiącu! To prawdziwy rozpęd dla Twojej reputacji.',
        'note' => 'Utrzymaj ten nawyk w stałym tempie, a następny miesiąc będzie jeszcze łatwiejszy.',
        'cta' => 'Otwórz opinie',
    ],

    'review_anomaly' => [
        'subject' => 'Uwaga: :count rzecz(y) do sprawdzenia w Twoich opiniach',
        'intro' => 'Zauważyliśmy coś wartego sprawdzenia w Twoich opiniach:',
        'stalled' => 'brak nowych opinii od :days dni, choć zwykle jest aktywna.',
        'negative_streak' => ':count opinii z niską liczbą gwiazdek w ciągu 3 dni. Odpowiedz szybko, aby ograniczyć szkody.',
        'spike' => 'nietypowy skok: :recent opinii w 7 dni (zwykle około :baseline tygodniowo). Świetna wiadomość lub warto sprawdzić pod kątem spamu.',
        'rating_drop' => 'ocena spada: :recent★ ostatnio wobec :prior★ wcześniej.',
        'cta' => 'Otwórz opinie',
    ],

    'invite' => [
        'subject' => 'Zaproszono Cię do dołączenia do :workspace w Repunio',
        'greeting' => 'Cześć,',
        'intro' => ':inviter zaprosił Cię do dołączenia do :workspace w Repunio jako :role.',
        'note' => 'To zaproszenie wygasa za 14 dni. Jeśli się go nie spodziewałeś, możesz zignorować tę wiadomość.',
        'cta' => 'Przyjmij zaproszenie',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Każda opinia, jedna skrzynka',
        'intro' => 'Wszystkie opinie z Twoich lokalizacji trafiają do jednej skrzynki. Filtruj według oceny, lokalizacji lub braku odpowiedzi i odpowiadaj w dwóch kliknięciach.',
        'tip' => 'Wypróbuj teraz: otwórz opinię i naciśnij Wygeneruj z AI. Otrzymasz gotową wersję roboczą w Twoim tonie, którą możesz edytować przed publikacją.',
        'cta' => 'Otwórz swoje opinie',
    ],
    'drip_automation' => [
        'subject' => 'Ustaw odpowiedzi na autopilocie',
        'intro' => 'Utwórz agenta AI, który zna Twoją firmę i ton, a następnie pozwól regułom automatycznych odpowiedzi odpowiadać za Ciebie na rutynowe opinie.',
        'tip' => 'Nie jesteś gotów na pełny autopilot? Skorzystaj z kolejki zatwierdzeń: AI przygotowuje wersję roboczą, a Ty zatwierdzasz jednym kliknięciem.',
        'cta' => 'Skonfiguruj automatyzacje',
    ],
    'drip_growth' => [
        'subject' => 'Zbierz więcej opinii w tym miesiącu',
        'intro' => 'Ustaw miesięczny cel opinii dla każdej lokalizacji, a my śledzimy tempo, świętujemy kamienie milowe i ostrzegamy przed anomaliami.',
        'tip' => 'Utwórz stronę zbierania opinii: krótki link i kod QR, które kierują zadowolonych klientów prosto do formularza opinii Google lub TripAdvisor.',
        'cta' => 'Utwórz swoją stronę opinii',
    ],
    'drip_reports' => [
        'subject' => 'Raporty, które naprawdę są czytane',
        'intro' => 'Zbuduj raport wyników z bloków: wskaźniki KPI, podsumowanie AI, wzmianki o pracownikach, tematy. Pobierz jako PDF lub udostępnij link.',
        'tip' => 'Ustaw raz, wysyłaj co miesiąc: zaplanuj raport, a trafi on do skrzynek automatycznie, po angielsku lub niemiecku.',
        'cta' => 'Zbuduj raport',
    ],
    'drip_team' => [
        'subject' => 'Zaproś swój zespół',
        'intro' => 'Zaproś współpracowników z rolami lub dodaj gości, którzy otrzymują tylko powiadomienia i raporty, bez potrzeby logowania.',
        'tip' => 'Zdecyduj, kto otrzymuje które e-maile w Ustawieniach, a następnie kieruj alerty o nowych opiniach do osób, które się nimi zajmują.',
        'cta' => 'Zaproś swój zespół',
    ],
    'drip_member' => [
        'subject' => 'Poruszanie się po Repunio',
        'intro' => 'Zostałeś dodany do obszaru roboczego. Skrzynka opinii to miejsce, gdzie dzieje się praca: filtruj, odpowiadaj, gotowe.',
        'tip' => 'Ustaw język interfejsu i e-maili w swoim profilu, aby wszystko docierało w wygodnej dla Ciebie formie.',
        'cta' => 'Otwórz Repunio',
    ],
    'drip_unsubscribe' => 'Za dużo wskazówek? :link',
    'drip_unsubscribe_link' => 'Wypisz się z tych e-maili',

    'unsubscribed_title' => 'Zostałeś wypisany',
    'unsubscribed_body' => 'Nie będziesz już otrzymywać wskazówek produktowych i e-maili wdrożeniowych. Ważne wiadomości dotyczące konta i rozliczeń nadal będą docierać. Zmieniłeś zdanie? Włącz je ponownie w :link.',
    'unsubscribed_profile' => 'swoim profilu',
];
