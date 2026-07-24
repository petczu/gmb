<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nenhum agente de IA ainda',
    'empty_desc' => 'Crie um agente de IA para redigir respostas e alimentar suas automações de resposta automática na voz da sua marca.',
    'empty_cta' => 'Novo agente de IA',

    // Table
    'col_native_lang' => 'Idioma nativo',
    'col_default' => 'Padrão',
    'col_updated' => 'Atualizado',
    'test_preview' => 'Testar e visualizar',
    'test_heading' => 'Testar resposta',
    'close' => 'Fechar',
    'no_reviews_to_test' => 'Ainda não há avaliações para testar, sincronize algumas avaliações primeiro.',
    'generation_failed' => 'Falha ao gerar: :error',
    'set_default' => 'Definir como padrão',

    // Form
    'section' => 'Seu agente de IA',
    'section_desc' => 'Dê um nome ao agente e descreva como ele deve responder. Usado pelas automações de resposta automática e pelo botão "redigir com IA".',
    'describe' => 'Descreva seu agente',
    'describe_helper' => 'As instruções / persona completas, como classificar a avaliação e como responder, tom e estilo, regras de personalização, etc.',
    'tone' => 'Tom de voz',
    'reply_native' => 'Responder no idioma da avaliação',
    'reply_native_helper' => 'O agente responde no mesmo idioma da avaliação.',
    'default_agent' => 'Agente padrão',
    'default_agent_helper' => 'Usado quando uma automação não especifica um agente.',

    // Knowledge base
    'knowledge' => 'Base de conhecimento (opcional)',
    'knowledge_helper' => 'Fatos sobre a empresa que o agente pode usar nas respostas: horário de funcionamento, políticas, nomes de salas/serviços, ofertas, perguntas frequentes. Mantido factual, nunca inventado além disso.',
    'knowledge_ph' => 'ex.: Aberto de seg. a dom., das 10h às 22h. Salas: The Heist, Prison Break, Haunted Manor. Grupos de 2 a 6. Reservas em example.com ou +43 ...',

    // Test panel
    'test_section' => 'Testar em uma avaliação',
    'test_section_desc' => 'Escolha uma avaliação real e gere um rascunho com as configurações atuais (não salvas), depois ajuste.',
    'test_pick_review' => 'Avaliação',
    'test_pick_placeholder' => 'Escolha uma avaliação sincronizada…',
    'test_review_text' => 'Avaliação',
    'test_generate' => 'Gerar rascunho',
    'test_result' => 'Rascunho gerado',
    'test_need_review' => 'Escolha uma avaliação para testar primeiro.',

    // AI description generator
    'generate_label' => 'Gerar com IA',
    'generate_heading' => 'Gerar a descrição com IA',
    'generate_desc' => 'Adicione seu site e/ou algumas palavras sobre a empresa, e a IA vai redigir as instruções do agente para você. Você pode editar o resultado depois.',
    'generate_submit' => 'Gerar',
    'generate_url' => 'URL do site',
    'generate_notes' => 'Algo a acrescentar (opcional)',
    'generate_notes_ph' => 'ex.: restaurante italiano familiar, foco no atendimento acolhedor, mencionar nosso terraço no verão',
    'generate_need_input' => 'Adicione a URL de um site ou uma breve descrição primeiro.',
    'generate_rate_limited' => 'Muitas gerações. Aguarde um pouco e tente de novo.',
    'generate_done' => 'Descrição gerada, revise e ajuste conforme necessário.',
    'generate_failed' => 'Não foi possível gerar a descrição. Tente novamente ou escreva manualmente.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Regras compartilhadas',
    'shared_rules_heading' => 'Regras de resposta compartilhadas',
    'shared_rules_desc' => 'Essas regras se aplicam a todos os agentes, em cada resposta de IA. Perfeito para correções de estilo que você nunca quer repetir por agente.',
    'shared_rules_placeholder' => "ex.:\nEm respostas em alemão, diga \"Raum\" ou \"Escape Room\", nunca \"Room\" como substantivo alemão.\nNunca prometa descontos ou reembolsos.\nAssine as respostas sem um nome.",
    'shared_rules_save' => 'Salvar regras',
    'shared_rules_saved' => 'Regras compartilhadas salvas',
    'col_name' => 'Nome',
    'col_tone' => 'Tom',
    'name' => 'Nome',

    // i18n label backfill (batch 2)
    'preview_reply' => 'Resposta do agente',
    'anonymous' => 'Anônimo',
];
