<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Adicione os dados da sua empresa',
    'step_company_hint' => 'País e informações de faturamento usados em faturas e relatórios.',
    'step_plan_label' => 'Escolha um plano',
    'step_plan_hint' => 'Comece seu teste gratuito de 14 dias, sem cartão.',
    'step_location_label' => 'Conecte seu primeiro local',
    'step_location_hint' => 'Vincule um Perfil da Empresa no Google para começar a importar avaliações.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Configure seu espaço de trabalho',
    'wiz_plan_done' => '✓ Seu plano está ativo. Continue para a próxima etapa.',
    'wiz_plan_pick' => 'Escolha um plano',
    'wiz_interval' => 'Intervalo de cobrança',
    'wiz_monthly' => 'Mensal',
    'wiz_yearly' => 'Anual',
    'wiz_start_trial' => 'Iniciar teste gratuito de 14 dias',
    'wiz_trial_note' => 'Seu teste gratuito de 14 dias começa assim que você continuar. Sem cartão.',
    'wiz_go_checkout' => 'Continuar para o pagamento',
    'wiz_plan_required' => 'Escolha um plano e conclua o pagamento para continuar.',
    'wiz_location_body' => 'Vincule seu Perfil da Empresa no Google para que possamos importar suas avaliações. Você será redirecionado ao Google para autorizar o acesso e depois escolher o local a conectar.',
    'wiz_connect_google' => 'Conectar Perfil da Empresa no Google',
    'wiz_skip_location' => 'Pular por enquanto',
    'skipped_title' => 'Está tudo pronto',
    'skipped_body' => 'Você pode conectar seu Perfil da Empresa no Google a qualquer momento na página de Locais.',
    'wiz_per_location' => 'por local / mês',
    'wiz_plan_desc_starter' => 'Caixa de entrada de avaliações, respostas manuais e relatórios básicos.',
    'wiz_plan_desc_growth' => 'Adiciona respostas automáticas por IA, relatórios agendados e comparações.',
    'wiz_plan_desc_pro' => 'Tudo isso, mais white label, API, MCP e acesso de clientes.',

    // Onboarding overlay
    'welcome_title' => 'Boas-vindas, vamos configurar sua conta',
    'welcome_subtitle' => 'Algumas etapas rápidas e você estará pronto para começar.',
    'continue_step' => 'Continuar: :label',
    'enter_app' => 'Entrar no aplicativo →',
    'sign_out' => 'Sair',

    // Pending-deletion overlay
    'deletion_title' => 'Este espaço de trabalho está agendado para exclusão',
    'deletion_body' => 'Todos os dados serão excluídos permanentemente em <strong>:date</strong>. Você ainda pode cancelar e manter seu espaço de trabalho.',
    'cancel_deletion' => 'Cancelar exclusão',

    // Grace banner
    'grace_banner' => '⚠️ Não conseguimos processar seu último pagamento. Seu serviço continua ativo até <strong>:date</strong>, por favor',
    'update_your_billing' => 'atualize seu faturamento',

    // Paywall overlay
    'payment_problem_title' => 'Há um problema com o seu pagamento',
    'needs_plan_title' => 'Escolha um plano para começar',
    'payment_problem_body' => 'Seu acesso está pausado porque não conseguimos processar o pagamento. Atualize seu faturamento para continuar.',
    'needs_plan_body' => 'Escolha um plano para ativar avaliações, respostas por IA e relatórios para seus locais. Teste gratuito de 14 dias.',
    'update_billing' => 'Atualizar faturamento',
    'view_plans' => 'Ver planos',

    // Connect-select-location page
    'connecting_location' => 'Conectando local…',
    'choose_location' => 'Escolha qual local do Perfil da Empresa no Google conectar a este espaço de trabalho.',
    'could_not_load' => 'Não foi possível carregar os locais',
    'pending_expired_title' => 'Sessão do Google expirada',
    'pending_expired' => 'A autorização do Google é válida por pouco tempo e esta já expirou. Reconecte e escolha seus locais novamente, leva só um instante.',
    'reconnect_google' => 'Reconectar Google',
    'back' => 'Voltar',
    'no_locations_available' => 'Nenhum local disponível',
    'no_locations_body' => 'Nenhum local do Perfil da Empresa no Google foi retornado. Eles podem ainda estar carregando no lado do Google, tente de novo em instantes.',
    'connect_then_done' => 'Conecte um ou mais locais e depois clique em Concluir.',
    'done' => 'Concluir',
    'connected' => 'Conectado',
    'connect' => 'Conectar',
    'connecting' => 'Conectando…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Selecione o local do negócio',
    'connect_failed' => 'Não foi possível conectar o local',
    'connected_title' => 'Conectado: :name',
    'connected_body' => 'As avaliações estão sincronizando em segundo plano, elas aparecerão na página de Locais em breve.',
    'location_fallback' => 'local',
    'trial_started_title' => 'Seu teste de 14 dias começou',
    'trial_started_body' => 'Acesso completo até :date, sem cartão. Aproveite!',
];
