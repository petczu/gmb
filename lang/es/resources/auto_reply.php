<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'No hay nada que aprobar',
    'empty_desc' => 'Cuando las automatizaciones redacten respuestas que necesiten aprobación, aparecerán aquí.',

    // Columns
    'col_location' => 'Ubicación',
    'col_author' => 'Autor',
    'col_rating' => 'Valoración',
    'col_review' => 'Reseña',
    'col_ai_reply' => 'Respuesta de IA',
    'col_status' => 'Estado',
    'col_source' => 'Origen',
    'col_generated' => 'Generada',
    'source_ai' => 'IA',
    'source_template' => 'Plantilla',

    // Statuses
    'status_pending' => 'Pendiente',
    'status_scheduled' => 'Programada',
    'status_published' => 'Publicada',
    'status_skipped' => 'Omitida',
    'status_failed' => 'Fallida',
    'status_indicator' => 'Estado: :status',
    'scheduled_for' => 'Se publica :time',

    // Actions
    'approve' => 'Aprobar y publicar',
    'approve_publish' => 'Aprobar y publicar',
    'edit_publish' => 'Editar y publicar',
    'review_reply' => 'Revisar y responder',
    'reply' => 'Responder',
    'reject' => 'Rechazar',

    // Filters
    'filter_date' => 'Fecha de la reseña',
    'filter_from' => 'Desde :date',
    'filter_to' => 'Hasta :date',

    // Notifications
    'reply_published' => 'Respuesta publicada',

    'approve_selected' => 'Aprobar y publicar la selección',
    'reject_selected' => 'Rechazar la selección',
    'bulk_approve_confirm' => '¿Publicar en Google todas las respuestas seleccionadas? Se ponen en cola y salen automáticamente en los próximos minutos.',
    'bulk_reject_confirm' => '¿Rechazar todos los borradores seleccionados?',
    'bulk_queued' => ':count respuestas en cola para publicarse',
    'bulk_queued_body' => 'Se publican automáticamente en los próximos minutos. Si alguna falla, aparecerá en el filtro Fallidas con el motivo.',
    'bulk_rejected' => ':count borradores rechazados',
    'publish_failed_title' => 'Fallo al publicar',
    'publish_not_found' => 'Google dice que esta reseña ya no existe. Puede que su autor la haya borrado, o que la ubicación se haya reconectado con otra cuenta. El borrador se ha marcado como fallido.',
    'publish_error' => 'No se ha podido publicar la respuesta. El borrador se ha marcado como fallido: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google no ha encontrado esta reseña o ubicación para responder. Puede que se haya eliminado, o que esta ubicación no admita respuestas.',
    'error_rate_limited' => 'Google está limitando la velocidad de publicación de respuestas. Se reintentará automáticamente.',
    'error_unauthorized' => 'La conexión con Google no tiene permiso para responder aquí. Vuelve a conectar la cuenta e inténtalo de nuevo.',
    'error_generic' => 'No se ha podido publicar la respuesta. Inténtalo de nuevo más tarde.',
    'draft_rejected' => 'Borrador rechazado',

    // Scheduled items
    'post_now' => 'Publicar ahora',
    'post_now_confirm' => 'La respuesta se publica en Google al instante, saltándose su hora programada.',
    'post_now_queued' => 'Respuesta en cola para publicarse',
    'post_now_queued_body' => 'Saldrá en los próximos minutos.',
    'cancel_scheduled' => 'Cancelar',
    'cancel_scheduled_confirm' => '¿Cancelar esta respuesta programada? No se publicará.',
    'schedule_cancelled' => 'Respuesta programada cancelada',

    // List tabs
    'tab_pending' => 'Requieren aprobación',
    'tab_all' => 'Todas',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Publicar ahora la selección',
    'bulk_publish_now_confirm' => 'Las respuestas seleccionadas se saltan su hora programada y salen en los próximos minutos.',
    'cancel_scheduled_selected' => 'Cancelar la programación',
];
