<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Restaurante de ejemplo',
    'demo_period' => 'Informe de rendimiento · últimos 30 días',
    'demo_five_star' => 'Porcentaje de 5 estrellas',
    'demo_summary_label' => 'Resumen ejecutivo',
    'demo_summary' => 'El Restaurante de ejemplo recibió 38 reseñas en los últimos 30 días (+9 frente al periodo anterior), con una media de 4,60★. El 84 % de las reseñas fueron positivas y la tasa de respuesta llegó al 92 %. Los clientes elogiaron una y otra vez la amabilidad del equipo y la rapidez del servicio.',

    'location' => 'Ubicación',
    'business_multi' => ':name y :count más',
    'compare' => 'Comparar',
    'compare_options' => [
        'none' => 'No comparar',
        'previous' => 'Periodo anterior',
        'custom' => 'Rango personalizado…',
    ],
    'compare_from' => 'Comparar desde',
    'compare_to' => 'Comparar hasta',
    'report_language' => 'Idioma del informe',

    'content_section' => 'Contenido del informe',
    'content_section_desc' => 'Elige una plantilla y ajusta qué bloques aparecen en el informe.',
    'preset' => 'Plantilla',
    'blocks' => 'Bloques',
    'competitors_block_hint' => 'Todavía no sigues a ningún competidor. Añádelos primero en Fichas > Competidores.',
    'ai_instructions' => 'Instrucciones para la IA',
    'ai_instructions_help' => 'Indicaciones opcionales para el texto de la IA. Son especialmente útiles para los nombres del personal: enumera a tu equipo y sus apodos para que las menciones se asignen a la persona correcta. Se guardan una vez y se aplican a todos los informes futuros, incluidos los programados.',
    'ai_instructions_placeholder' => 'Nuestro equipo: Eva, Alette, Suleyman (también escrito Suly), Lisa. Unifica los apodos con el nombre completo.',
    'ai_improve' => 'Mejorar con IA',
    'ai_improve_empty' => 'Escribe unas notas primero y luego mejóralas.',
    'ai_improve_rate_limited' => 'Demasiados intentos, prueba más tarde.',
    'ai_improve_done' => 'Instrucciones mejoradas',
    'ai_improve_failed' => 'No se han podido mejorar las instrucciones, inténtalo de nuevo.',

    'schedule_report' => 'Enviar de forma programada',
    'schedule_heading' => 'Programar este informe',
    'schedule_desc' => 'La selección actual (periodo, ubicación, comparación, bloques) se enviará por correo en PDF de forma periódica.',
    'schedule_submit' => 'Crear programación',
    'schedule_created' => 'Programación creada',
    'schedule_created_body' => 'Gestiónala en Informes → Informes programados.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'Te quedan :left de :cap informes con IA este mes',

    // Generate modal
    'generate_heading' => '¿Generar informe con IA?',
    'generate_desc' => 'Genera el resumen ejecutivo con IA para la selección actual.',
    'generate_desc_left' => 'Esto consume 1 de tus informes con IA del mes, te quedan :left.',
    'generate_submit' => 'Generar',

    // Generate notifications
    'report_generated' => 'Informe generado',
    'report_generated_body' => 'El resumen con IA está listo y la vista previa se ha actualizado. Usa Descargar para guardar el PDF.',
    'limit_reached' => 'Límite mensual de informes alcanzado',
    'limit_reached_body' => 'Se muestra un informe básico sin IA. Cambia de plan para tener un límite mensual mayor.',

    // Blade view
    'generate_report' => 'Generar informe',
    'generating' => 'Generando…',
    'download_pdf' => 'Descargar PDF',
    'download_first_tooltip' => 'Genera primero el informe',
    'building' => 'Creando el informe…',
    'preview_title' => 'Vista previa del informe',
];
