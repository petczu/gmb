<?php

declare(strict_types=1);

return [
    'nav' => 'Bedrijfsinfo',
    'title' => 'Bedrijfsinfo',

    'not_configured_title' => 'Beheer van vermeldingen is niet geconfigureerd',
    'not_configured_body' => 'Stel ZERNIO_API_KEY in de serveromgeving in om Google Bedrijfsprofielen te bewerken.',

    'pick_location' => 'Locatie',
    'status_live' => 'Live op Google',
    'status_suspended' => 'Opgeschort door Google',
    'status_disabled' => 'Uitgeschakeld',
    'status_unverified' => 'Niet geverifieerd',

    'section_basics' => 'Profiel',
    'field_logo' => 'Locatielogo',
    'field_logo_helper' => 'Getoond in het voorbeeld van het Google-bericht. Valt terug op het werkruimtelogo als het leeg is.',
    'field_description' => 'Bedrijfsomschrijving',
    'field_description_helper' => 'Getoond op je Google-profiel. Maximaal 750 tekens. Het formulier laadt de huidige live waarden van Google.',
    'field_phone' => 'Telefoonnummer',
    'field_website' => 'Website',

    'section_hours' => 'Openingstijden',
    'section_hours_desc' => 'Eén rij per tijdvak. Voeg twee rijen toe voor dezelfde dag voor onderbroken tijden (bijv. lunchpauze).',
    'add_hours' => 'Tijdvak toevoegen',
    'field_day' => 'Dag',
    'field_open' => 'Opent',
    'field_close' => 'Sluit',

    'day_monday' => 'Maandag',
    'day_tuesday' => 'Dinsdag',
    'day_wednesday' => 'Woensdag',
    'day_thursday' => 'Donderdag',
    'day_friday' => 'Vrijdag',
    'day_saturday' => 'Zaterdag',
    'day_sunday' => 'Zondag',

    'section_special' => 'Bijzondere openingstijden',
    'section_special_desc' => 'Feestdagen en uitzonderingen: deze overschrijven de reguliere tijden voor de opgegeven data.',

    'section_socials' => 'Socialmediaprofielen',
    'section_socials_desc' => 'Links naar je socialmediaprofielen, getoond op je Google-vermelding. Alleen ingevulde velden worden gepubliceerd; laat een veld leeg om de huidige waarde op Google te behouden.',
    'add_special' => 'Bijzondere openingstijden toevoegen',
    'field_start_date' => 'Van',
    'field_end_date' => 'Tot',
    'field_closed' => 'Gesloten op deze dagen',

    'save' => 'Publiceren naar Google',
    'saved' => 'Profielupdate verzonden naar Google',
    'save_failed' => 'Update mislukt',
    'unmatched' => 'Deze locatie kon nog niet aan een Google-vermelding worden gekoppeld.',

    'field_additional_phones' => 'Extra telefoonnummers',
    'field_additional_phones_placeholder' => 'voeg nummer toe + Enter',
    'field_additional_phones_help' => 'Maximaal twee extra nummers getoond op het profiel.',
    'field_timezone' => 'Tijdzone',
    'field_timezone_helper' => 'De werktijden voor automatische antwoorden worden in deze tijdzone geïnterpreteerd. Automatisch gedetecteerd bij het verbinden; overschrijf hier indien onjuist.',
    'loading_live' => 'De huidige profielgegevens van Google laden…',
];
