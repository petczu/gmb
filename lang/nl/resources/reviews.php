<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Locatie',
    'col_author' => 'Auteur',
    'col_review' => 'Review',
    'only_rating' => 'Alleen beoordeling',
    'col_reply' => 'Reactie',
    'col_status' => 'Status',
    'col_replied_by' => 'Beantwoord door',
    'col_date' => 'Datum',
    'replied_ai' => 'AI',
    'replied_human' => 'Team',
    'replied_assistant' => 'Assistent',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— geen reactie —',
    'status_replied' => 'Beantwoord',
    'status_pending' => 'In behandeling',
    'status_scheduled' => 'Ingepland',
    'scheduled_for' => 'Wordt geplaatst op :datetime',
    'replied_at' => 'Beantwoord op :datetime',
    'status_failed' => 'Mislukt',

    // Filters
    'review_date' => 'Reviewdatum',
    'filter_from' => 'Vanaf :date',
    'filter_to' => 'Tot :date',
    'reply_status' => 'Reactiestatus',
    'review_text' => 'Reviewtekst',
    'with_text' => 'Met tekst',
    'rating_only' => 'Alleen beoordeling',
    'photos' => "Foto's",
    'with_photos' => "Met foto's",
    'without_photos' => "Zonder foto's",

    // Reply action
    'edit_reply' => 'Reactie bewerken',
    'save_reply' => 'Opslaan',
    'reply' => 'Reageren',
    'reply_to_review' => 'Reageren op review',
    'no_written_review' => 'Geen tekst, alleen een beoordeling.',
    'translated_by_google' => '🌐 Vertaald door Google',
    'ai_agent' => 'AI-agent',
    'default_agent' => 'Standaardagent',
    'your_reply' => 'Jouw reactie',
    'generate_with_ai' => 'Genereren met AI',
    'generate' => 'Genereren',
    'generating' => 'Je reactie wordt gegenereerd…',
    'cancel' => 'Annuleren',
    'add_emoji' => 'Emoji toevoegen',
    'show_translation' => 'Vertaling tonen (:language)',
    'translation_label' => 'Vertaling (:language)',
    'translation_failed' => 'Vertaling mislukt',
    'hide_emoji' => "Emoji's verbergen",
    'delete_reply' => 'Reactie verwijderen',
    'delete_reply_desc' => 'Hiermee verwijder je de reactie van Google. De review zelf blijft ongewijzigd.',
    'delete_confirm' => 'Verwijderen',
    'submit_heading' => 'Je reactie publiceren?',
    'submit_desc' => 'Hiermee wordt je reactie openbaar op Google geplaatst, zichtbaar voor iedereen die de review ziet.',
    'submit_confirm' => 'Publiceren',

    // AI cost hints
    'cost_generic' => 'Hiermee genereer je een reactie met AI.',
    'cost_all_used' => 'Je hebt al je AI-reacties deze maand gebruikt. Koop een pakket bij, upgrade of schrijf de reactie handmatig.',
    'cost_credit' => 'Dit kost 1 credit (:count over).',
    'cost_monthly' => 'Dit gebruikt 1 van je maandelijkse AI-reacties, :count over.',

    // Notifications
    'reply_deleted' => 'Reactie verwijderd',
    'no_changes' => 'Geen wijzigingen om op te slaan',
    'reply_published' => 'Reactie gepubliceerd',
    'reply_failed' => 'Reactie kon niet worden geplaatst',
    'ai_limit_reached' => 'AI-limiet bereikt',
    'ai_limit_body' => 'Je hebt al je AI-reacties deze maand gebruikt. Bewerk handmatig of upgrade voor een hogere limiet.',
    'generation_failed' => 'Genereren mislukt',
    'reply_generated' => 'Reactie gegenereerd',
    'retry' => 'Opnieuw proberen',
    'retry_heading' => 'Deze reactie opnieuw proberen?',
    'retry_desc' => 'We proberen het opnieuw: de opgestelde reactie opnieuw plaatsen, of opnieuw genereren als de AI-stap mislukte.',
    'retry_queued' => 'Reactie opnieuw in de wachtrij geplaatst',
    'retry_nothing' => 'Niets om opnieuw te proberen. Reageer in plaats daarvan handmatig.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Alle',
    'tab_needs_approval' => 'Vereist goedkeuring',
    'tab_scheduled' => 'Ingepland',
    'tab_published' => 'Gepubliceerd',
    'tab_failed' => 'Mislukt',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} 1 review uit je e-mailmelding wordt getoond|[2,*] :count reviews uit je e-mailmelding worden getoond',
    'from_email_clear' => 'Alle reviews tonen',
];
