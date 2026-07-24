<?php

declare(strict_types=1);

return [
    'col_name' => 'Naam',
    'col_enabled' => 'Ingeschakeld',
    'name' => 'Naam',
    'enabled' => 'Ingeschakeld',
    // Empty state
    'empty_heading' => 'Nog geen automatiseringen',
    'empty_desc' => 'Stel een automatisering in om automatisch op nieuwe reviews te reageren, op basis van beoordeling en locatie.',
    'empty_cta' => 'Nieuwe automatisering',

    // Table columns
    'col_rating' => 'Beoordeling',
    'rating_any' => 'alle',
    'col_reply' => 'Reactie',
    'reply_ai' => 'AI: :agent',
    'reply_default' => 'Standaardbericht',
    'col_mode' => 'Modus',
    'mode_approval' => 'Met goedkeuring',
    'mode_auto' => 'Automatisch publiceren',
    'col_scope' => 'Bereik',
    'scope_all' => 'Alle locaties',
    'scope_count' => ':count locatie(s)',

    // Run action
    'run_now' => 'Nu uitvoeren',
    'run_heading' => 'Deze automatisering nu uitvoeren',
    'run_desc' => 'Pas deze automatisering toe op onbeantwoorde reviews die overeenkomen. Je kunt dit desgewenst beperken tot een periode op reviewdatum; laat beide velden leeg om alles mee te nemen.',
    'run_from' => 'Reviews vanaf',
    'run_until' => 'Reviews tot',
    'run_title' => '“:name” uitgevoerd',
    'run_queued_title' => '“:name” in de wachtrij',
    'run_queued_body' => 'De uitvoering gebeurt op de achtergrond. Nieuwe concepten verschijnen bij Goedkeuringen en automatisch gepubliceerde reacties verschijnen de komende minuten bij de reviews.',
    'run_body' => 'Gegenereerd :generated, gepubliceerd :published, in wachtrij :queued, overgeslagen :skipped.',

    // Form — Flow section
    'flow_section' => 'Verloop',
    'flow_section_desc' => 'Wanneer de automatisering wordt uitgevoerd en op welke reviews ze van toepassing is.',
    'trigger' => 'Trigger',
    'trigger_new_review' => 'Nieuwe review op Google',
    'rating_is' => 'De beoordeling is…',
    'rating_helper' => 'Laat alles uitgevinkt om op elke beoordeling toe te passen.',
    'all_locations' => 'Alle locaties',
    'locations' => 'Locaties',
    'all_locations_helper' => 'Werkt als vangnet: automatiseringen die beperkt zijn tot specifieke locaties hebben voorrang voor die locaties.',
    'covered_by' => 'zit al in “:name” (:ratings)',
    'any_rating' => 'elke beoordeling',
    'overlap_title' => 'Overlapt met een andere automatisering',
    'overlap_body' => 'Komt ook overeen met dezelfde reviews: :list. Elke review wordt door precies één automatisering afgehandeld: specifieke locaties winnen van "Alle locaties", anders geldt de oudste.',
    'respect_working_hours' => 'Openingstijden respecteren',
    'respect_working_hours_helper' => 'Alleen reageren tijdens de openingstijden van de locatie.',
    'reply_to_previous' => 'Reageren op eerdere reviews',
    'reply_to_previous_helper' => 'Behandel ook bestaande onbeantwoorde reviews (telt mee voor je maandelijkse AI-tegoed).',
    'approve_before_posting' => 'Goedkeuren voor publicatie',
    'approve_before_posting_helper' => 'Uit = automatisch publiceren op Google. Aan = eerst naar Goedkeuringen sturen.',

    // Form — Timing section
    'timing_section' => 'Timing',
    'timing_section_desc' => 'Voeg een willekeurige vertraging toe (en eventueel openingstijden) zodat reacties op een menselijk, organisch tempo worden geplaatst in plaats van meteen.',
    'reply_delay_min' => 'Minimale vertraging',
    'reply_delay_max' => 'Maximale vertraging',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'Reacties worden na een willekeurige vertraging tussen het minimum en maximum geplaatst, zodat ze organisch lijken. Zet beide op 0 om meteen te publiceren.',
    'reply_delay_max_error' => 'De maximale vertraging moet groter dan of gelijk aan de minimale vertraging zijn.',
    'working_days' => 'Werkdagen',
    'working_start' => 'Begintijd',
    'working_end' => 'Eindtijd',
    'day_mon' => 'ma',
    'day_tue' => 'di',
    'day_wed' => 'wo',
    'day_thu' => 'do',
    'day_fri' => 'vr',
    'day_sat' => 'za',
    'day_sun' => 'zo',

    // Form — Content section
    'content_section' => 'Inhoud',
    'content_section_desc' => 'Welke reactie er wordt verstuurd.',
    'content_ai_agent' => 'AI-agent',
    'content_default_message' => 'Standaardbericht',
    'ai_agent' => 'AI-agent',
    'default_message' => 'Standaardbericht',
];
