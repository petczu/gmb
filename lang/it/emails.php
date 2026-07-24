<?php

declare(strict_types=1);

return [
    'greeting' => 'Ciao :name,',
    'signoff' => 'Grazie,',
    'team' => 'Il team di Repunio',

    'drip_competitors' => [
        'subject' => 'Sai come sta andando l’attività qui accanto?',
        'intro' => 'Le tue recensioni sono sotto controllo. La domanda successiva che si pone ogni titolare: sono in vantaggio sulla concorrenza o sto restando indietro? Repunio può monitorarlo per te, con la valutazione e il numero di recensioni giornalieri di qualsiasi attività su Google.',
        'tip' => 'Bastano due minuti: apri Concorrenti, cerca il nome, aggiungilo. Da quel momento vedrai chi prende il vantaggio, di quanto, e se la tua valutazione tiene il passo.',
        'cta' => 'Aggiungi il tuo primo concorrente',
    ],

    'location_connected' => [
        'subject' => ':location è collegata',
        'intro' => 'La tua sede :location è ora collegata. Stiamo importando le sue recensioni da Google in questo momento; a seconda di quante sono, può richiedere qualche minuto.',
        'note' => 'Riceverai un’altra e-mail non appena le recensioni saranno arrivate.',
        'cta' => 'Vedi le sedi',
    ],

    'location_synced' => [
        'subject' => 'Le tue recensioni sono arrivate',
        'intro' => 'La prima importazione è terminata. Ecco cosa è arrivato:',
        'note' => 'D’ora in poi le nuove recensioni arrivano automaticamente e vi si applicano le tue regole di automazione.',
        'cta' => 'Apri la casella delle recensioni',
    ],

    'drip_connect' => [
        'subject' => 'Il tuo account è pronto. Manca un passaggio',
        'intro' => 'Il tuo spazio di lavoro Repunio è configurato, ma è ancora vuoto: recensioni, valutazioni e report provengono tutti dal tuo profilo dell’attività su Google, e nessuno è ancora collegato.',
        'tip' => 'Ci vogliono circa due minuti: apri Sedi, clicca su Collega, accedi con Google e scegli la tua attività. Le tue recensioni iniziano subito ad arrivare.',
        'cta' => 'Collega la tua sede',
    ],

    'signup_code' => [
        'subject' => ':code è il tuo codice di registrazione a Repunio',
        'intro' => 'Inserisci questo codice nella pagina di registrazione per confermare il tuo indirizzo e-mail:',
        'note' => 'Il codice è valido per :minutes minuti. Se non l’hai richiesto tu, puoi ignorare questa e-mail in tutta sicurezza.',
    ],

    'beta_received' => [
        'subject' => 'Grazie! La tua richiesta di accesso è registrata',
        'intro' => 'Grazie per esserti registrato! Repunio è attualmente in beta privata e attiviamo i nuovi account in piccole ondate.',
        'note' => 'Ti scriveremo non appena il tuo accesso sarà pronto. Per ora non c’è altro da fare.',
    ],

    'beta_approved' => [
        'subject' => 'Il tuo accesso a Repunio è pronto',
        'intro' => 'Buone notizie: il tuo account è stato attivato. Ora puoi accedere e configurare tutto.',
        'note' => 'Inizia collegando il tuo profilo dell’attività su Google: le tue recensioni vengono importate in pochi minuti.',
        'cta' => 'Apri Repunio',
    ],

    'welcome' => [
        'subject' => 'Benvenuto in Repunio',
        'intro' => 'Il tuo account è pronto. Repunio ti aiuta a raccogliere le tue recensioni Google, a rispondere e a rendicontarle, tutto in un unico posto.',
        'next' => 'Poi: collega la tua prima sede e scegli un piano per avviare la tua prova gratuita di 14 giorni.',
        'cta' => 'Apri Repunio',
    ],

    'trial_ending' => [
        'subject' => 'La tua prova gratuita termina tra :days giorni',
        'intro' => 'La tua prova gratuita di Repunio termina il :date. Aggiungi ora un metodo di pagamento perché nulla si interrompa: le tue recensioni continuano a sincronizzarsi e le risposte IA a funzionare.',
        'note' => 'Non ti verrà addebitato nulla prima della fine della prova e puoi annullare in qualsiasi momento.',
        'cta' => 'Aggiungi un metodo di pagamento',
    ],

    'payment_succeeded' => [
        'subject' => 'Pagamento ricevuto',
        'intro' => 'Abbiamo ricevuto il tuo pagamento di :amount. Il tuo abbonamento a Repunio è attivo.',
        'cta' => 'Vedi la fatturazione',
    ],

    'payment_failed' => [
        'subject' => 'Pagamento non riuscito, azione necessaria',
        'intro' => 'Non siamo riusciti a elaborare il tuo ultimo pagamento. Il tuo account resta attivo per :days giorni; aggiorna i dati di fatturazione per evitare interruzioni.',
        'cta' => 'Aggiorna la fatturazione',
    ],

    'subscription_canceled' => [
        'subject' => 'Il tuo abbonamento è impostato per essere annullato',
        'intro' => 'Il tuo abbonamento a Repunio è stato annullato. Mantieni l’accesso completo fino al :date, dopodiché non verrà rinnovato.',
        'note' => 'Hai cambiato idea? Puoi riprenderlo in qualsiasi momento prima di allora, senza costi.',
        'cta' => 'Riprendi l’abbonamento',
    ],

    'subscription_resumed' => [
        'subject' => 'Il tuo abbonamento è di nuovo attivo',
        'intro' => 'Il tuo abbonamento a Repunio è stato ripreso e continuerà a rinnovarsi normalmente. Non c’è altro da fare.',
        'cta' => 'Vedi la fatturazione',
    ],

    'ai_limit' => [
        'subject' => 'Hai usato tutte le tue risposte IA di questo mese',
        'intro' => 'Hai raggiunto il limite mensile di risposte IA del piano :plan. Passa a un piano superiore per un limite più alto, oppure continua a rispondere manualmente fino al mese prossimo.',
        'cta' => 'Vedi i piani',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Ricarica IA non riuscita',
        'intro' => 'Abbiamo provato a ricaricare automaticamente le tue risposte IA, ma il pagamento non è andato a buon fine. Aggiorna la tua carta perché la ricarica automatica continui a funzionare.',
        'cta' => 'Aggiorna la fatturazione',
    ],

    'new_reviews' => [
        'subject' => ':count nuova/e recensione/i per la tua attività',
        'intro' => 'Hai :count nuova/e recensione/i per :location.',
        'col_author' => 'Autore',
        'col_rating' => 'Valutazione',
        'col_location' => 'Sede',
        'col_review' => 'Recensione',
        'cta' => 'Vedi le recensioni',
    ],

    'account_disconnected' => [
        'subject' => 'Azione necessaria: la tua connessione con Google ha smesso di funzionare',
        'intro' => 'La connessione con Google di ":account" ha smesso di funzionare, quindi le tue recensioni non si sincronizzano più.',
        'detail' => 'Ricollega l’account per riprendere la sincronizzazione delle recensioni e la pubblicazione delle risposte.',
        'cta' => 'Ricollega',
    ],

    'sync_restored' => [
        'subject' => 'La tua connessione con Google è ripristinata',
        'intro' => 'Buone notizie: la connessione di ":account" è ripristinata e la sincronizzazione è ripresa. Le tue recensioni sono di nuovo aggiornate.',
        'cta' => 'Apri Repunio',
    ],

    'negative_review' => [
        'subject' => 'Una recensione da :rating★ richiede la tua attenzione',
        'intro' => 'Una nuova recensione per :business richiede la tua attenzione.',
        'col_author' => 'Autore',
        'col_rating' => 'Valutazione',
        'col_review' => 'Recensione',
        'cta' => 'Rispondi ora',
    ],

    'reply_failed' => [
        'subject' => 'Non siamo riusciti a pubblicare la tua risposta',
        'intro' => 'Abbiamo provato a pubblicare una risposta a una recensione per :business, ma non è riuscito.',
        'col_author' => 'Autore',
        'col_review' => 'Recensione',
        'detail' => 'Prova a pubblicare di nuovo la risposta dall’app.',
        'detail_retry' => 'Sembra un problema temporaneo, quindi riproveremo automaticamente a pubblicarla nelle prossime ore. Non serve fare nulla. Se continua a non riuscire, la troverai in Recensioni → Non riuscite.',
        'detail_not_found' => 'Google indica che questa recensione non esiste più. Potrebbe essere stata eliminata dal suo autore o filtrata da Google. Nulla da fare: la bozza è stata messa da parte e non verrà riprovata.',
        'detail_unauthorized' => 'La connessione con Google non è autorizzata a rispondere per questa sede, quindi non continueremo a riprovare. Ricollega l’account, poi pubblica di nuovo la risposta dall’app.',
        'cta' => 'Apri le approvazioni',
    ],

    'post_failed' => [
        'subject' => 'Non siamo riusciti a pubblicare il tuo post Google',
        'intro' => 'Abbiamo provato a pubblicare un post Google per :business, ma non è riuscito. Il post è nel tuo calendario con l’errore.',
        'detail' => 'Prova a pubblicare di nuovo il post dall’app.',
        'detail_reason' => 'Motivo: :reason',
        'cta' => 'Apri i post',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies in attesa di approvazione',
        'intro' => 'Hai :count :replies in attesa della tua approvazione. Controllale e approvale perché vengano pubblicate.',
        'reply_word' => '{1}risposta|[2,*]risposte',
        'reply_label' => 'Risposta proposta',
        'cta' => 'Controlla le approvazioni',
    ],

    'review_goal' => [
        'subject_mid' => 'Il tuo obiettivo recensioni: come sta andando il mese',
        'subject_recap' => 'Riepilogo recensioni per :month',
        'intro_mid_ahead' => 'Ottimo ritmo! Hai :actual nuove recensioni questo mese, sopra le :expected attese a questo punto (obiettivo :goal). Continua così.',
        'intro_mid_on_track' => 'Sei in linea: :actual nuove recensioni questo mese, proprio intorno alle :expected attese a questo punto (obiettivo :goal).',
        'intro_mid_behind' => 'Un piccolo promemoria: hai :actual nuove recensioni questo mese, sotto le :expected attese a questo punto (obiettivo :goal). Una piccola spinta aiuta.',
        'intro_recap' => 'Ecco come si è concluso :month: :actual nuove recensioni a fronte di un obiettivo di :goal.',
        'col_location' => 'Sede',
        'col_goal' => 'Obiettivo',
        'col_so_far' => 'Finora',
        'col_projected' => 'Proiezione',
        'col_pace' => 'Ritmo',
        'col_got' => 'Ottenute',
        'col_vs_goal' => 'vs obiettivo',
        'col_vs_prev' => 'vs mese scorso',
        'status_ahead' => 'In vantaggio',
        'status_on_track' => 'In linea',
        'status_behind' => 'In ritardo',
        'cta' => 'Vedi le recensioni',
    ],

    'coaching' => [
        'subject' => 'Il tuo obiettivo recensioni: teniamo il ritmo',
        'intro_almost' => 'Ci sei quasi! Ti mancano solo :remaining per raggiungere il tuo obiettivo di :goal questo mese. Ce la fai!',
        'intro_behind' => 'Sei a :actual su :goal questo mese. Uno sforzo costante questa settimana ti rimette in ritmo. Ecco qualche idea.',
        'intro_on_track' => 'Ottimo lavoro! :actual su :goal e proprio in ritmo. Qualche richiesta questa settimana mantiene lo slancio.',
        'intro_ahead' => 'Che slancio! :actual su :goal, in anticipo sul piano. Continua così con queste idee.',
        'steady' => 'Un consiglio: distribuisci le richieste nell’arco dei giorni. Un’improvvisa ondata di recensioni appare sospetta a Google e può essere filtrata. La costanza premia.',
        'cta' => 'Apri le recensioni',
    ],

    'goal_reached' => [
        'subject' => 'Obiettivo centrato! :goal recensioni questo mese! 🎉',
        'intro' => 'Congratulazioni! Hai raggiunto il tuo obiettivo di :goal nuove recensioni questo mese! È un vero slancio per la tua reputazione.',
        'note' => 'Mantieni questa abitudine a un ritmo costante e il mese prossimo sarà ancora più facile.',
        'cta' => 'Apri le recensioni',
    ],

    'review_anomaly' => [
        'subject' => 'Attenzione: :count cosa/e da controllare sulle tue recensioni',
        'intro' => 'Abbiamo notato qualcosa che merita un’occhiata sulle tue recensioni:',
        'stalled' => 'nessuna nuova recensione da :days giorni, anche se di solito è attiva.',
        'negative_streak' => ':count recensioni con poche stelle in 3 giorni. Rispondi in fretta per limitare i danni.',
        'spike' => 'picco insolito: :recent recensioni in 7 giorni (di solito circa :baseline a settimana). Buona notizia, oppure da verificare per lo spam.',
        'rating_drop' => 'la valutazione sta calando: :recent★ di recente contro :prior★ prima.',
        'cta' => 'Apri le recensioni',
    ],

    'invite' => [
        'subject' => 'Sei stato invitato a unirti a :workspace su Repunio',
        'greeting' => 'Ciao,',
        'intro' => ':inviter ti ha invitato a unirti a :workspace su Repunio come :role.',
        'note' => 'Questo invito scade tra 14 giorni. Se non te lo aspettavi, puoi ignorare questa e-mail.',
        'cta' => 'Accetta l’invito',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Tutte le recensioni, un’unica casella',
        'intro' => 'Tutte le recensioni delle tue sedi arrivano in un’unica casella. Filtra per valutazione, sede o senza risposta, e rispondi in due clic.',
        'tip' => 'Provalo subito: apri una recensione e premi Genera con l’IA. Ottieni una bozza pronta, nel tuo tono, che puoi modificare prima di pubblicare.',
        'cta' => 'Apri le tue recensioni',
    ],
    'drip_automation' => [
        'subject' => 'Metti le risposte in pilota automatico',
        'intro' => 'Crea un agente IA che conosce la tua attività e il tuo tono, poi lascia che le regole di risposta automatica rispondano al posto tuo alle recensioni di routine.',
        'tip' => 'Non ancora pronto per il pilota automatico completo? Usa la coda di approvazione: l’IA scrive la bozza, tu approvi con un clic.',
        'cta' => 'Configura le automazioni',
    ],
    'drip_growth' => [
        'subject' => 'Raccogli più recensioni questo mese',
        'intro' => 'Imposta un obiettivo mensile di recensioni per sede e noi monitoriamo il ritmo, celebriamo i traguardi e ti avvisiamo in caso di anomalie.',
        'tip' => 'Crea la tua pagina di raccolta recensioni: un link breve e un QR code che indirizzano i clienti soddisfatti direttamente al tuo modulo di recensione Google o TripAdvisor.',
        'cta' => 'Crea la tua pagina di recensioni',
    ],
    'drip_reports' => [
        'subject' => 'Report che vengono davvero letti',
        'intro' => 'Componi un report delle prestazioni a partire da blocchi: KPI, riepilogo IA, menzioni del personale, temi. Scaricalo in PDF o condividi un link.',
        'tip' => 'Impostalo una volta, invialo ogni mese: programma il report e arriverà automaticamente nelle caselle, in inglese o in tedesco.',
        'cta' => 'Crea un report',
    ],
    'drip_team' => [
        'subject' => 'Coinvolgi il tuo team',
        'intro' => 'Invita i colleghi con dei ruoli, oppure aggiungi ospiti che ricevono solo notifiche e report, senza bisogno di accesso.',
        'tip' => 'Decidi chi riceve quale e-mail in Impostazioni, poi indirizza gli avvisi di nuove recensioni verso le persone che le gestiscono.',
        'cta' => 'Invita il tuo team',
    ],
    'drip_member' => [
        'subject' => 'Orientarsi in Repunio',
        'intro' => 'Sei stato aggiunto a uno spazio di lavoro. La casella delle recensioni è dove si svolge il lavoro: filtra, rispondi, fatto.',
        'tip' => 'Imposta la lingua dell’interfaccia e delle e-mail nel tuo profilo così tutto arriva come preferisci.',
        'cta' => 'Apri Repunio',
    ],
    'drip_unsubscribe' => 'Troppi consigli? :link',
    'drip_unsubscribe_link' => 'Annulla l’iscrizione a queste e-mail',

    'unsubscribed_title' => 'Iscrizione annullata',
    'unsubscribed_body' => 'Non riceverai più i consigli sul prodotto e le e-mail di introduzione. Le e-mail importanti su account e fatturazione continuano ad arrivare. Hai cambiato idea? Riattivale in :link.',
    'unsubscribed_profile' => 'il tuo profilo',
];
