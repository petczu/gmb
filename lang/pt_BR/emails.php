<?php

declare(strict_types=1);

return [
    'greeting' => 'Olá :name,',
    'signoff' => 'Obrigado,',
    'team' => 'Equipe Repunio',

    'drip_competitors' => [
        'subject' => 'Você sabe como vai o negócio do lado?',
        'intro' => 'Suas próprias avaliações já estão sob controle. A próxima pergunta que todo dono se faz: estou à frente da concorrência ou ficando para trás? O Repunio pode acompanhar isso por você, com a nota e o número de avaliações de qualquer empresa no Google, todos os dias.',
        'tip' => 'Leva dois minutos: abra Concorrentes, busque o nome e adicione. A partir daí você vê quem está ganhando terreno, por quanto, e se a sua nota acompanha o ritmo.',
        'cta' => 'Adicionar seu primeiro concorrente',
    ],

    'location_connected' => [
        'subject' => ':location está conectado',
        'intro' => 'Seu local :location está conectado agora. Estamos importando as avaliações do Google neste momento; dependendo da quantidade, isso pode levar alguns minutos.',
        'note' => 'Você vai receber outro e-mail assim que as avaliações chegarem.',
        'cta' => 'Ver locais',
    ],

    'location_synced' => [
        'subject' => 'Suas avaliações chegaram',
        'intro' => 'A primeira importação terminou. Veja o que entrou:',
        'note' => 'A partir de agora, novas avaliações chegam automaticamente e suas regras de automação são aplicadas a elas.',
        'cta' => 'Abrir a caixa de avaliações',
    ],

    'drip_connect' => [
        'subject' => 'Sua conta está pronta. Falta um passo',
        'intro' => 'Seu espaço de trabalho no Repunio está configurado, mas ainda está vazio: avaliações, notas e relatórios vêm todos do seu Perfil da Empresa no Google, e nenhum está conectado ainda.',
        'tip' => 'Leva cerca de dois minutos: abra Locais, clique em Conectar, faça login com o Google e escolha sua empresa. Suas avaliações começam a chegar na hora.',
        'cta' => 'Conectar seu local',
    ],

    'signup_code' => [
        'subject' => ':code é o seu código de cadastro do Repunio',
        'intro' => 'Digite este código na página de cadastro para confirmar seu endereço de e-mail:',
        'note' => 'O código é válido por :minutes minutos. Se você não o solicitou, pode ignorar este e-mail com tranquilidade.',
    ],

    'beta_received' => [
        'subject' => 'Obrigado! Recebemos sua solicitação de acesso',
        'intro' => 'Obrigado por se cadastrar! O Repunio está em beta privado e ativamos novas contas em pequenas levas.',
        'note' => 'Vamos avisar por e-mail assim que seu acesso estiver pronto. Por enquanto, não há mais nada a fazer.',
    ],

    'beta_approved' => [
        'subject' => 'Seu acesso ao Repunio está pronto',
        'intro' => 'Boa notícia: sua conta foi ativada. Agora você pode entrar e configurar tudo.',
        'note' => 'Comece conectando seu Perfil da Empresa no Google: suas avaliações são importadas em poucos minutos.',
        'cta' => 'Abrir o Repunio',
    ],

    'welcome' => [
        'subject' => 'Boas-vindas ao Repunio',
        'intro' => 'Sua conta está pronta. O Repunio ajuda você a coletar, responder e gerar relatórios das suas avaliações do Google, tudo em um só lugar.',
        'next' => 'Próximo passo: conecte seu primeiro local e escolha um plano para começar seu teste gratuito de 14 dias.',
        'cta' => 'Abrir o Repunio',
    ],

    'trial_ending' => [
        'subject' => 'Seu teste gratuito termina em :days dias',
        'intro' => 'Seu teste gratuito do Repunio termina em :date. Adicione uma forma de pagamento agora para que nada pare: suas avaliações continuam sincronizando e as respostas com IA seguem funcionando.',
        'note' => 'Você não será cobrado antes do fim do teste, e pode cancelar quando quiser.',
        'cta' => 'Adicionar forma de pagamento',
    ],

    'payment_succeeded' => [
        'subject' => 'Pagamento recebido',
        'intro' => 'Recebemos seu pagamento de :amount. Sua assinatura do Repunio está ativa.',
        'cta' => 'Ver faturamento',
    ],

    'payment_failed' => [
        'subject' => 'Pagamento recusado, ação necessária',
        'intro' => 'Não conseguimos processar seu último pagamento. Sua conta continua funcionando por :days dias; atualize seus dados de faturamento para evitar interrupções.',
        'cta' => 'Atualizar faturamento',
    ],

    'subscription_canceled' => [
        'subject' => 'Sua assinatura será cancelada',
        'intro' => 'Sua assinatura do Repunio foi cancelada. Você mantém acesso completo até :date, depois disso ela não será renovada.',
        'note' => 'Mudou de ideia? Você pode retomá-la a qualquer momento antes dessa data, sem cobrança.',
        'cta' => 'Retomar assinatura',
    ],

    'subscription_resumed' => [
        'subject' => 'Sua assinatura está ativa de novo',
        'intro' => 'Sua assinatura do Repunio foi retomada e vai continuar renovando normalmente. Não há mais nada a fazer.',
        'cta' => 'Ver faturamento',
    ],

    'ai_limit' => [
        'subject' => 'Você usou todas as suas respostas com IA deste mês',
        'intro' => 'Você atingiu o limite mensal de respostas com IA do plano :plan. Faça upgrade para um limite maior, ou continue respondendo manualmente até o mês que vem.',
        'cta' => 'Ver planos',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Falha no pagamento da recarga de IA',
        'intro' => 'Tentamos recarregar automaticamente suas respostas com IA, mas o pagamento não foi aprovado. Atualize seu cartão para que a recarga automática continue funcionando.',
        'cta' => 'Atualizar faturamento',
    ],

    'new_reviews' => [
        'subject' => ':count nova(s) avaliação(ões) para a sua empresa',
        'intro' => 'Você tem :count nova(s) avaliação(ões) para :location.',
        'col_author' => 'Autor',
        'col_rating' => 'Nota',
        'col_location' => 'Local',
        'col_review' => 'Avaliação',
        'cta' => 'Ver avaliações',
    ],

    'account_disconnected' => [
        'subject' => 'Ação necessária: sua conexão com o Google parou de funcionar',
        'intro' => 'A conexão com o Google de ":account" parou de funcionar, então suas avaliações não estão mais sincronizando.',
        'detail' => 'Reconecte a conta para retomar a sincronização das avaliações e a publicação das respostas.',
        'cta' => 'Reconectar',
    ],

    'sync_restored' => [
        'subject' => 'Sua conexão com o Google voltou',
        'intro' => 'Boa notícia: a conexão de ":account" voltou e a sincronização foi retomada. Suas avaliações estão em dia de novo.',
        'cta' => 'Abrir o Repunio',
    ],

    'negative_review' => [
        'subject' => 'Uma avaliação de :rating★ precisa da sua atenção',
        'intro' => 'Uma nova avaliação para :business precisa da sua atenção.',
        'col_author' => 'Autor',
        'col_rating' => 'Nota',
        'col_review' => 'Avaliação',
        'cta' => 'Responder agora',
    ],

    'reply_failed' => [
        'subject' => 'Não conseguimos publicar sua resposta',
        'intro' => 'Tentamos publicar uma resposta a uma avaliação de :business, mas houve uma falha.',
        'col_author' => 'Autor',
        'col_review' => 'Avaliação',
        'detail' => 'Tente publicar a resposta novamente pelo aplicativo.',
        'detail_retry' => 'Isso parece ser temporário, então vamos tentar publicar de novo automaticamente nas próximas horas. Não é preciso fazer nada. Se continuar falhando, você vai encontrá-la em Avaliações → Com falha.',
        'detail_not_found' => 'O Google informa que esta avaliação não existe mais. Ela pode ter sido excluída pelo autor ou filtrada pelo Google. Nada a fazer: o rascunho foi arquivado e não será tentado de novo.',
        'detail_unauthorized' => 'A conexão com o Google não tem autorização para responder por este local, então não vamos continuar tentando. Reconecte a conta e publique a resposta de novo pelo aplicativo.',
        'cta' => 'Abrir aprovações',
    ],

    'post_failed' => [
        'subject' => 'Não conseguimos publicar sua postagem no Google',
        'intro' => 'Tentamos publicar uma postagem no Google para :business, mas houve uma falha. A postagem está no seu calendário com o erro.',
        'detail' => 'Tente publicar a postagem novamente pelo aplicativo.',
        'detail_reason' => 'Motivo: :reason',
        'cta' => 'Abrir postagens',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies aguardando aprovação',
        'intro' => 'Você tem :count :replies aguardando sua aprovação. Revise e aprove para que sejam publicadas.',
        'reply_word' => '{1}resposta|[2,*]respostas',
        'reply_label' => 'Resposta sugerida',
        'cta' => 'Revisar aprovações',
    ],

    'review_goal' => [
        'subject_mid' => 'Sua meta de avaliações: como vai o mês',
        'subject_recap' => 'Resumo de avaliações de :month',
        'intro_mid_ahead' => 'Ótimo ritmo! Você tem :actual novas avaliações este mês, acima das :expected esperadas a esta altura (meta :goal). Continue assim.',
        'intro_mid_on_track' => 'Você está no ritmo certo: :actual novas avaliações este mês, bem perto das :expected esperadas a esta altura (meta :goal).',
        'intro_mid_behind' => 'Um empurrãozinho: você tem :actual novas avaliações este mês, abaixo das :expected esperadas a esta altura (meta :goal). Um esforço a mais ajuda.',
        'intro_recap' => 'Veja como :month terminou: :actual novas avaliações frente a uma meta de :goal.',
        'col_location' => 'Local',
        'col_goal' => 'Meta',
        'col_so_far' => 'Até agora',
        'col_projected' => 'Projeção',
        'col_pace' => 'Ritmo',
        'col_got' => 'Obtidas',
        'col_vs_goal' => 'vs meta',
        'col_vs_prev' => 'vs mês passado',
        'status_ahead' => 'Adiantado',
        'status_on_track' => 'No ritmo',
        'status_behind' => 'Atrasado',
        'cta' => 'Ver avaliações',
    ],

    'coaching' => [
        'subject' => 'Sua meta de avaliações: vamos manter o ritmo',
        'intro_almost' => 'Tão perto! Faltam só :remaining para alcançar sua meta de :goal este mês. Você consegue!',
        'intro_behind' => 'Você está em :actual de :goal este mês. Um esforço constante esta semana coloca você de volta no ritmo. Aqui vão algumas ideias.',
        'intro_on_track' => 'Bom trabalho! :actual de :goal e bem no ritmo. Pedir algumas avaliações esta semana mantém o embalo.',
        'intro_ahead' => 'Que embalo! :actual de :goal, à frente do planejado. Continue assim com estas ideias.',
        'steady' => 'Uma dica: distribua os pedidos ao longo dos dias. Uma enxurrada repentina de avaliações parece suspeita para o Google e pode acabar filtrada. A constância vence.',
        'cta' => 'Abrir avaliações',
    ],

    'goal_reached' => [
        'subject' => 'Meta batida! :goal avaliações este mês! 🎉',
        'intro' => 'Parabéns! Você alcançou sua meta de :goal novas avaliações este mês! Isso é um embalo de verdade para a sua reputação.',
        'note' => 'Mantenha o hábito em um ritmo constante e o mês que vem será ainda mais fácil.',
        'cta' => 'Abrir avaliações',
    ],

    'review_anomaly' => [
        'subject' => 'Atenção: :count ponto(s) para verificar nas suas avaliações',
        'intro' => 'Encontramos algo que merece um olhar nas suas avaliações:',
        'stalled' => 'nenhuma nova avaliação há :days dias, embora costume ser ativo.',
        'negative_streak' => ':count avaliações com poucas estrelas em 3 dias. Responda rápido para limitar os danos.',
        'spike' => 'pico incomum: :recent avaliações em 7 dias (normalmente cerca de :baseline por semana). Boa notícia, ou vale verificar se não é spam.',
        'rating_drop' => 'a nota está caindo: :recent★ recentemente contra :prior★ antes.',
        'cta' => 'Abrir avaliações',
    ],

    'invite' => [
        'subject' => 'Você foi convidado para entrar em :workspace no Repunio',
        'greeting' => 'Olá,',
        'intro' => ':inviter convidou você para entrar em :workspace no Repunio como :role.',
        'note' => 'Este convite expira em 14 dias. Se você não esperava por ele, pode ignorar este e-mail.',
        'cta' => 'Aceitar convite',
    ],

    // Série de e-mails de onboarding (educação do produto)
    'drip_inbox' => [
        'subject' => 'Todas as avaliações em uma só caixa',
        'intro' => 'Todas as avaliações dos seus locais chegam em uma única caixa de entrada. Filtre por nota, local ou sem resposta, e responda em dois cliques.',
        'tip' => 'Experimente agora: abra uma avaliação e clique em Gerar com IA. Você recebe um rascunho pronto no seu tom, que pode editar antes de publicar.',
        'cta' => 'Abrir suas avaliações',
    ],
    'drip_automation' => [
        'subject' => 'Coloque as respostas no piloto automático',
        'intro' => 'Crie um agente de IA que conhece a sua empresa e o seu tom, depois deixe as regras de resposta automática cuidarem das avaliações de rotina por você.',
        'tip' => 'Ainda não está pronto para o piloto automático? Use a fila de aprovação: a IA redige, você aprova com um clique.',
        'cta' => 'Configurar automações',
    ],
    'drip_growth' => [
        'subject' => 'Colete mais avaliações este mês',
        'intro' => 'Defina uma meta mensal de avaliações por local e nós acompanhamos o ritmo, comemoramos as conquistas e avisamos sobre anomalias.',
        'tip' => 'Crie sua página de coleta de avaliações: um link curto e um QR code que levam seus clientes satisfeitos direto para o formulário de avaliação do Google ou do TripAdvisor.',
        'cta' => 'Criar sua página de avaliações',
    ],
    'drip_reports' => [
        'subject' => 'Relatórios que realmente são lidos',
        'intro' => 'Monte um relatório de desempenho a partir de blocos: indicadores, resumo com IA, menções à equipe, temas. Baixe em PDF ou compartilhe um link.',
        'tip' => 'Configure uma vez, envie todo mês: agende o relatório e ele chega automaticamente nas caixas de entrada, no idioma que você escolher.',
        'cta' => 'Montar um relatório',
    ],
    'drip_team' => [
        'subject' => 'Traga a sua equipe',
        'intro' => 'Convide colegas com funções, ou adicione convidados que recebem apenas notificações e relatórios, sem precisar de login.',
        'tip' => 'Defina quem recebe cada e-mail em Configurações e depois direcione os alertas de novas avaliações para as pessoas que cuidam delas.',
        'cta' => 'Convidar sua equipe',
    ],
    'drip_member' => [
        'subject' => 'Se orientando no Repunio',
        'intro' => 'Você foi adicionado a um espaço de trabalho. A caixa de Avaliações é onde o trabalho acontece: filtrar, responder, pronto.',
        'tip' => 'Defina o idioma da interface e dos e-mails no seu perfil para que tudo chegue do jeito que você prefere.',
        'cta' => 'Abrir o Repunio',
    ],
    'drip_unsubscribe' => 'Dicas demais? :link',
    'drip_unsubscribe_link' => 'Cancelar a inscrição destes e-mails',

    'unsubscribed_title' => 'Sua inscrição foi cancelada',
    'unsubscribed_body' => 'Você não vai mais receber dicas do produto e e-mails de introdução. Os e-mails importantes de conta e faturamento continuam chegando. Mudou de ideia? Reative-os em :link.',
    'unsubscribed_profile' => 'seu perfil',
];
