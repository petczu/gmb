<?php

declare(strict_types=1);

return [
    'nav' => 'Datos del negocio',
    'title' => 'Datos del negocio',

    'not_configured_title' => 'La gestión de fichas no está configurada',
    'not_configured_body' => 'Define ZERNIO_API_KEY en el entorno del servidor para editar los Perfiles de Empresa de Google.',

    'pick_location' => 'Ubicación',
    'status_live' => 'Publicada en Google',
    'status_suspended' => 'Suspendida por Google',
    'status_disabled' => 'Desactivada',
    'status_unverified' => 'Sin verificar',

    'section_basics' => 'Perfil',
    'field_logo' => 'Logotipo de la ubicación',
    'field_logo_helper' => 'Se muestra en la vista previa de las publicaciones de Google. Si lo dejas vacío, se usa el logotipo del espacio.',
    'field_description' => 'Descripción del negocio',
    'field_description_helper' => 'Se muestra en tu perfil de Google. Hasta 750 caracteres. El formulario carga los valores actuales desde Google.',
    'field_phone' => 'Teléfono',
    'field_website' => 'Sitio web',

    'section_hours' => 'Horario de apertura',
    'section_hours_desc' => 'Una fila por franja horaria. Añade dos filas al mismo día para horarios partidos (por ejemplo, con pausa para comer).',
    'add_hours' => 'Añadir franja horaria',
    'field_day' => 'Día',
    'field_open' => 'Abre',
    'field_close' => 'Cierra',

    'day_monday' => 'Lunes',
    'day_tuesday' => 'Martes',
    'day_wednesday' => 'Miércoles',
    'day_thursday' => 'Jueves',
    'day_friday' => 'Viernes',
    'day_saturday' => 'Sábado',
    'day_sunday' => 'Domingo',

    'section_special' => 'Horarios especiales',
    'section_special_desc' => 'Festivos y excepciones: prevalecen sobre el horario habitual en esas fechas.',

    'section_socials' => 'Perfiles sociales',
    'section_socials_desc' => 'Enlaces a tus perfiles en redes sociales, mostrados en tu ficha de Google. Solo se publican los campos rellenados; deja uno vacío para conservar el valor actual en Google.',
    'add_special' => 'Añadir horario especial',
    'field_start_date' => 'Desde',
    'field_end_date' => 'Hasta',
    'field_closed' => 'Cerrado estos días',

    'save' => 'Publicar en Google',
    'saved' => 'Actualización del perfil enviada a Google',
    'save_failed' => 'La actualización ha fallado',
    'unmatched' => 'Todavía no se ha podido emparejar esta ubicación con una ficha de Google.',

    'field_additional_phones' => 'Teléfonos adicionales',
    'field_additional_phones_placeholder' => 'añade un número + Intro',
    'field_additional_phones_help' => 'Hasta dos números extra visibles en el perfil.',
    'field_timezone' => 'Zona horaria',
    'field_timezone_helper' => 'El horario laboral de las respuestas automáticas se interpreta en esta zona horaria. Se detecta al conectar; cámbialo aquí si no es correcto.',
    'loading_live' => 'Cargando los datos actuales del perfil desde Google…',
];
