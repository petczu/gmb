<?php

declare(strict_types=1);

return [
    'title' => 'Regole di risposta automatica AI',
    'section' => ':stars  ·  Recensioni da :rating stelle',
    'enabled' => 'Risposta automatica attiva',
    'mode' => 'Modalità',
    'mode_auto' => 'Pubblicazione automatica',
    'mode_draft' => 'Bozza da approvare',
    'tone' => 'Tono / modello',
    'tone_placeholder_positive' => 'es. Caloroso e riconoscente.',
    'tone_placeholder_negative' => 'es. Scusati e proponi di rimediare.',
    'instruction' => 'Istruzione aggiuntiva (facoltativa)',
    'language' => 'Lingua',
    'language_placeholder' => 'Rilevamento automatico dalla recensione',
    'save_rules' => 'Salva le regole',
    'rules_saved' => 'Regole di risposta automatica salvate',

    // Blade intro
    'intro' => 'Configura come l\'AI risponde a ogni valutazione a stelle. <strong>Pubblicazione automatica</strong> pubblica la risposta su Google immediatamente; <strong>Bozza da approvare</strong> la invia prima alla coda di approvazione. Ogni generazione viene conteggiata nel limite mensile di AI del tuo piano.',
];
