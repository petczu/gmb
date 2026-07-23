<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Los webhooks son una función Pro',
    'pro_only_body' => 'Recibe un HTTP POST firmado en cuanto entra una reseña, se publica una respuesta, se alcanza un objetivo o se detecta una anomalía. Cambia a Pro para añadir endpoints.',
    'see_plans' => 'Ver planes',

    'intro' => 'Enviamos por POST un payload JSON firmado a tu endpoint en cada evento suscrito, con reintentos. Verifica la cabecera X-Webhook-Signature con el secreto de tu endpoint.',

    'docs_link' => 'Documentación de webhooks',
    'empty' => 'Todavía no hay endpoints de webhook.',
    'col_url' => 'URL',
    'col_events' => 'Eventos',
    'col_active' => 'Activo',
    'col_last' => 'Último envío',

    'create' => 'Añadir endpoint',
    'create_heading' => 'Añadir endpoint de webhook',
    'edit' => 'Editar',
    'delete' => 'Eliminar',
    'saved' => 'Endpoint guardado',
    'created' => 'Endpoint añadido',
    'deleted' => 'Endpoint eliminado',

    'field_name' => 'Nombre (opcional)',
    'field_url' => 'URL del endpoint',
    'field_events' => 'Eventos',
    'field_active' => 'Activo',

    'secret' => 'Secreto',
    'secret_heading' => 'Secreto de firma',
    'secret_desc' => 'Úsalo para verificar la firma del payload.',
    'signature_hint' => 'Cada petición va firmada:',

    'deliveries' => 'Envíos',
    'deliveries_heading' => 'Envíos recientes',
    'no_deliveries' => 'Todavía no hay envíos.',
    'attempts' => 'intentos',
    'resend' => 'Reenviar',
    'resent' => 'Envío puesto de nuevo en cola',
    'status_pending' => 'Pendiente',
    'status_success' => 'Entregado',
    'status_failed' => 'Fallido',

    'event_review_created' => 'Reseña nueva',
    'event_reply_published' => 'Respuesta publicada',
    'event_goal_reached' => 'Objetivo alcanzado',
    'event_anomaly_detected' => 'Anomalía detectada',
];
