<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Restaurante de demonstração',
    'demo_period' => 'Relatório de desempenho · últimos 30 dias',
    'demo_five_star' => 'Percentual de 5 estrelas',
    'demo_summary_label' => 'Resumo executivo',
    'demo_summary' => 'O Restaurante de demonstração recebeu 38 avaliações nos últimos 30 dias (+9 em relação ao período anterior), com média de 4,60★. 84% das avaliações foram positivas e a taxa de resposta chegou a 92%. Os clientes elogiaram repetidamente a simpatia da equipe e a agilidade do atendimento.',

    'location' => 'Local',
    'business_multi' => ':name + :count outros',
    'compare' => 'Comparar',
    'compare_options' => [
        'none' => 'Não comparar',
        'previous' => 'Período anterior',
        'custom' => 'Intervalo personalizado…',
    ],
    'compare_from' => 'Comparar de',
    'compare_to' => 'Comparar até',
    'report_language' => 'Idioma do relatório',

    'content_section' => 'Conteúdo do relatório',
    'content_section_desc' => 'Escolha uma predefinição e depois ajuste quais blocos aparecem no relatório.',
    'preset' => 'Predefinição',
    'blocks' => 'Blocos',
    'competitors_block_hint' => 'Nenhum concorrente monitorado ainda. Adicione-os primeiro em Fichas > Concorrentes.',
    'ai_instructions' => 'Instruções para a IA',
    'ai_instructions_help' => 'Orientações opcionais para o texto gerado pela IA. Muito úteis para os nomes da equipe: liste seu time e os apelidos para que as menções sejam atribuídas à pessoa certa. Salvas uma vez e aplicadas a todos os relatórios futuros, incluindo os agendados.',
    'ai_instructions_placeholder' => 'Nossa equipe: Eva, Alette, Suleyman (também escrito Suly), Lisa. Unifique os apelidos com o nome completo.',
    'ai_improve' => 'Melhorar com IA',
    'ai_improve_empty' => 'Escreva algumas anotações primeiro e depois melhore-as.',
    'ai_improve_rate_limited' => 'Tentativas em excesso, tente novamente mais tarde.',
    'ai_improve_done' => 'Instruções melhoradas',
    'ai_improve_failed' => 'Não foi possível melhorar as instruções, tente novamente.',

    'schedule_report' => 'Enviar de forma agendada',
    'schedule_heading' => 'Agendar este relatório',
    'schedule_desc' => 'A seleção atual (período, local, comparação, blocos) será enviada por e-mail em PDF de forma recorrente.',
    'schedule_submit' => 'Criar agendamento',
    'schedule_created' => 'Agendamento criado',
    'schedule_created_body' => 'Gerencie em Relatórios → Relatórios agendados.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'Restam :left de :cap relatórios de IA neste mês',

    // Generate modal
    'generate_heading' => 'Gerar relatório com IA?',
    'generate_desc' => 'Gere o resumo executivo por IA para a seleção atual.',
    'generate_desc_left' => 'Isso consome 1 dos seus relatórios de IA mensais, restam :left.',
    'generate_submit' => 'Gerar',

    // Generate notifications
    'report_generated' => 'Relatório gerado',
    'report_generated_body' => 'O resumo por IA está pronto e a prévia foi atualizada. Use Baixar para salvar o PDF.',
    'limit_reached' => 'Limite mensal de relatórios atingido',
    'limit_reached_body' => 'Exibindo um relatório básico sem IA. Faça upgrade para um limite mensal maior.',

    // Blade view
    'generate_report' => 'Gerar relatório',
    'generating' => 'Gerando…',
    'download_pdf' => 'Baixar PDF',
    'download_first_tooltip' => 'Gere o relatório primeiro',
    'building' => 'Criando o relatório…',
    'preview_title' => 'Prévia do relatório',
];
