<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhooks são um recurso Pro',
    'pro_only_body' => 'Receba um POST HTTP assinado no momento em que uma avaliação chega, uma resposta é publicada, uma meta é atingida ou uma anomalia é detectada. Faça upgrade para o Pro para adicionar endpoints.',
    'see_plans' => 'Ver planos',

    'intro' => 'Enviamos por POST um payload JSON assinado ao seu endpoint a cada evento assinado, com novas tentativas. Verifique o cabeçalho X-Webhook-Signature com o segredo do seu endpoint.',

    'docs_link' => 'Documentação dos webhooks',
    'empty' => 'Nenhum endpoint de webhook ainda.',
    'col_url' => 'URL',
    'col_events' => 'Eventos',
    'col_active' => 'Ativo',
    'col_last' => 'Último disparo',

    'create' => 'Adicionar endpoint',
    'create_heading' => 'Adicionar endpoint de webhook',
    'edit' => 'Editar',
    'delete' => 'Excluir',
    'saved' => 'Endpoint salvo',
    'created' => 'Endpoint adicionado',
    'deleted' => 'Endpoint excluído',

    'field_name' => 'Nome (opcional)',
    'field_url' => 'URL do endpoint',
    'field_events' => 'Eventos',
    'field_active' => 'Ativo',

    'secret' => 'Segredo',
    'secret_heading' => 'Segredo de assinatura',
    'secret_desc' => 'Use isto para verificar a assinatura do payload.',
    'signature_hint' => 'Cada requisição é assinada:',

    'deliveries' => 'Entregas',
    'deliveries_heading' => 'Entregas recentes',
    'no_deliveries' => 'Nenhuma entrega ainda.',
    'attempts' => 'tentativas',
    'resend' => 'Reenviar',
    'resent' => 'Entrega recolocada na fila',
    'status_pending' => 'Pendente',
    'status_success' => 'Entregue',
    'status_failed' => 'Falha',

    'event_review_created' => 'Nova avaliação',
    'event_reply_published' => 'Resposta publicada',
    'event_goal_reached' => 'Meta atingida',
    'event_anomaly_detected' => 'Anomalia detectada',
];
