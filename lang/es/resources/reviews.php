<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Ubicación',
    'col_author' => 'Autor',
    'col_review' => 'Reseña',
    'only_rating' => 'Solo valoración',
    'col_reply' => 'Respuesta',
    'col_status' => 'Estado',
    'col_replied_by' => 'Respondida por',
    'col_date' => 'Fecha',
    'replied_ai' => 'IA',
    'replied_human' => 'Equipo',
    'replied_assistant' => 'Asistente',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— sin respuesta —',
    'status_replied' => 'Respondida',
    'status_pending' => 'Pendiente',
    'status_scheduled' => 'Programada',
    'scheduled_for' => 'Se publica el :datetime',
    'replied_at' => 'Respondida el :datetime',
    'status_failed' => 'Fallida',

    // Filters
    'review_date' => 'Fecha de la reseña',
    'filter_from' => 'Desde :date',
    'filter_to' => 'Hasta :date',
    'reply_status' => 'Estado de la respuesta',
    'review_text' => 'Texto de la reseña',
    'with_text' => 'Con texto',
    'rating_only' => 'Solo estrellas',
    'photos' => 'Fotos',
    'with_photos' => 'Con fotos',
    'without_photos' => 'Sin fotos',

    // Reply action
    'edit_reply' => 'Editar respuesta',
    'save_reply' => 'Guardar',
    'reply' => 'Responder',
    'reply_to_review' => 'Responder a la reseña',
    'no_written_review' => 'Sin texto, solo valoración.',
    'translated_by_google' => '🌐 Traducido por Google',
    'ai_agent' => 'Agente de IA',
    'default_agent' => 'Agente predeterminado',
    'your_reply' => 'Tu respuesta',
    'generate_with_ai' => 'Generar con IA',
    'generate' => 'Generar',
    'generating' => 'Generando tu respuesta…',
    'cancel' => 'Cancelar',
    'add_emoji' => 'Añadir emoji',
    'show_translation' => 'Ver traducción (:language)',
    'translation_label' => 'Traducción (:language)',
    'translation_failed' => 'La traducción ha fallado',
    'hide_emoji' => 'Ocultar emojis',
    'delete_reply' => 'Eliminar respuesta',
    'delete_reply_desc' => 'Esto quita la respuesta de Google. La reseña en sí no se ve afectada.',
    'delete_confirm' => 'Eliminar',
    'submit_heading' => '¿Publicar tu respuesta?',
    'submit_desc' => 'Esto publica tu respuesta de forma pública en Google, visible para todo el que vea la reseña.',
    'submit_confirm' => 'Publicar',

    // AI cost hints
    'cost_generic' => 'Esto genera una respuesta con IA.',
    'cost_all_used' => 'Has agotado tus respuestas con IA de este mes. Recarga un paquete, cambia de plan o escribe la respuesta a mano.',
    'cost_credit' => 'Esto consume 1 crédito (te quedan :count).',
    'cost_monthly' => 'Esto consume 1 de tus respuestas con IA del mes, te quedan :count.',

    // Notifications
    'reply_deleted' => 'Respuesta eliminada',
    'no_changes' => 'No hay cambios que guardar',
    'reply_published' => 'Respuesta publicada',
    'reply_failed' => 'No se ha podido publicar la respuesta',
    'ai_limit_reached' => 'Límite de IA alcanzado',
    'ai_limit_body' => 'Has agotado las respuestas con IA de este mes. Edita a mano o cambia de plan para tener un límite mayor.',
    'generation_failed' => 'Fallo al generar',
    'reply_generated' => 'Respuesta generada',
    'retry' => 'Reintentar',
    'retry_heading' => '¿Reintentar esta respuesta?',
    'retry_desc' => 'Lo intentaremos de nuevo: volver a publicar el borrador, o regenerarlo si falló el paso de IA.',
    'retry_queued' => 'Respuesta puesta de nuevo en cola',
    'retry_nothing' => 'No hay nada que reintentar. Responde a mano.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Todas',
    'tab_needs_approval' => 'Requieren aprobación',
    'tab_scheduled' => 'Programadas',
    'tab_published' => 'Publicadas',
    'tab_failed' => 'Fallidas',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Mostrando 1 reseña de tu notificación por correo|[2,*] Mostrando :count reseñas de tu notificación por correo',
    'from_email_clear' => 'Ver todas las reseñas',

    // i18n label backfill
    'col_rating' => 'Valoración',
];
