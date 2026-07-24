<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Sede',
    'col_author' => 'Autore',
    'col_review' => 'Recensione',
    'only_rating' => 'Solo valutazione',
    'col_reply' => 'Risposta',
    'col_status' => 'Stato',
    'col_replied_by' => 'Risposto da',
    'col_date' => 'Data',
    'replied_ai' => 'IA',
    'replied_human' => 'Team',
    'replied_assistant' => 'Assistente',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— nessuna risposta —',
    'status_replied' => 'Risposto',
    'status_pending' => 'In attesa',
    'status_scheduled' => 'Programmato',
    'scheduled_for' => 'Pubblicazione il :datetime',
    'replied_at' => 'Risposto il :datetime',
    'status_failed' => 'Non riuscito',

    // Filters
    'review_date' => 'Data della recensione',
    'filter_from' => 'Dal :date',
    'filter_to' => 'Al :date',
    'reply_status' => 'Stato della risposta',
    'review_text' => 'Testo della recensione',
    'with_text' => 'Con testo',
    'rating_only' => 'Solo valutazione',
    'photos' => 'Foto',
    'with_photos' => 'Con foto',
    'without_photos' => 'Senza foto',

    // Reply action
    'edit_reply' => 'Modifica risposta',
    'save_reply' => 'Salva',
    'reply' => 'Rispondi',
    'reply_to_review' => 'Rispondi alla recensione',
    'no_written_review' => 'Nessun testo, solo una valutazione.',
    'translated_by_google' => '🌐 Tradotto da Google',
    'ai_agent' => 'Agente IA',
    'default_agent' => 'Agente predefinito',
    'your_reply' => 'La tua risposta',
    'generate_with_ai' => 'Genera con l’IA',
    'generate' => 'Genera',
    'generating' => 'Generazione della tua risposta…',
    'cancel' => 'Annulla',
    'add_emoji' => 'Aggiungi emoji',
    'show_translation' => 'Mostra la traduzione (:language)',
    'translation_label' => 'Traduzione (:language)',
    'translation_failed' => 'Traduzione non riuscita',
    'hide_emoji' => 'Nascondi emoji',
    'delete_reply' => 'Elimina risposta',
    'delete_reply_desc' => 'Questo rimuove la risposta da Google. La recensione stessa non viene modificata.',
    'delete_confirm' => 'Elimina',
    'submit_heading' => 'Pubblicare la tua risposta?',
    'submit_desc' => 'Questo pubblica la tua risposta pubblicamente su Google, visibile a chiunque veda la recensione.',
    'submit_confirm' => 'Pubblica',

    // AI cost hints
    'cost_generic' => 'Questo genera una risposta con l’IA.',
    'cost_all_used' => 'Hai usato tutte le tue risposte IA di questo mese. Ricarica un pacchetto, passa a un piano superiore oppure scrivi la risposta manualmente.',
    'cost_credit' => 'Questo consuma 1 credito (:count rimanenti).',
    'cost_monthly' => 'Questo consuma 1 delle tue risposte IA mensili, :count rimanenti.',

    // Notifications
    'reply_deleted' => 'Risposta eliminata',
    'no_changes' => 'Nessuna modifica da salvare',
    'reply_published' => 'Risposta pubblicata',
    'reply_failed' => 'Non è stato possibile pubblicare la risposta',
    'ai_limit_reached' => 'Limite IA raggiunto',
    'ai_limit_body' => 'Hai usato tutte le risposte IA di questo mese. Modifica manualmente oppure passa a un piano superiore per un limite più alto.',
    'generation_failed' => 'Generazione non riuscita',
    'reply_generated' => 'Risposta generata',
    'retry' => 'Riprova',
    'retry_heading' => 'Riprovare questa risposta?',
    'retry_desc' => 'Riproveremo: ripubblicare la bozza, oppure rigenerarla se il passaggio IA non è riuscito.',
    'retry_queued' => 'Risposta rimessa in coda',
    'retry_nothing' => 'Niente da riprovare. Rispondi invece manualmente.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Tutte',
    'tab_needs_approval' => 'Da approvare',
    'tab_scheduled' => 'Programmate',
    'tab_published' => 'Pubblicate',
    'tab_failed' => 'Non riuscite',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Viene mostrata 1 recensione dalla tua notifica e-mail|[2,*] Vengono mostrate :count recensioni dalla tua notifica e-mail',
    'from_email_clear' => 'Mostra tutte le recensioni',
];
