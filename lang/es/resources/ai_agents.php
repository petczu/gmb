<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Todavía no hay agentes de IA',
    'empty_desc' => 'Crea un agente de IA para redactar respuestas y alimentar tus automatizaciones con la voz de tu marca.',
    'empty_cta' => 'Nuevo agente de IA',

    // Table
    'col_native_lang' => 'Idioma original',
    'col_default' => 'Predeterminado',
    'col_updated' => 'Actualizado',
    'test_preview' => 'Probar y previsualizar',
    'test_heading' => 'Probar respuesta',
    'close' => 'Cerrar',
    'no_reviews_to_test' => 'Todavía no hay reseñas con las que probar, sincroniza algunas primero.',
    'generation_failed' => 'Fallo al generar: :error',
    'set_default' => 'Marcar como predeterminado',

    // Form
    'section' => 'Tu agente de IA',
    'section_desc' => 'Ponle nombre al agente y describe cómo debe responder. Lo usan las automatizaciones de respuesta y el botón «redactar con IA».',
    'describe' => 'Describe tu agente',
    'describe_helper' => 'Las instrucciones completas: cómo clasificar la reseña y cómo responder, tono y estilo, reglas de personalización, etc.',
    'tone' => 'Tono de voz',
    'reply_native' => 'Responder en el idioma de la reseña',
    'reply_native_helper' => 'El agente responde en el mismo idioma en que está escrita la reseña.',
    'default_agent' => 'Agente predeterminado',
    'default_agent_helper' => 'Se usa cuando una automatización no indica un agente.',

    // Knowledge base
    'knowledge' => 'Base de conocimiento (opcional)',
    'knowledge_helper' => 'Datos del negocio que el agente puede usar en las respuestas: horarios, políticas, nombres de salas o servicios, ofertas, preguntas frecuentes. Se ciñe a los hechos y nunca inventa más allá de esto.',
    'knowledge_ph' => 'p. ej. Abierto de lunes a domingo de 10:00 a 22:00. Salas: El Atraco, Fuga de Prisión, La Mansión. Grupos de 2 a 6. Reservas en example.com o +34 ...',

    // Test panel
    'test_section' => 'Probar con una reseña',
    'test_section_desc' => 'Elige una reseña real y genera un borrador con los ajustes actuales (sin guardar), y luego afina.',
    'test_pick_review' => 'Reseña',
    'test_pick_placeholder' => 'Elige una reseña sincronizada…',
    'test_review_text' => 'Reseña',
    'test_generate' => 'Generar borrador',
    'test_result' => 'Borrador generado',
    'test_need_review' => 'Elige primero una reseña con la que probar.',

    // AI description generator
    'generate_label' => 'Generar con IA',
    'generate_heading' => 'Generar la descripción con IA',
    'generate_desc' => 'Añade tu web o unas palabras sobre el negocio y la IA redactará las instrucciones del agente. Después puedes editar el resultado.',
    'generate_submit' => 'Generar',
    'generate_url' => 'URL de la web',
    'generate_notes' => 'Algo que añadir (opcional)',
    'generate_notes_ph' => 'p. ej. restaurante italiano familiar, énfasis en el trato cercano, mencionar la terraza en verano',
    'generate_need_input' => 'Añade primero la URL de una web o una descripción breve.',
    'generate_rate_limited' => 'Demasiadas generaciones. Espera un poco y vuelve a intentarlo.',
    'generate_done' => 'Descripción generada, revísala y ajústala si hace falta.',
    'generate_failed' => 'No se ha podido generar la descripción. Inténtalo de nuevo o escríbela a mano.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Reglas comunes',
    'shared_rules_heading' => 'Reglas comunes de respuesta',
    'shared_rules_desc' => 'Estas reglas se aplican por encima de todos los agentes, en cada respuesta con IA. Ideales para correcciones de estilo que no quieres repetir en cada agente.',
    'shared_rules_placeholder' => "p. ej.\nEn las respuestas en español, usa «sala» y nunca «room».\nNo prometas nunca descuentos ni reembolsos.\nFirma las respuestas sin nombre.",
    'shared_rules_save' => 'Guardar reglas',
    'shared_rules_saved' => 'Reglas comunes guardadas',

    // i18n label backfill
    'col_name' => 'Nombre',
    'col_tone' => 'Tono',
    'name' => 'Nombre',

    // i18n label backfill (batch 2)
    'preview_reply' => 'Respuesta del agente',
    'anonymous' => 'Anónimo',
];
