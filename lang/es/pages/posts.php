<?php

declare(strict_types=1);

return [
    'nav' => 'Publicaciones',
    'title' => 'Publicaciones de Google',

    'empty' => 'Todavía no hay publicaciones.',
    'empty_desc' => 'Crea tu primera publicación para mostrar novedades, ofertas o eventos en tu perfil de Google.',

    'not_configured_title' => 'La publicación de contenido no está configurada',
    'not_configured_body' => 'Define ZERNIO_API_KEY en el entorno del servidor para activar las publicaciones de Google.',

    'col_created' => 'Creada',
    'col_type' => 'Tipo',
    'col_caption' => 'Texto',
    'col_locations' => 'Ubicaciones',
    'col_status' => 'Estado',
    'col_scheduled' => 'Programada para',

    'type_update' => 'Novedad',
    'type_offer' => 'Oferta',
    'type_event' => 'Evento',
    'type_photo' => 'Foto',

    'status_published' => 'Publicada',
    'status_scheduled' => 'Programada',
    'status_in_progress' => 'Publicando…',
    'status_failed' => 'Fallida',
    'status_draft' => 'Borrador',

    'create' => 'Nueva publicación',
    'create_heading' => 'Nueva publicación de Google',
    'submit' => 'Publicar',

    'field_type' => 'Tipo de publicación',
    'field_locations' => 'Ubicaciones',
    'field_caption' => 'Texto',
    'field_image' => 'Imagen',
    'field_image_helper' => 'La imagen tiene que ser accesible públicamente para que Google pueda descargarla: las subidas solo funcionan desde un servidor público, no desde un equipo local.',
    'field_photo_category' => 'Categoría de la foto',
    'field_title' => 'Título',
    'field_starts' => 'Empieza',
    'field_ends' => 'Termina',
    'field_voucher' => 'Código promocional',
    'field_redeem_url' => 'Enlace para canjear',
    'field_terms_url' => 'Enlace a los términos y condiciones',
    'field_cta' => 'Botón de llamada a la acción',
    'field_cta_url' => 'Enlace del botón',
    'field_schedule' => 'Programar para más tarde',
    'field_schedule_helper' => 'Déjalo vacío para publicar al instante. Las horas están en UTC.',

    'cta_none' => 'Sin botón',
    'cta_book' => 'Reservar',
    'cta_order' => 'Pedir online',
    'cta_shop' => 'Comprar',
    'cta_learn_more' => 'Más información',
    'cta_sign_up' => 'Registrarse',
    'cta_call' => 'Llamar ahora',

    'no_locations' => 'Elige al menos una ubicación.',
    'unmatched' => 'Estas ubicaciones todavía no se han podido emparejar con una ficha de Google:',
    'publish_failed' => 'Fallo al publicar',
    'published_ok' => 'Publicación publicada',
    'scheduled_ok' => 'Publicación programada',

    'delete' => 'Eliminar',
    'delete_desc' => 'Esto solo quita la entrada de esta lista, no elimina la publicación de Google.',
    'deleted' => 'Entrada eliminada',

    // Calendar view
    'view_calendar' => 'Calendario',
    'view_list' => 'Lista',
    'view_month' => 'Mes',
    'view_week' => 'Semana',
    'today' => 'Hoy',
    'all_locations' => 'Todas las ubicaciones',
    'location_plus' => ':name +:count',
    'close' => 'Cerrar',
    'location_count' => '{1} 1 ubicación|[2,*] :count ubicaciones',
    'add_post' => 'Publicación',
    'add_note' => 'Nota',

    // Drafts
    'save_draft' => 'Guardar borrador',

    // Imported Google posts
    'view' => 'Ver',
    'duplicate_draft' => 'Duplicar como borrador',
    'duplicated_draft' => 'Borrador creado',
    'draft_heading' => 'Editar borrador',
    'draft_saved' => 'Borrador guardado',
    'draft_delete' => 'Eliminar borrador',
    'draft_delete_desc' => 'El borrador se eliminará. No se ha publicado nada en Google.',
    'draft_deleted' => 'Borrador eliminado',

    // Live preview
    'preview_label' => 'Vista previa',
    'preview_business' => 'Tu negocio',
    'preview_now' => 'ahora mismo',
    'preview_no_image' => 'Sin imagen',
    'preview_placeholder' => 'Aquí aparecerá el texto de tu publicación.',

    // Sticky notes
    'note_placeholder' => 'Escribe una nota privada…',
    'note_color' => 'Color de la nota',
    'note_tag' => '# etiqueta',
    'note_delete' => 'Eliminar nota',
    'note_delete_confirm' => '¿Eliminar esta nota?',
    'filter' => 'Filtrar',
    'notes_filter' => 'Notas',
    'notes_filter_title' => 'Notas por etiqueta',
    'notes_filter_hint' => 'Las etiquetas sin marcar se ocultan del calendario.',
    'notes_filter_untagged' => 'Sin etiqueta',

    'color_yellow' => 'Amarillo',
    'color_orange' => 'Naranja',
    'color_red' => 'Rojo',
    'color_pink' => 'Rosa',
    'color_purple' => 'Morado',
    'color_blue' => 'Azul',
    'color_teal' => 'Turquesa',
    'color_green' => 'Verde',
    'color_gray' => 'Gris',

    // External calendars
    'calendars_button' => '{0} Calendarios|{1} 1 calendario|[2,*] :count calendarios',
    'calendars_connect' => 'Calendario externo',
    'calendars_title' => 'Calendarios externos',
    'calendars_empty' => 'Superpón calendarios públicos en esta vista: festivos, reservas u otros planes de contenido.',
    'calendars_synced_ago' => 'Sincronizado :ago',
    'calendars_refresh' => 'Sincronizar ahora',
    'calendars_synced' => 'Calendarios sincronizados',
    'calendars_sync_failed' => 'Algunos calendarios no se han podido sincronizar',
    'calendar_add' => 'Añadir calendario externo',
    'calendar_add_submit' => 'Añadir calendario',
    'calendar_name' => 'Nombre',
    'calendar_name_placeholder' => 'p. ej. Festivos de España',
    'calendar_url' => 'Enlace ICS',
    'calendar_url_helper' => 'La URL de un feed iCal/ICS público. En Google Calendar: Configuración, luego «Integrar calendario» y «Dirección pública en formato iCal».',
    'calendar_color' => 'Color',
    'calendar_added' => 'Calendario añadido',
    'calendar_events_count' => '{0} No se han encontrado eventos en el feed.|{1} 1 evento importado.|[2,*] :count eventos importados.',
    'calendar_sync_error' => 'Calendario añadido, pero no se ha podido sincronizar el feed',
    'calendar_delete' => 'Eliminar calendario',
    'calendar_delete_confirm' => '¿Eliminar este calendario y sus eventos de la vista?',
];
