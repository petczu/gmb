<?php

declare(strict_types=1);

return [
    'nav' => 'Posts',
    'title' => 'Posts do Google',

    'empty' => 'Nenhum post ainda.',
    'empty_desc' => 'Crie seu primeiro post para mostrar novidades, ofertas ou eventos no seu perfil do Google.',

    'not_configured_title' => 'A publicação de conteúdo não está configurada',
    'not_configured_body' => 'Defina ZERNIO_API_KEY no ambiente do servidor para ativar os posts do Google.',

    'col_created' => 'Criado',
    'col_type' => 'Tipo',
    'col_caption' => 'Texto',
    'col_locations' => 'Locais',
    'col_status' => 'Status',
    'col_scheduled' => 'Agendado para',

    'type_update' => 'Novidade',
    'type_offer' => 'Oferta',
    'type_event' => 'Evento',
    'type_photo' => 'Foto',

    'status_published' => 'Publicado',
    'status_scheduled' => 'Agendado',
    'status_in_progress' => 'Publicando…',
    'status_failed' => 'Falhou',
    'status_draft' => 'Rascunho',

    'create' => 'Novo post',
    'create_heading' => 'Novo post do Google',
    'submit' => 'Publicar',

    'field_type' => 'Tipo de post',
    'field_locations' => 'Locais',
    'field_caption' => 'Texto',
    'field_image' => 'Imagem',
    'field_image_helper' => 'A imagem precisa estar acessível publicamente para que o Google consiga buscá-la: os envios só funcionam a partir de um servidor público, não de uma máquina local.',
    'field_photo_category' => 'Categoria da foto',
    'field_title' => 'Título',
    'field_starts' => 'Começa',
    'field_ends' => 'Termina',
    'field_voucher' => 'Código promocional',
    'field_redeem_url' => 'Link para resgatar',
    'field_terms_url' => 'Link dos termos e condições',
    'field_cta' => 'Botão de chamada para ação',
    'field_cta_url' => 'Link do botão',
    'field_schedule' => 'Agendar para depois',
    'field_schedule_helper' => 'Deixe em branco para publicar imediatamente. Os horários estão em UTC.',

    'cta_none' => 'Sem botão',
    'cta_book' => 'Reservar',
    'cta_order' => 'Pedir online',
    'cta_shop' => 'Comprar',
    'cta_learn_more' => 'Saiba mais',
    'cta_sign_up' => 'Cadastrar-se',
    'cta_call' => 'Ligar agora',

    'no_locations' => 'Escolha pelo menos um local.',
    'unmatched' => 'Estes locais ainda não puderam ser associados a uma ficha do Google:',
    'publish_failed' => 'Falha ao publicar',
    'published_ok' => 'Post publicado',
    'scheduled_ok' => 'Post agendado',

    'delete' => 'Remover',
    'delete_desc' => 'Isso apenas remove a entrada desta lista, não exclui o post do Google.',
    'deleted' => 'Entrada removida',

    // Calendar view
    'view_calendar' => 'Calendário',
    'view_list' => 'Lista',
    'view_month' => 'Mês',
    'view_week' => 'Semana',
    'today' => 'Hoje',
    'all_locations' => 'Todos os locais',
    'location_plus' => ':name +:count',
    'close' => 'Fechar',
    'location_count' => '{1} 1 local|[2,*] :count locais',
    'add_post' => 'Post',
    'add_note' => 'Nota',

    // Drafts
    'save_draft' => 'Salvar rascunho',

    // Imported Google posts
    'view' => 'Ver',
    'duplicate_draft' => 'Duplicar como rascunho',
    'duplicated_draft' => 'Rascunho criado',
    'draft_heading' => 'Editar rascunho',
    'draft_saved' => 'Rascunho salvo',
    'draft_delete' => 'Excluir rascunho',
    'draft_delete_desc' => 'O rascunho será removido. Nada foi publicado no Google.',
    'draft_deleted' => 'Rascunho excluído',

    // Live preview
    'preview_label' => 'Prévia',
    'preview_business' => 'Sua empresa',
    'preview_now' => 'agora mesmo',
    'preview_no_image' => 'Sem imagem',
    'preview_placeholder' => 'O texto do seu post aparecerá aqui.',

    // Sticky notes
    'note_placeholder' => 'Digite uma nota privada…',
    'note_color' => 'Cor da nota',
    'note_tag' => '# etiqueta',
    'note_delete' => 'Excluir nota',
    'note_delete_confirm' => 'Excluir esta nota?',
    'filter' => 'Filtrar',
    'notes_filter' => 'Notas',
    'notes_filter_title' => 'Notas por etiqueta',
    'notes_filter_hint' => 'As etiquetas desmarcadas ficam ocultas no calendário.',
    'notes_filter_untagged' => 'Sem etiqueta',

    'color_yellow' => 'Amarelo',
    'color_orange' => 'Laranja',
    'color_red' => 'Vermelho',
    'color_pink' => 'Rosa',
    'color_purple' => 'Roxo',
    'color_blue' => 'Azul',
    'color_teal' => 'Turquesa',
    'color_green' => 'Verde',
    'color_gray' => 'Cinza',

    // External calendars
    'calendars_button' => '{0} Calendários|{1} 1 calendário|[2,*] :count calendários',
    'calendars_connect' => 'Calendário externo',
    'calendars_title' => 'Calendários externos',
    'calendars_empty' => 'Sobreponha calendários públicos nesta visualização: feriados, reservas ou outros planos de conteúdo.',
    'calendars_synced_ago' => 'Sincronizado :ago',
    'calendars_refresh' => 'Sincronizar agora',
    'calendars_synced' => 'Calendários sincronizados',
    'calendars_sync_failed' => 'Alguns calendários não puderam ser sincronizados',
    'calendar_add' => 'Adicionar calendário externo',
    'calendar_add_submit' => 'Adicionar calendário',
    'calendar_name' => 'Nome',
    'calendar_name_placeholder' => 'ex.: Feriados do Brasil',
    'calendar_url' => 'Link ICS',
    'calendar_url_helper' => 'A URL de um feed iCal/ICS público. No Google Agenda: Configurações, depois "Integrar agenda", depois "Endereço público no formato iCal".',
    'calendar_color' => 'Cor',
    'calendar_added' => 'Calendário adicionado',
    'calendar_events_count' => '{0} Nenhum evento encontrado no feed.|{1} 1 evento importado.|[2,*] :count eventos importados.',
    'calendar_sync_error' => 'Calendário adicionado, mas o feed não pôde ser sincronizado',
    'calendar_delete' => 'Remover calendário',
    'calendar_delete_confirm' => 'Remover este calendário e seus eventos da visualização?',
];
