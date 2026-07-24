<?php

declare(strict_types=1);

return [
    'col_name' => 'Naam',
    'col_tone' => 'Toon',
    'name' => 'Naam',
    // Empty state
    'empty_heading' => 'Nog geen AI-agents',
    'empty_desc' => 'Maak een AI-agent aan om reacties op te stellen en je automatische antwoorden aan te sturen in de stem van je merk.',
    'empty_cta' => 'Nieuwe AI-agent',

    // Table
    'col_native_lang' => 'Oorspronkelijke taal',
    'col_default' => 'Standaard',
    'col_updated' => 'Bijgewerkt',
    'test_preview' => 'Testen en voorbeeld',
    'test_heading' => 'Reactie testen',
    'close' => 'Sluiten',
    'no_reviews_to_test' => 'Nog geen reviews om op te testen, synchroniseer eerst een paar reviews.',
    'generation_failed' => 'Genereren mislukt: :error',
    'set_default' => 'Als standaard instellen',

    // Form
    'section' => 'Je AI-agent',
    'section_desc' => 'Geef de agent een naam en beschrijf hoe hij moet reageren. Wordt gebruikt door automatische antwoorden en de knop "opstellen met AI".',
    'describe' => 'Beschrijf je agent',
    'describe_helper' => 'De volledige instructies / persona: hoe de review geclassificeerd moet worden en hoe er gereageerd wordt, toon en stijl, personalisatieregels, enzovoort.',
    'tone' => 'Toon',
    'reply_native' => 'Reageer in de taal van de review',
    'reply_native_helper' => 'De agent reageert in dezelfde taal als de review.',
    'default_agent' => 'Standaardagent',
    'default_agent_helper' => 'Wordt gebruikt wanneer een automatisering geen agent aangeeft.',

    // Knowledge base
    'knowledge' => 'Kennisbank (optioneel)',
    'knowledge_helper' => 'Bedrijfsfeiten die de agent in reacties kan gebruiken: openingstijden, beleid, namen van ruimtes/diensten, aanbiedingen, veelgestelde vragen. Blijft feitelijk en verzint niets daarbuiten.',
    'knowledge_ph' => 'bijv. Open ma–zo 10:00–22:00. Ruimtes: The Heist, Prison Break, Haunted Manor. Groepen van 2–6. Reserveren op example.com of +31 ...',

    // Test panel
    'test_section' => 'Testen op een review',
    'test_section_desc' => 'Kies een echte review en genereer een concept met de huidige (niet-opgeslagen) instellingen, en pas het dan aan.',
    'test_pick_review' => 'Review',
    'test_pick_placeholder' => 'Kies een gesynchroniseerde review…',
    'test_review_text' => 'Review',
    'test_generate' => 'Concept genereren',
    'test_result' => 'Gegenereerd concept',
    'test_need_review' => 'Kies eerst een review om op te testen.',

    // AI description generator
    'generate_label' => 'Genereren met AI',
    'generate_heading' => 'De beschrijving met AI genereren',
    'generate_desc' => 'Voeg je website en/of een paar woorden over het bedrijf toe, en AI stelt de agentinstructies voor je op. Je kunt het resultaat daarna aanpassen.',
    'generate_submit' => 'Genereren',
    'generate_url' => 'Website-URL',
    'generate_notes' => 'Iets toe te voegen (optioneel)',
    'generate_notes_ph' => 'bijv. Italiaans familierestaurant, nadruk op vriendelijke service, vermeld ons terras in de zomer',
    'generate_need_input' => 'Voeg eerst een website-URL of een korte beschrijving toe.',
    'generate_rate_limited' => 'Te veel generaties. Wacht even en probeer het opnieuw.',
    'generate_done' => 'Beschrijving gegenereerd, controleer en pas ze naar wens aan.',
    'generate_failed' => 'Kon de beschrijving niet genereren. Probeer het opnieuw of schrijf ze handmatig.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Gedeelde regels',
    'shared_rules_heading' => 'Gedeelde reactieregels',
    'shared_rules_desc' => 'Deze regels gelden bovenop elke agent, in elke AI-reactie. Perfect voor stijlcorrecties die je nooit per agent wilt herhalen.',
    'shared_rules_placeholder' => "bijv.\nSchrijf in Nederlandse reacties \"ruimte\" of \"escape room\", nooit \"Room\" als Nederlands zelfstandig naamwoord.\nBeloof nooit kortingen of terugbetalingen.\nOnderteken reacties zonder naam.",
    'shared_rules_save' => 'Regels opslaan',
    'shared_rules_saved' => 'Gedeelde regels opgeslagen',

    // i18n label backfill (batch 2)
    'preview_reply' => 'Reactie van agent',
    'anonymous' => 'Anoniem',
];
