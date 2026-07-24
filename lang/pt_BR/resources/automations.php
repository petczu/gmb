<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nenhuma automação ainda',
    'empty_desc' => 'Configure uma automação para responder novas avaliações automaticamente, por nota e local.',
    'empty_cta' => 'Nova automação',

    // Table columns
    'col_rating' => 'Nota',
    'rating_any' => 'qualquer',
    'col_reply' => 'Resposta',
    'reply_ai' => 'IA: :agent',
    'reply_default' => 'Mensagem padrão',
    'col_mode' => 'Modo',
    'mode_approval' => 'Aprovação',
    'mode_auto' => 'Publicação automática',
    'col_scope' => 'Escopo',
    'scope_all' => 'Todos os locais',
    'scope_count' => ':count local(is)',

    // Run action
    'run_now' => 'Executar agora',
    'run_heading' => 'Executar esta automação agora',
    'run_desc' => 'Aplique esta automação às avaliações sem resposta correspondentes. Opcionalmente, limite a um período por data da avaliação; deixe ambos os campos em branco para incluir todas.',
    'run_from' => 'Avaliações a partir de',
    'run_until' => 'Avaliações até',
    'run_title' => 'Executou ":name"',
    'run_queued_title' => '":name" na fila',
    'run_queued_body' => 'A execução acontece em segundo plano. Novos rascunhos aparecem em Aprovações e as respostas publicadas automaticamente aparecem nas avaliações nos próximos minutos.',
    'run_body' => 'Geradas :generated, publicadas :published, na fila :queued, ignoradas :skipped.',

    // Form — Flow section
    'flow_section' => 'Fluxo',
    'flow_section_desc' => 'Quando a automação é executada e a quais avaliações se aplica.',
    'trigger' => 'Gatilho',
    'trigger_new_review' => 'Nova avaliação no Google',
    'rating_is' => 'A nota é…',
    'rating_helper' => 'Deixe tudo desmarcado para aplicar a qualquer nota.',
    'all_locations' => 'Todos os locais',
    'locations' => 'Locais',
    'all_locations_helper' => 'Funciona como abrangente: automações limitadas a locais específicos têm prioridade para seus locais.',
    'covered_by' => 'já em ":name" (:ratings)',
    'any_rating' => 'qualquer nota',
    'overlap_title' => 'Sobreposição com outra automação',
    'overlap_body' => 'Também corresponde às mesmas avaliações: :list. Cada avaliação é tratada por exatamente uma automação: locais específicos vencem "Todos os locais", caso contrário a mais antiga é executada.',
    'respect_working_hours' => 'Respeitar horário de funcionamento',
    'respect_working_hours_helper' => 'Responder somente durante o horário de funcionamento do local.',
    'reply_to_previous' => 'Responder a avaliações anteriores',
    'reply_to_previous_helper' => 'Também tratar avaliações existentes sem resposta (conta para sua cota mensal de IA).',
    'approve_before_posting' => 'Aprovar antes de publicar',
    'approve_before_posting_helper' => 'Desligado = publicação automática no Google. Ligado = enviar primeiro para Aprovações.',

    // Form — Timing section
    'timing_section' => 'Cronometragem',
    'timing_section_desc' => 'Adicione um atraso aleatório (e horário de funcionamento opcional) para que as respostas sejam publicadas em horários orgânicos e no ritmo humano, em vez de instantaneamente.',
    'reply_delay_min' => 'Atraso mínimo',
    'reply_delay_max' => 'Atraso máximo',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'As respostas são publicadas após um atraso aleatório entre o mínimo e o máximo, para parecerem orgânicas. Defina ambos como 0 para publicar imediatamente.',
    'reply_delay_max_error' => 'O atraso máximo deve ser maior ou igual ao atraso mínimo.',
    'working_days' => 'Dias úteis',
    'working_start' => 'Horário de início',
    'working_end' => 'Horário de término',
    'day_mon' => 'Seg',
    'day_tue' => 'Ter',
    'day_wed' => 'Qua',
    'day_thu' => 'Qui',
    'day_fri' => 'Sex',
    'day_sat' => 'Sáb',
    'day_sun' => 'Dom',

    // Form — Content section
    'content_section' => 'Conteúdo',
    'content_section_desc' => 'Qual resposta enviar.',
    'content_ai_agent' => 'Agente de IA',
    'content_default_message' => 'Mensagem padrão',
    'ai_agent' => 'Agente de IA',
    'default_message' => 'Mensagem padrão',
];
