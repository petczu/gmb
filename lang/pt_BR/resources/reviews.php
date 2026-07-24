<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Local',
    'col_author' => 'Autor',
    'col_review' => 'Avaliação',
    'only_rating' => 'Apenas nota',
    'col_reply' => 'Resposta',
    'col_status' => 'Status',
    'col_replied_by' => 'Respondido por',
    'col_date' => 'Data',
    'replied_ai' => 'IA',
    'replied_human' => 'Equipe',
    'replied_assistant' => 'Assistente',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— sem resposta —',
    'status_replied' => 'Respondida',
    'status_pending' => 'Pendente',
    'status_scheduled' => 'Agendada',
    'scheduled_for' => 'Publica em :datetime',
    'replied_at' => 'Respondida em :datetime',
    'status_failed' => 'Falhou',

    // Filters
    'review_date' => 'Data da avaliação',
    'filter_from' => 'De :date',
    'filter_to' => 'Até :date',
    'reply_status' => 'Status da resposta',
    'review_text' => 'Texto da avaliação',
    'with_text' => 'Com texto',
    'rating_only' => 'Apenas nota',
    'photos' => 'Fotos',
    'with_photos' => 'Com fotos',
    'without_photos' => 'Sem fotos',

    // Reply action
    'edit_reply' => 'Editar resposta',
    'save_reply' => 'Salvar',
    'reply' => 'Responder',
    'reply_to_review' => 'Responder à avaliação',
    'no_written_review' => 'Sem texto, apenas nota.',
    'translated_by_google' => '🌐 Traduzido pelo Google',
    'ai_agent' => 'Agente de IA',
    'default_agent' => 'Agente padrão',
    'your_reply' => 'Sua resposta',
    'generate_with_ai' => 'Gerar com IA',
    'generate' => 'Gerar',
    'generating' => 'Gerando sua resposta…',
    'cancel' => 'Cancelar',
    'add_emoji' => 'Adicionar emoji',
    'show_translation' => 'Mostrar tradução (:language)',
    'translation_label' => 'Tradução (:language)',
    'translation_failed' => 'Falha na tradução',
    'hide_emoji' => 'Ocultar emojis',
    'delete_reply' => 'Excluir resposta',
    'delete_reply_desc' => 'Isso remove a resposta do Google. A avaliação em si não é afetada.',
    'delete_confirm' => 'Excluir',
    'submit_heading' => 'Publicar sua resposta?',
    'submit_desc' => 'Isso publica sua resposta publicamente no Google, visível a todos que veem a avaliação.',
    'submit_confirm' => 'Publicar',

    // AI cost hints
    'cost_generic' => 'Isso gera uma resposta com IA.',
    'cost_all_used' => 'Você usou todas as suas respostas com IA deste mês. Recarregue um pacote, faça upgrade ou escreva a resposta manualmente.',
    'cost_credit' => 'Isso usa 1 crédito (:count restantes).',
    'cost_monthly' => 'Isso usa 1 das suas respostas com IA mensais, :count restantes.',

    // Notifications
    'reply_deleted' => 'Resposta excluída',
    'no_changes' => 'Nenhuma alteração para salvar',
    'reply_published' => 'Resposta publicada',
    'reply_failed' => 'Não foi possível publicar a resposta',
    'ai_limit_reached' => 'Limite de IA atingido',
    'ai_limit_body' => 'Você usou todas as respostas com IA deste mês. Edite manualmente ou faça upgrade para um limite maior.',
    'generation_failed' => 'Falha ao gerar',
    'reply_generated' => 'Resposta gerada',
    'retry' => 'Tentar novamente',
    'retry_heading' => 'Tentar esta resposta novamente?',
    'retry_desc' => 'Vamos tentar de novo: republicar o rascunho da resposta, ou regerá-lo se a etapa de IA falhou.',
    'retry_queued' => 'Resposta colocada na fila novamente',
    'retry_nothing' => 'Nada para tentar novamente. Responda manualmente.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Todas',
    'tab_needs_approval' => 'Requer aprovação',
    'tab_scheduled' => 'Agendadas',
    'tab_published' => 'Publicadas',
    'tab_failed' => 'Com falha',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Exibindo 1 avaliação da sua notificação por e-mail|[2,*] Exibindo :count avaliações da sua notificação por e-mail',
    'from_email_clear' => 'Ver todas as avaliações',
];
