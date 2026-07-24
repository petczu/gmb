<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Ancora nessun agente IA',
    'empty_desc' => 'Crea un agente IA per redigere le risposte e alimentare le tue automazioni di risposta automatica con la voce del tuo brand.',
    'empty_cta' => 'Nuovo agente IA',

    // Table
    'col_native_lang' => 'Lingua nativa',
    'col_default' => 'Predefinito',
    'col_updated' => 'Aggiornato',
    'test_preview' => 'Prova e anteprima',
    'test_heading' => 'Prova la risposta',
    'close' => 'Chiudi',
    'no_reviews_to_test' => 'Ancora nessuna recensione su cui fare prove, sincronizzane prima qualcuna.',
    'generation_failed' => 'Generazione non riuscita: :error',
    'set_default' => 'Imposta come predefinito',

    // Form
    'section' => 'Il tuo agente IA',
    'section_desc' => 'Dai un nome all’agente e descrivi come deve rispondere. Usato dalle automazioni di risposta automatica e dal pulsante «redigi con l’IA».',
    'describe' => 'Descrivi il tuo agente',
    'describe_helper' => 'Le istruzioni complete / la personalità, come classificare la recensione e come rispondere, tono e stile, regole di personalizzazione, ecc.',
    'tone' => 'Tono di voce',
    'reply_native' => 'Rispondi nella lingua della recensione',
    'reply_native_helper' => 'L’agente risponde nella stessa lingua della recensione.',
    'default_agent' => 'Agente predefinito',
    'default_agent_helper' => 'Usato quando un’automazione non specifica un agente.',

    // Knowledge base
    'knowledge' => 'Base di conoscenza (facoltativa)',
    'knowledge_helper' => 'Le informazioni sull’attività che l’agente può usare nelle risposte: orari di apertura, regole, nomi di sale o servizi, offerte, domande frequenti. Si attiene ai fatti e non inventa nulla oltre a questo.',
    'knowledge_ph' => 'es. Aperto da lun a dom 10:00–22:00. Sale: The Heist, Prison Break, Haunted Manor. Gruppi di 2–6. Prenotazioni su example.com o al +43 ...',

    // Test panel
    'test_section' => 'Prova su una recensione',
    'test_section_desc' => 'Scegli una recensione reale e genera una bozza con le impostazioni attuali (non salvate), poi perfezionala.',
    'test_pick_review' => 'Recensione',
    'test_pick_placeholder' => 'Scegli una recensione sincronizzata…',
    'test_review_text' => 'Recensione',
    'test_generate' => 'Genera bozza',
    'test_result' => 'Bozza generata',
    'test_need_review' => 'Scegli prima una recensione su cui fare la prova.',

    // AI description generator
    'generate_label' => 'Genera con l’IA',
    'generate_heading' => 'Genera la descrizione con l’IA',
    'generate_desc' => 'Aggiungi il tuo sito web e/o qualche parola sull’attività e l’IA redigerà le istruzioni dell’agente al posto tuo. Potrai modificare il risultato in seguito.',
    'generate_submit' => 'Genera',
    'generate_url' => 'URL del sito web',
    'generate_notes' => 'Qualcosa da aggiungere (facoltativo)',
    'generate_notes_ph' => 'es. ristorante italiano a conduzione familiare, accento sul servizio cordiale, menzionare la terrazza in estate',
    'generate_need_input' => 'Aggiungi prima l’URL di un sito o una breve descrizione.',
    'generate_rate_limited' => 'Troppe generazioni. Attendi un momento e riprova.',
    'generate_done' => 'Descrizione generata, rileggila e perfezionala come preferisci.',
    'generate_failed' => 'Impossibile generare la descrizione. Riprova o scrivila manualmente.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Regole comuni',
    'shared_rules_heading' => 'Regole di risposta comuni',
    'shared_rules_desc' => 'Queste regole si applicano a tutti gli agenti, in ogni risposta IA. Perfette per le correzioni di stile che non vuoi ripetere agente per agente.',
    'shared_rules_placeholder' => "es.\nNelle risposte in tedesco usa «Raum» o «Escape Room», mai «Room» come sostantivo tedesco.\nNon promettere mai sconti o rimborsi.\nFirma le risposte senza un nome.",
    'shared_rules_save' => 'Salva le regole',
    'shared_rules_saved' => 'Regole comuni salvate',
];
