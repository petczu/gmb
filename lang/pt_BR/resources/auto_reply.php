<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nada para aprovar',
    'empty_desc' => 'Quando as automações criam rascunhos de respostas que precisam de aprovação, eles aparecem aqui.',

    // Columns
    'col_location' => 'Local',
    'col_author' => 'Autor',
    'col_rating' => 'Nota',
    'col_review' => 'Avaliação',
    'col_ai_reply' => 'Resposta da IA',
    'col_status' => 'Status',
    'col_source' => 'Origem',
    'col_generated' => 'Gerada',
    'source_ai' => 'IA',
    'source_template' => 'Modelo',

    // Statuses
    'status_pending' => 'Pendente',
    'status_scheduled' => 'Agendada',
    'status_published' => 'Publicada',
    'status_skipped' => 'Ignorada',
    'status_failed' => 'Falhou',
    'status_indicator' => 'Status: :status',
    'scheduled_for' => 'Publica :time',

    // Actions
    'approve' => 'Aprovar e publicar',
    'approve_publish' => 'Aprovar e publicar',
    'edit_publish' => 'Editar e publicar',
    'review_reply' => 'Revisar e responder',
    'reply' => 'Responder',
    'reject' => 'Rejeitar',

    // Filters
    'filter_date' => 'Data da avaliação',
    'filter_from' => 'De :date',
    'filter_to' => 'Até :date',

    // Notifications
    'reply_published' => 'Resposta publicada',

    'approve_selected' => 'Aprovar e publicar selecionadas',
    'reject_selected' => 'Rejeitar selecionadas',
    'bulk_approve_confirm' => 'Publicar todas as respostas selecionadas no Google? Elas entram na fila e são publicadas automaticamente nos próximos minutos.',
    'bulk_reject_confirm' => 'Rejeitar todos os rascunhos selecionados?',
    'bulk_queued' => ':count respostas na fila para publicação',
    'bulk_queued_body' => 'Elas são publicadas automaticamente nos próximos minutos. Qualquer falha aparece no filtro Com falha com o motivo.',
    'bulk_rejected' => ':count rascunhos rejeitados',
    'publish_failed_title' => 'Falha na publicação',
    'publish_not_found' => 'O Google informa que esta avaliação não existe mais. Ela pode ter sido excluída pelo autor, ou o local foi reconectado em uma nova conta. O rascunho foi marcado como falho.',
    'publish_error' => 'Não foi possível publicar a resposta. O rascunho foi marcado como falho: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'O Google não encontrou esta avaliação ou local para responder. Pode ter sido removido, ou respostas não estão disponíveis para este local.',
    'error_rate_limited' => 'O Google está limitando a velocidade de publicação das respostas. Será tentado novamente de forma automática.',
    'error_unauthorized' => 'A conexão com o Google não está autorizada a responder aqui. Reconecte a conta e tente de novo.',
    'error_generic' => 'Não foi possível publicar a resposta. Tente novamente mais tarde.',
    'draft_rejected' => 'Rascunho rejeitado',

    // Scheduled items
    'post_now' => 'Publicar agora',
    'post_now_confirm' => 'A resposta é publicada no Google imediatamente, ignorando seu horário agendado.',
    'post_now_queued' => 'Resposta na fila para publicação',
    'post_now_queued_body' => 'Ela é publicada nos próximos minutos.',
    'cancel_scheduled' => 'Cancelar',
    'cancel_scheduled_confirm' => 'Cancelar esta resposta agendada? Ela não será publicada.',
    'schedule_cancelled' => 'Resposta agendada cancelada',

    // List tabs
    'tab_pending' => 'Requer aprovação',
    'tab_all' => 'Todas',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Publicar selecionadas agora',
    'bulk_publish_now_confirm' => 'As respostas selecionadas ignoram o horário agendado e são publicadas nos próximos minutos.',
    'cancel_scheduled_selected' => 'Cancelar agendamento',
];
