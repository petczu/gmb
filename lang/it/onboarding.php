<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Inserisci i dati della tua azienda',
    'step_company_hint' => 'Paese e informazioni di fatturazione usati su fatture e report.',
    'step_plan_label' => 'Scegli un piano',
    'step_plan_hint' => 'Avvia la prova gratuita di 14 giorni, senza carta.',
    'step_location_label' => 'Collega la tua prima sede',
    'step_location_hint' => 'Collega una scheda Google Business Profile per iniziare a importare le recensioni.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Configura il tuo spazio di lavoro',
    'wiz_plan_done' => '✓ Il tuo piano è attivo. Passa allo step successivo.',
    'wiz_plan_pick' => 'Scegli un piano',
    'wiz_interval' => 'Periodicità di fatturazione',
    'wiz_monthly' => 'Mensile',
    'wiz_yearly' => 'Annuale',
    'wiz_start_trial' => 'Avvia la prova gratuita di 14 giorni',
    'wiz_trial_note' => 'La tua prova gratuita di 14 giorni inizia appena continui. Nessuna carta richiesta.',
    'wiz_go_checkout' => 'Continua verso il pagamento',
    'wiz_plan_required' => 'Scegli un piano e completa il pagamento per continuare.',
    'wiz_location_body' => 'Collega la tua scheda Google Business Profile così possiamo importare le tue recensioni. Verrai reindirizzato a Google per autorizzare l\'accesso, poi sceglierai la sede da collegare.',
    'wiz_connect_google' => 'Collega Google Business Profile',
    'wiz_skip_location' => 'Salta per ora',
    'skipped_title' => 'È tutto pronto',
    'skipped_body' => 'Puoi collegare la tua scheda Google Business Profile in qualsiasi momento dalla pagina Sedi.',
    'wiz_per_location' => 'per sede / mese',
    'wiz_plan_desc_starter' => 'Casella delle recensioni, risposte manuali e report di base.',
    'wiz_plan_desc_growth' => 'Aggiunge le risposte automatiche con IA, i report programmati e i confronti.',
    'wiz_plan_desc_pro' => 'Tutto, più white label, API, MCP e accesso per i clienti.',

    // Onboarding overlay
    'welcome_title' => 'Benvenuto, configuriamo il tuo account',
    'welcome_subtitle' => 'Pochi passaggi rapidi e sei pronto.',
    'continue_step' => 'Continua: :label',
    'enter_app' => 'Entra nell\'app →',
    'sign_out' => 'Esci',

    // Pending-deletion overlay
    'deletion_title' => 'L\'eliminazione di questo spazio di lavoro è programmata',
    'deletion_body' => 'Tutti i dati verranno eliminati definitivamente il <strong>:date</strong>. Puoi ancora annullare e conservare il tuo spazio di lavoro.',
    'cancel_deletion' => 'Annulla eliminazione',

    // Grace banner
    'grace_banner' => '⚠️ Non siamo riusciti a elaborare il tuo ultimo pagamento. Il tuo servizio resta attivo fino al <strong>:date</strong>, ti preghiamo di',
    'update_your_billing' => 'aggiornare la fatturazione',

    // Paywall overlay
    'payment_problem_title' => 'C\'è un problema con il tuo pagamento',
    'needs_plan_title' => 'Scegli un piano per iniziare',
    'payment_problem_body' => 'Il tuo accesso è sospeso perché non siamo riusciti a elaborare il pagamento. Aggiorna la fatturazione per continuare.',
    'needs_plan_body' => 'Scegli un piano per attivare recensioni, risposte IA e report per le tue sedi. Prova gratuita di 14 giorni.',
    'update_billing' => 'Aggiorna fatturazione',
    'view_plans' => 'Vedi i piani',

    // Connect-select-location page
    'connecting_location' => 'Collegamento della sede…',
    'choose_location' => 'Scegli quale sede Google Business collegare a questo spazio di lavoro.',
    'could_not_load' => 'Impossibile caricare le sedi',
    'pending_expired_title' => 'Sessione Google scaduta',
    'pending_expired' => 'L\'autorizzazione Google è valida solo per poco tempo e questa è scaduta. Ricollegati e scegli di nuovo le tue sedi, ci vuole solo un istante.',
    'reconnect_google' => 'Ricollega Google',
    'back' => 'Indietro',
    'no_locations_available' => 'Nessuna sede disponibile',
    'no_locations_body' => 'Nessuna sede Google Business è stata restituita. Potrebbero essere ancora in caricamento lato Google, riprova tra poco.',
    'connect_then_done' => 'Collega una o più sedi, poi clicca su Fine.',
    'done' => 'Fine',
    'connected' => 'Collegata',
    'connect' => 'Collega',
    'connecting' => 'Collegamento…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Seleziona la sede',
    'connect_failed' => 'Impossibile collegare la sede',
    'connected_title' => 'Collegata: :name',
    'connected_body' => 'Le recensioni si stanno sincronizzando in background, appariranno a breve nella pagina Sedi.',
    'location_fallback' => 'sede',
    'trial_started_title' => 'La tua prova di 14 giorni è iniziata',
    'trial_started_body' => 'Accesso completo fino al :date, senza carta. Buona esplorazione!',
];
