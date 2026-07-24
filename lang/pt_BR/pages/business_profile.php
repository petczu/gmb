<?php

declare(strict_types=1);

return [
    'nav' => 'Informações',
    'title' => 'Informações da empresa',

    'not_configured_title' => 'A gestão de fichas não está configurada',
    'not_configured_body' => 'Defina ZERNIO_API_KEY no ambiente do servidor para editar os perfis da empresa no Google.',

    'pick_location' => 'Local',
    'status_live' => 'No ar no Google',
    'status_suspended' => 'Suspensa pelo Google',
    'status_disabled' => 'Desativada',
    'status_unverified' => 'Não verificada',

    'section_basics' => 'Ficha',
    'field_logo' => 'Logotipo do local',
    'field_logo_helper' => 'Exibido na prévia das publicações do Google. Caso vazio, usa o logotipo do espaço de trabalho.',
    'field_description' => 'Descrição da empresa',
    'field_description_helper' => 'Exibida no seu perfil do Google. Até 750 caracteres. O formulário carrega os valores atualmente publicados no Google.',
    'field_phone' => 'Número de telefone',
    'field_website' => 'Site',

    'section_hours' => 'Horário de funcionamento',
    'section_hours_desc' => 'Uma linha por faixa de horário. Adicione duas linhas no mesmo dia para horários separados (por exemplo, pausa para o almoço).',
    'add_hours' => 'Adicionar faixa de horário',
    'field_day' => 'Dia',
    'field_open' => 'Abre',
    'field_close' => 'Fecha',

    'day_monday' => 'Segunda-feira',
    'day_tuesday' => 'Terça-feira',
    'day_wednesday' => 'Quarta-feira',
    'day_thursday' => 'Quinta-feira',
    'day_friday' => 'Sexta-feira',
    'day_saturday' => 'Sábado',
    'day_sunday' => 'Domingo',

    'section_special' => 'Horários especiais',
    'section_special_desc' => 'Feriados e exceções: eles substituem o horário normal nas datas indicadas.',

    'section_socials' => 'Redes sociais',
    'section_socials_desc' => 'Links para seus perfis nas redes sociais, exibidos na sua ficha do Google. Apenas os campos preenchidos são publicados; deixe um campo vazio para manter o valor atual no Google.',
    'add_special' => 'Adicionar horário especial',
    'field_start_date' => 'De',
    'field_end_date' => 'Até',
    'field_closed' => 'Fechado nesses dias',

    'save' => 'Publicar no Google',
    'saved' => 'Atualização da ficha enviada ao Google',
    'save_failed' => 'Falha na atualização',
    'unmatched' => 'Este local ainda não pôde ser associado a uma ficha do Google.',

    'field_additional_phones' => 'Números de telefone adicionais',
    'field_additional_phones_placeholder' => 'adicionar número + Enter',
    'field_additional_phones_help' => 'Até dois números extras exibidos no perfil.',
    'field_timezone' => 'Fuso horário',
    'field_timezone_helper' => 'O horário de funcionamento das respostas automáticas é interpretado neste fuso horário. Detectado automaticamente na conexão; corrija aqui se estiver errado.',
    'loading_live' => 'Carregando os dados atuais da ficha a partir do Google…',
];
