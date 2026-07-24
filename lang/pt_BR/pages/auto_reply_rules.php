<?php

declare(strict_types=1);

return [
    'title' => 'Regras de resposta automática por IA',
    'section' => ':stars  ·  avaliações de :rating estrelas',
    'enabled' => 'Resposta automática ativada',
    'mode' => 'Modo',
    'mode_auto' => 'Publicação automática',
    'mode_draft' => 'Rascunho para aprovação',
    'tone' => 'Tom / modelo',
    'tone_placeholder_positive' => 'ex.: Caloroso e agradecido.',
    'tone_placeholder_negative' => 'ex.: Pedir desculpas e propor uma solução.',
    'instruction' => 'Instrução extra (opcional)',
    'language' => 'Idioma',
    'language_placeholder' => 'Detectar automaticamente pela avaliação',
    'save_rules' => 'Salvar regras',
    'rules_saved' => 'Regras de resposta automática salvas',

    // Blade intro
    'intro' => 'Configure como a IA responde a cada classificação por estrelas. <strong>Publicação automática</strong> envia a resposta ao Google imediatamente; <strong>Rascunho para aprovação</strong> a coloca primeiro na fila de Aprovações. Cada geração é descontada da cota mensal de IA do seu plano.',
];
