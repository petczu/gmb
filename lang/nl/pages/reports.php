<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Demo Restaurant',
    'demo_period' => 'Prestatierapport · afgelopen 30 dagen',
    'demo_five_star' => 'Aandeel 5 sterren',
    'demo_summary_label' => 'Managementsamenvatting',
    'demo_summary' => 'Demo Restaurant ontving de afgelopen 30 dagen 38 reviews (+9 ten opzichte van de vorige periode), met een gemiddelde van 4,60★. 84% van de reviews was positief en het reactiepercentage bereikte 92%. Gasten prezen herhaaldelijk het vriendelijke team en de snelle service.',

    'location' => 'Locatie',
    'business_multi' => ':name + :count meer',
    'compare' => 'Vergelijken',
    'compare_options' => [
        'none' => 'Niet vergelijken',
        'previous' => 'Vorige periode',
        'custom' => 'Aangepaste periode…',
    ],
    'compare_from' => 'Vergelijken vanaf',
    'compare_to' => 'Vergelijken tot',
    'report_language' => 'Rapporttaal',

    'content_section' => 'Rapportinhoud',
    'content_section_desc' => 'Kies een voorinstelling en bepaal daarna welke blokken in het rapport verschijnen.',
    'preset' => 'Voorinstelling',
    'blocks' => 'Blokken',
    'competitors_block_hint' => 'Nog geen concurrenten gevolgd. Voeg ze eerst toe onder Vermeldingen > Concurrenten.',
    'ai_instructions' => 'AI-instructies',
    'ai_instructions_help' => 'Optionele aanwijzingen voor het AI-verhaal. Vooral handig voor namen van medewerkers: noem je team en eventuele bijnamen zodat vermeldingen aan de juiste persoon worden gekoppeld. Wordt eenmalig opgeslagen en toegepast op elk toekomstig rapport, ook geplande.',
    'ai_instructions_placeholder' => 'Onze medewerkers: Eva, Alette, Suleyman (ook geschreven als Suly), Lisa. Voeg bijnamen samen tot de volledige naam.',
    'ai_improve' => 'Verbeteren met AI',
    'ai_improve_empty' => 'Schrijf eerst een paar notities en verbeter ze daarna.',
    'ai_improve_rate_limited' => 'Te veel pogingen, probeer het later opnieuw.',
    'ai_improve_done' => 'Instructies verbeterd',
    'ai_improve_failed' => 'Kon de instructies niet verbeteren, probeer het opnieuw.',

    'schedule_report' => 'Volgens schema versturen',
    'schedule_heading' => 'Dit rapport plannen',
    'schedule_desc' => 'De huidige selectie (periode, locatie, vergelijking, blokken) wordt volgens een terugkerend schema als PDF gemaild.',
    'schedule_submit' => 'Schema aanmaken',
    'schedule_created' => 'Schema aangemaakt',
    'schedule_created_body' => 'Beheer het onder Rapporten → Geplande rapporten.',

    // Usage line ("N of M AI reports left this month")
    'usage' => ':left van :cap AI-rapporten resterend deze maand',

    // Generate modal
    'generate_heading' => 'AI-rapport genereren?',
    'generate_desc' => 'Genereer de AI-managementsamenvatting voor de huidige selectie.',
    'generate_desc_left' => 'Dit gebruikt 1 van je maandelijkse AI-rapporten, :left resterend.',
    'generate_submit' => 'Genereren',

    // Generate notifications
    'report_generated' => 'Rapport gegenereerd',
    'report_generated_body' => 'AI-samenvatting is klaar, het voorbeeld is bijgewerkt. Gebruik Downloaden om de PDF op te slaan.',
    'limit_reached' => 'Maandelijkse rapportlimiet bereikt',
    'limit_reached_body' => 'Er wordt een basisrapport zonder AI getoond. Upgrade voor een hogere maandelijkse limiet.',

    // Blade view
    'generate_report' => 'Rapport genereren',
    'generating' => 'Genereren…',
    'download_pdf' => 'PDF downloaden',
    'download_first_tooltip' => 'Genereer eerst het rapport',
    'building' => 'Rapport samenstellen…',
    'preview_title' => 'Rapportvoorbeeld',
];
