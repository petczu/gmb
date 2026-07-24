<?php

declare(strict_types=1);

return [
    'nav' => 'Concorrentes',
    'title' => 'Concorrentes',
    'intro' => 'Acompanhe empresas próximas e compare a classificação no Google e o número de avaliações com os seus locais. Os números são atualizados automaticamente todos os dias.',

    'empty' => 'Nenhum concorrente ainda.',
    'empty_desc' => 'Adicione um concorrente para acompanhar a classificação no Google e o crescimento das avaliações.',

    'not_configured_title' => 'O acompanhamento de concorrentes não está configurado',
    'not_configured_body' => 'Defina GOOGLE_PLACES_API_KEY no ambiente do servidor (uma chave de API do Google Places) para ativar o comparativo de concorrentes.',

    'col_battle' => 'Concorrente',
    'col_name' => 'Concorrente',
    'col_rating' => 'Classificação',
    'col_reviews' => 'Avaliações',
    'filter_location' => 'Local',
    'filter_city' => 'Cidade',
    'col_vs' => 'Contra você',
    'col_location' => 'Seu lado',
    'col_checked' => 'Atualizado',

    'untitled_battle' => 'Comparativo sem nome',
    'default_battle_name' => '{1} :location contra 1 concorrente|[2,*] :location contra :count concorrentes',
    'own_locations_count' => ':count locais',
    'rating_weighted_hint' => 'Classificação média dos concorrentes, ponderada pelo número de avaliações.',

    'vs_ahead' => 'Você lidera por :delta ★',
    'vs_behind' => 'Eles lideram por :delta ★',
    'vs_tied' => 'Empate',
    'vs_unknown' => '—',

    'add' => 'Adicionar concorrente',
    'add_heading' => 'Adicionar concorrente',
    'edit' => 'Editar',
    'edit_heading' => 'Editar concorrentes',
    'field_name' => 'Nome do comparativo',
    'field_name_placeholder' => 'ex.: Rua principal contra o bairro',
    'field_your_locations' => 'Seus locais',
    'field_your_locations_helper' => 'Escolha um ou mais dos seus locais para o seu lado.',
    'field_place' => 'Concorrente',
    'field_places' => 'Concorrentes',
    'field_places_helper' => 'Digite o nome de uma empresa (e a cidade) para pesquisar no Google Places.',
    'already_tracked' => 'Você já acompanha este concorrente.',
    'saved' => 'Concorrente salvo',
    'some_failed' => ':count concorrente(s) não puderam ser obtidos e foram ignorados.',

    'duplicate' => 'Duplicar',
    'duplicate_heading' => 'Duplicar concorrente',
    'copy_name' => ':name (cópia)',
    'remove' => 'Remover',
    'removed' => 'Concorrente removido',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Criar grupo',
    'group_heading' => 'Agrupar concorrentes',
    'group_need_two' => 'Escolha pelo menos dois concorrentes para agrupar.',
    'group_created' => 'Grupo criado',
    'group_removed' => 'Grupo removido',
    'ungroup' => 'Remover do grupo',
    'ungrouped' => 'Removido do grupo',
    'field_group_name' => 'Nome do grupo',
    'field_group_competitors' => 'Concorrentes',
    'field_group_competitors_helper' => 'Esses concorrentes se combinam em uma única linha no gráfico de crescimento, com as avaliações somadas.',
    'col_group' => 'Grupo',

    'col_new_reviews' => 'Novas avaliações',
    'col_rating_trend' => 'Mudança na classificação',
    'col_trend' => 'Tendência',
    'you_delta' => 'você: :delta',
    'trend_hint' => 'Novas avaliações no período selecionado.',
    'trend_collecting' => 'coletando…',
    'period_4w' => '4 semanas',
    'period_12w' => '3 meses',

    'collecting' => 'coletando…',
    'prev_delta' => 'anterior: :delta',
    'period_7d' => '7 dias',
    'period_6m' => '6 meses',
    'no_change' => 'sem mudança',
    'search_failed' => 'A busca de concorrentes está temporariamente indisponível',

    // Competitor detail modal
    'view' => 'Ver detalhes',
    'close' => 'Fechar',
    'you' => 'Você',
    'reviews_count' => '{1} 1 avaliação|[2,*] :count avaliações',
    'no_distribution' => 'A distribuição por estrelas ainda não está disponível (atualiza na próxima sincronização).',
];
