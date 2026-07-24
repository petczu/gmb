<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Vul je bedrijfsgegevens in',
    'step_company_hint' => 'Land en factuurgegevens die op facturen en rapporten worden gebruikt.',
    'step_plan_label' => 'Kies een plan',
    'step_plan_hint' => 'Start je gratis proefperiode van 14 dagen, zonder creditcard.',
    'step_location_label' => 'Verbind je eerste locatie',
    'step_location_hint' => 'Koppel een Google Bedrijfsprofiel om reviews op te halen.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Richt je werkruimte in',
    'wiz_plan_done' => '✓ Je plan is actief. Ga verder naar de volgende stap.',
    'wiz_plan_pick' => 'Kies een plan',
    'wiz_interval' => 'Factureringsinterval',
    'wiz_monthly' => 'Maandelijks',
    'wiz_yearly' => 'Jaarlijks',
    'wiz_start_trial' => 'Start gratis proefperiode van 14 dagen',
    'wiz_trial_note' => 'Je gratis proefperiode van 14 dagen begint zodra je verdergaat. Geen creditcard nodig.',
    'wiz_go_checkout' => 'Verder naar afrekenen',
    'wiz_plan_required' => 'Kies een plan en rond het afrekenen af om verder te gaan.',
    'wiz_location_body' => 'Koppel je Google Bedrijfsprofiel zodat we je reviews kunnen ophalen. Je wordt doorgestuurd naar Google om toegang te autoriseren en kiest daarna de locatie die je wilt verbinden.',
    'wiz_connect_google' => 'Google Bedrijfsprofiel verbinden',
    'wiz_skip_location' => 'Nu overslaan',
    'skipped_title' => 'Alles is klaar',
    'skipped_body' => 'Je kunt je Google Bedrijfsprofiel op elk moment verbinden via de pagina Locaties.',
    'wiz_per_location' => 'per locatie / maand',
    'wiz_plan_desc_starter' => 'Reviews-inbox, handmatige reacties en basisrapporten.',
    'wiz_plan_desc_growth' => 'Voegt AI-antwoorden, geplande rapporten en vergelijkingen toe.',
    'wiz_plan_desc_pro' => 'Alles, plus white label, API, MCP en klanttoegang.',

    // Onboarding overlay
    'welcome_title' => 'Welkom, laten we je account instellen',
    'welcome_subtitle' => 'Een paar snelle stappen en je bent klaar om te beginnen.',
    'continue_step' => 'Verder: :label',
    'enter_app' => 'Naar de app →',
    'sign_out' => 'Uitloggen',

    // Pending-deletion overlay
    'deletion_title' => 'Deze werkruimte staat gepland om verwijderd te worden',
    'deletion_body' => 'Alle gegevens worden op <strong>:date</strong> definitief verwijderd. Je kunt dit nog annuleren en je werkruimte behouden.',
    'cancel_deletion' => 'Verwijdering annuleren',

    // Grace banner
    'grace_banner' => '⚠️ We konden je laatste betaling niet verwerken. Je service blijft actief tot <strong>:date</strong>, gelieve je',
    'update_your_billing' => 'factureringsgegevens bij te werken',

    // Paywall overlay
    'payment_problem_title' => 'Er is een probleem met je betaling',
    'needs_plan_title' => 'Kies een plan om te beginnen',
    'payment_problem_body' => 'Je toegang is gepauzeerd omdat we de betaling niet konden verwerken. Werk je factureringsgegevens bij om verder te gaan.',
    'needs_plan_body' => 'Kies een plan om reviews, AI-reacties en rapporten voor je locaties te activeren. Gratis proefperiode van 14 dagen.',
    'update_billing' => 'Facturering bijwerken',
    'view_plans' => 'Plannen bekijken',

    // Connect-select-location page
    'connecting_location' => 'Locatie verbinden…',
    'choose_location' => 'Kies welke Google Bedrijfslocatie je met deze werkruimte wilt verbinden.',
    'could_not_load' => 'Kan locaties niet laden',
    'pending_expired_title' => 'Google-sessie verlopen',
    'pending_expired' => 'De Google-autorisatie is maar korte tijd geldig en deze is verlopen. Maak opnieuw verbinding en kies je locaties nogmaals, het duurt maar even.',
    'reconnect_google' => 'Opnieuw verbinden met Google',
    'back' => 'Terug',
    'no_locations_available' => 'Geen locaties beschikbaar',
    'no_locations_body' => 'Er zijn geen Google Bedrijfslocaties teruggegeven. Ze worden mogelijk nog geladen aan de kant van Google, probeer het zo dadelijk opnieuw.',
    'connect_then_done' => 'Verbind een of meer locaties en klik daarna op Klaar.',
    'done' => 'Klaar',
    'connected' => 'Verbonden',
    'connect' => 'Verbinden',
    'connecting' => 'Verbinden…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Bedrijfslocatie selecteren',
    'connect_failed' => 'Kan locatie niet verbinden',
    'connected_title' => 'Verbonden: :name',
    'connected_body' => 'Reviews worden op de achtergrond gesynchroniseerd, ze verschijnen zo dadelijk op de pagina Locaties.',
    'location_fallback' => 'locatie',
    'trial_started_title' => 'Je proefperiode van 14 dagen is gestart',
    'trial_started_body' => 'Volledige toegang tot :date, geen creditcard nodig. Veel plezier!',
];
