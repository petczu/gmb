<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Niente da approvare',
    'empty_desc' => 'Quando le automazioni preparano risposte che richiedono approvazione, appaiono qui.',

    // Columns
    'col_location' => 'Sede',
    'col_author' => 'Autore',
    'col_rating' => 'Valutazione',
    'col_review' => 'Recensione',
    'col_ai_reply' => 'Risposta IA',
    'col_status' => 'Stato',
    'col_source' => 'Origine',
    'col_generated' => 'Generata',
    'source_ai' => 'IA',
    'source_template' => 'Modello',

    // Statuses
    'status_pending' => 'In attesa',
    'status_scheduled' => 'Programmata',
    'status_published' => 'Pubblicata',
    'status_skipped' => 'Ignorata',
    'status_failed' => 'Non riuscita',
    'status_indicator' => 'Stato: :status',
    'scheduled_for' => 'Pubblicazione :time',

    // Actions
    'approve' => 'Approva e pubblica',
    'approve_publish' => 'Approva e pubblica',
    'edit_publish' => 'Modifica e pubblica',
    'review_reply' => 'Rivedi e rispondi',
    'reply' => 'Rispondi',
    'reject' => 'Rifiuta',

    // Filters
    'filter_date' => 'Data della recensione',
    'filter_from' => 'Dal :date',
    'filter_to' => 'Fino al :date',

    // Notifications
    'reply_published' => 'Risposta pubblicata',

    'approve_selected' => 'Approva e pubblica la selezione',
    'reject_selected' => 'Rifiuta la selezione',
    'bulk_approve_confirm' => 'Pubblicare su Google tutte le risposte selezionate? Vengono messe in coda e partono automaticamente nei prossimi minuti.',
    'bulk_reject_confirm' => 'Rifiutare tutte le bozze selezionate?',
    'bulk_queued' => ':count risposte in coda per la pubblicazione',
    'bulk_queued_body' => 'Vengono pubblicate automaticamente nei prossimi minuti. Eventuali errori compaiono nel filtro Non riuscite con il motivo.',
    'bulk_rejected' => ':count bozze rifiutate',
    'publish_failed_title' => 'Pubblicazione non riuscita',
    'publish_not_found' => 'Google indica che questa recensione non esiste più. Potrebbe essere stata eliminata dal suo autore, oppure la sede è stata ricollegata a un nuovo account. La bozza è stata contrassegnata come non riuscita.',
    'publish_error' => 'Non è stato possibile pubblicare la risposta. La bozza è stata contrassegnata come non riuscita: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google non ha trovato questa recensione o sede a cui rispondere. Potrebbe essere stata rimossa, oppure le risposte non sono disponibili per questa sede.',
    'error_rate_limited' => 'Google sta limitando la velocità di pubblicazione delle risposte. Verrà ritentato automaticamente.',
    'error_unauthorized' => 'La connessione a Google non è autorizzata a rispondere qui. Ricollega l\'account e riprova.',
    'error_generic' => 'Non è stato possibile pubblicare la risposta. Riprova più tardi.',
    'draft_rejected' => 'Bozza rifiutata',

    // Scheduled items
    'post_now' => 'Pubblica ora',
    'post_now_confirm' => 'La risposta viene pubblicata su Google immediatamente, ignorando l\'orario programmato.',
    'post_now_queued' => 'Risposta in coda per la pubblicazione',
    'post_now_queued_body' => 'Parte entro i prossimi minuti.',
    'cancel_scheduled' => 'Annulla',
    'cancel_scheduled_confirm' => 'Annullare questa risposta programmata? Non verrà pubblicata.',
    'schedule_cancelled' => 'Risposta programmata annullata',

    // List tabs
    'tab_pending' => 'Da approvare',
    'tab_all' => 'Tutte',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Pubblica ora la selezione',
    'bulk_publish_now_confirm' => 'Le risposte selezionate ignorano l\'orario programmato e partono entro i prossimi minuti.',
    'cancel_scheduled_selected' => 'Annulla la programmazione',
];
