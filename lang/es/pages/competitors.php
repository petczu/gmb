<?php

declare(strict_types=1);

return [
    'nav' => 'Competidores',
    'title' => 'Competidores',
    'intro' => 'Sigue a negocios cercanos y compara su valoración y número de reseñas en Google con tus ubicaciones. Los datos se actualizan solos cada día.',

    'empty' => 'Todavía no hay competidores.',
    'empty_desc' => 'Añade un competidor para seguir su valoración en Google y su crecimiento de reseñas.',

    'not_configured_title' => 'El seguimiento de competidores no está configurado',
    'not_configured_body' => 'Define GOOGLE_PLACES_API_KEY en el entorno del servidor (una clave de la API de Google Places) para activar la comparativa de competidores.',

    'col_battle' => 'Competidor',
    'col_name' => 'Competidor',
    'col_rating' => 'Valoración',
    'col_reviews' => 'Reseñas',
    'filter_location' => 'Ubicación',
    'filter_city' => 'Ciudad',
    'col_vs' => 'Frente a ti',
    'col_location' => 'Tu lado',
    'col_checked' => 'Actualizado',

    'untitled_battle' => 'Comparativa sin nombre',
    'default_battle_name' => '{1} :location frente a 1 competidor|[2,*] :location frente a :count competidores',
    'own_locations_count' => ':count ubicaciones',
    'rating_weighted_hint' => 'Valoración promediada entre los competidores, ponderada por su número de reseñas.',

    'vs_ahead' => 'Le sacas :delta ★',
    'vs_behind' => 'Te sacan :delta ★',
    'vs_tied' => 'Empate',
    'vs_unknown' => '—',

    'add' => 'Añadir competidor',
    'add_heading' => 'Añadir competidor',
    'edit' => 'Editar',
    'edit_heading' => 'Editar competidores',
    'field_name' => 'Nombre de la comparativa',
    'field_name_placeholder' => 'p. ej. Calle Mayor frente al barrio',
    'field_your_locations' => 'Tus ubicaciones',
    'field_your_locations_helper' => 'Elige una o varias de tus ubicaciones para tu lado.',
    'field_place' => 'Competidor',
    'field_places' => 'Competidores',
    'field_places_helper' => 'Escribe el nombre de un negocio (y la ciudad) para buscarlo en Google Places.',
    'already_tracked' => 'Ya estás siguiendo a este competidor.',
    'saved' => 'Competidor guardado',
    'some_failed' => 'No se han podido obtener :count competidor(es) y se han omitido.',

    'duplicate' => 'Duplicar',
    'duplicate_heading' => 'Duplicar competidor',
    'copy_name' => ':name (copia)',
    'remove' => 'Eliminar',
    'removed' => 'Competidor eliminado',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Crear grupo',
    'group_heading' => 'Agrupar competidores',
    'group_need_two' => 'Elige al menos dos competidores para agruparlos.',
    'group_created' => 'Grupo creado',
    'group_removed' => 'Grupo eliminado',
    'ungroup' => 'Quitar del grupo',
    'ungrouped' => 'Quitado del grupo',
    'field_group_name' => 'Nombre del grupo',
    'field_group_competitors' => 'Competidores',
    'field_group_competitors_helper' => 'Estos competidores se combinan en una sola línea del gráfico de crecimiento, sumando sus reseñas.',
    'col_group' => 'Grupo',

    'col_new_reviews' => 'Reseñas nuevas',
    'col_rating_trend' => 'Cambio de valoración',
    'col_trend' => 'Tendencia',
    'you_delta' => 'tú: :delta',
    'trend_hint' => 'Reseñas nuevas en el periodo seleccionado.',
    'trend_collecting' => 'recopilando…',
    'period_4w' => '4 semanas',
    'period_12w' => '3 meses',

    'collecting' => 'recopilando…',
    'prev_delta' => 'anterior: :delta',
    'period_7d' => '7 días',
    'period_6m' => '6 meses',
    'no_change' => 'sin cambios',
    'search_failed' => 'La búsqueda de competidores no está disponible temporalmente',

    // Competitor detail modal
    'view' => 'Ver detalles',
    'close' => 'Cerrar',
    'you' => 'Tú',
    'reviews_count' => '{1} 1 reseña|[2,*] :count reseñas',
    'no_distribution' => 'El desglose por estrellas todavía no está disponible (se actualiza en la próxima sincronización).',
];
