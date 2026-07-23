<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Todavía no hay automatizaciones',
    'empty_desc' => 'Crea una automatización para responder a las reseñas nuevas de forma automática, según la valoración y la ubicación.',
    'empty_cta' => 'Nueva automatización',

    // Table columns
    'col_rating' => 'Valoración',
    'rating_any' => 'cualquiera',
    'col_reply' => 'Respuesta',
    'reply_ai' => 'IA: :agent',
    'reply_default' => 'Mensaje predeterminado',
    'col_mode' => 'Modo',
    'mode_approval' => 'Con aprobación',
    'mode_auto' => 'Publicación automática',
    'col_scope' => 'Alcance',
    'scope_all' => 'Todas las ubicaciones',
    'scope_count' => ':count ubicación(es)',

    // Run action
    'run_now' => 'Ejecutar ahora',
    'run_heading' => 'Ejecutar esta automatización ahora',
    'run_desc' => 'Aplica esta automatización a las reseñas sin responder que encajen. Si quieres, limítala a un periodo por fecha de reseña; deja ambos campos vacíos para incluirlas todas.',
    'run_from' => 'Reseñas desde',
    'run_until' => 'Reseñas hasta',
    'run_title' => 'Se ha ejecutado «:name»',
    'run_queued_title' => '«:name» en cola',
    'run_queued_body' => 'La ejecución ocurre en segundo plano. Los borradores nuevos llegan a Aprobaciones y las respuestas publicadas automáticamente aparecen en las reseñas en los próximos minutos.',
    'run_body' => 'Generadas :generated, publicadas :published, en cola :queued, omitidas :skipped.',

    // Form — Flow section
    'flow_section' => 'Flujo',
    'flow_section_desc' => 'Cuándo se ejecuta la automatización y a qué reseñas se aplica.',
    'trigger' => 'Disparador',
    'trigger_new_review' => 'Reseña nueva en Google',
    'rating_is' => 'La valoración es…',
    'rating_helper' => 'Déjalo todo sin marcar para aplicarla a cualquier valoración.',
    'all_locations' => 'Todas las ubicaciones',
    'locations' => 'Ubicaciones',
    'all_locations_helper' => 'Funciona como comodín: las automatizaciones limitadas a ubicaciones concretas tienen prioridad en esas ubicaciones.',
    'covered_by' => 'ya está en «:name» (:ratings)',
    'any_rating' => 'cualquier valoración',
    'overlap_title' => 'Se solapa con otra automatización',
    'overlap_body' => 'También coincide con las mismas reseñas: :list. Cada reseña la gestiona exactamente una automatización: las ubicaciones concretas ganan a «Todas las ubicaciones» y, si no, se ejecuta la más antigua.',
    'respect_working_hours' => 'Respetar el horario laboral',
    'respect_working_hours_helper' => 'Responder solo durante el horario de apertura de la ubicación.',
    'reply_to_previous' => 'Responder a reseñas anteriores',
    'reply_to_previous_helper' => 'Gestiona también las reseñas antiguas sin responder (cuenta para tu cuota mensual de IA).',
    'approve_before_posting' => 'Aprobar antes de publicar',
    'approve_before_posting_helper' => 'Desactivado = se publica solo en Google. Activado = pasa antes por Aprobaciones.',

    // Form — Timing section
    'timing_section' => 'Tiempos',
    'timing_section_desc' => 'Añade un retardo aleatorio (y, si quieres, un horario laboral) para que las respuestas se publiquen a un ritmo humano y natural, en vez de al instante.',
    'reply_delay_min' => 'Retardo mínimo',
    'reply_delay_max' => 'Retardo máximo',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'Las respuestas se publican tras un retardo aleatorio entre el mínimo y el máximo, para que parezcan naturales. Pon ambos a 0 para publicar al instante.',
    'reply_delay_max_error' => 'El retardo máximo debe ser mayor o igual que el mínimo.',
    'working_days' => 'Días laborables',
    'working_start' => 'Hora de inicio',
    'working_end' => 'Hora de fin',
    'day_mon' => 'Lun',
    'day_tue' => 'Mar',
    'day_wed' => 'Mié',
    'day_thu' => 'Jue',
    'day_fri' => 'Vie',
    'day_sat' => 'Sáb',
    'day_sun' => 'Dom',

    // Form — Content section
    'content_section' => 'Contenido',
    'content_section_desc' => 'Qué respuesta enviar.',
    'content_ai_agent' => 'Agente de IA',
    'content_default_message' => 'Mensaje predeterminado',
    'ai_agent' => 'Agente de IA',
    'default_message' => 'Mensaje predeterminado',
];
