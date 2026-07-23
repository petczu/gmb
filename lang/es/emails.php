<?php

declare(strict_types=1);

return [
    'greeting' => 'Hola :name:',
    'signoff' => 'Gracias,',
    'team' => 'El equipo de Repunio',

    'drip_competitors' => [
        'subject' => '¿Sabes cómo le va al negocio de al lado?',
        'intro' => 'Tus reseñas ya están bajo control. La siguiente pregunta que se hace todo dueño: ¿voy por delante de la competencia o me estoy quedando atrás? Repunio puede vigilarlo por ti, con la valoración y el número de reseñas de cualquier negocio de Google, cada día.',
        'tip' => 'Se tarda dos minutos: abre Competidores, busca el nombre y añádelo. A partir de ahí verás quién se adelanta, por cuánto, y si tu valoración aguanta el ritmo.',
        'cta' => 'Añade tu primer competidor',
    ],

    'location_connected' => [
        'subject' => ':location ya está conectada',
        'intro' => 'Tu ubicación :location ya está conectada. Estamos importando sus reseñas de Google ahora mismo; según cuántas haya, esto puede tardar unos minutos.',
        'note' => 'Te escribiremos otra vez en cuanto estén todas dentro.',
        'cta' => 'Ver ubicaciones',
    ],

    'location_synced' => [
        'subject' => 'Tus reseñas ya están aquí',
        'intro' => 'La primera importación ha terminado. Esto es lo que ha entrado:',
        'note' => 'A partir de ahora, las reseñas nuevas llegan solas y tus reglas de automatización se les aplican.',
        'cta' => 'Abrir la bandeja de reseñas',
    ],

    'drip_connect' => [
        'subject' => 'Tu cuenta está lista. Queda un paso',
        'intro' => 'Tu espacio de Repunio está montado, pero sigue vacío: las reseñas, valoraciones e informes vienen de tu Perfil de Empresa de Google, y todavía no hay ninguno conectado.',
        'tip' => 'Son unos dos minutos: abre Ubicaciones, pulsa Conectar, inicia sesión con Google y elige tu negocio. Tus reseñas empiezan a entrar al momento.',
        'cta' => 'Conecta tu ubicación',
    ],

    'signup_code' => [
        'subject' => ':code es tu código de registro de Repunio',
        'intro' => 'Introduce este código en la página de registro para confirmar tu dirección de correo:',
        'note' => 'El código es válido durante :minutes minutos. Si no lo has pedido tú, puedes ignorar este correo sin problema.',
    ],

    'beta_received' => [
        'subject' => '¡Gracias! Ya tenemos tu solicitud de acceso',
        'intro' => '¡Gracias por registrarte! Repunio está en beta privada y activamos cuentas nuevas por tandas.',
        'note' => 'Te escribiremos en cuanto tu acceso esté listo. Por ahora no tienes que hacer nada más.',
    ],

    'beta_approved' => [
        'subject' => 'Tu acceso a Repunio está listo',
        'intro' => 'Buenas noticias: tu cuenta ya está activada. Puedes entrar y dejarlo todo montado.',
        'note' => 'Empieza conectando tu Perfil de Empresa de Google: tus reseñas se importan en cuestión de minutos.',
        'cta' => 'Abrir Repunio',
    ],

    'welcome' => [
        'subject' => 'Te damos la bienvenida a Repunio',
        'intro' => 'Tu cuenta está lista. Repunio te ayuda a conseguir reseñas de Google, responderlas y medirlas, todo en un mismo sitio.',
        'next' => 'Lo siguiente: conecta tu primera ubicación y elige un plan para empezar tu prueba gratuita de 14 días.',
        'cta' => 'Abrir Repunio',
    ],

    'trial_ending' => [
        'subject' => 'Tu prueba gratuita termina en :days días',
        'intro' => 'Tu prueba gratuita de Repunio termina el :date. Añade un método de pago ahora para que no se pare nada: tus reseñas siguen sincronizándose y las respuestas con IA siguen funcionando.',
        'note' => 'No te cobraremos hasta que acabe la prueba, y puedes cancelar cuando quieras.',
        'cta' => 'Añadir método de pago',
    ],

    'payment_succeeded' => [
        'subject' => 'Pago recibido',
        'intro' => 'Hemos recibido tu pago de :amount. Tu suscripción a Repunio está activa.',
        'cta' => 'Ver facturación',
    ],

    'payment_failed' => [
        'subject' => 'Pago fallido: hay que actuar',
        'intro' => 'No hemos podido procesar tu último pago. Tu cuenta sigue funcionando :days días; actualiza tus datos de pago para evitar interrupciones.',
        'cta' => 'Actualizar la facturación',
    ],

    'subscription_canceled' => [
        'subject' => 'Tu suscripción se va a cancelar',
        'intro' => 'Tu suscripción a Repunio se ha cancelado. Mantienes el acceso completo hasta el :date; después no se renovará.',
        'note' => '¿Has cambiado de idea? Puedes reanudarla en cualquier momento antes de esa fecha, sin coste.',
        'cta' => 'Reanudar la suscripción',
    ],

    'subscription_resumed' => [
        'subject' => 'Tu suscripción vuelve a estar activa',
        'intro' => 'Tu suscripción a Repunio se ha reanudado y seguirá renovándose con normalidad. No tienes que hacer nada más.',
        'cta' => 'Ver facturación',
    ],

    'ai_limit' => [
        'subject' => 'Has agotado tus respuestas con IA de este mes',
        'intro' => 'Has alcanzado el límite mensual de respuestas con IA de tu plan :plan. Cambia de plan para tener un límite mayor, o sigue respondiendo a mano hasta el mes que viene.',
        'cta' => 'Ver planes',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Ha fallado la recarga de IA',
        'intro' => 'Hemos intentado recargar automáticamente tus respuestas con IA, pero el pago no ha salido adelante. Actualiza tu tarjeta para que la recarga automática siga funcionando.',
        'cta' => 'Actualizar la facturación',
    ],

    'new_reviews' => [
        'subject' => ':count reseña(s) nueva(s) para tu negocio',
        'intro' => 'Tienes :count reseña(s) nueva(s) de :location.',
        'col_author' => 'Autor',
        'col_rating' => 'Valoración',
        'col_location' => 'Ubicación',
        'col_review' => 'Reseña',
        'cta' => 'Ver reseñas',
    ],

    'account_disconnected' => [
        'subject' => 'Atención: tu conexión con Google ha dejado de funcionar',
        'intro' => 'La conexión con Google de «:account» ha dejado de funcionar, así que tus reseñas ya no se están sincronizando.',
        'detail' => 'Vuelve a conectar la cuenta para retomar la sincronización de reseñas y la publicación de respuestas.',
        'cta' => 'Volver a conectar',
    ],

    'sync_restored' => [
        'subject' => 'Tu conexión con Google vuelve a funcionar',
        'intro' => 'Buenas noticias: la conexión de «:account» vuelve a estar activa y la sincronización se ha retomado. Tus reseñas están otra vez al día.',
        'cta' => 'Abrir Repunio',
    ],

    'negative_review' => [
        'subject' => 'Una reseña de :rating★ necesita tu atención',
        'intro' => 'Una reseña nueva de :business necesita tu atención.',
        'col_author' => 'Autor',
        'col_rating' => 'Valoración',
        'col_review' => 'Reseña',
        'cta' => 'Responder ahora',
    ],

    'reply_failed' => [
        'subject' => 'No hemos podido publicar tu respuesta',
        'intro' => 'Hemos intentado publicar una respuesta a una reseña de :business, pero ha fallado.',
        'col_author' => 'Autor',
        'col_review' => 'Reseña',
        'detail' => 'Vuelve a intentar publicar la respuesta desde la aplicación.',
        'detail_retry' => 'Parece algo puntual, así que lo intentaremos de nuevo automáticamente en las próximas horas. No tienes que hacer nada. Si sigue fallando, la encontrarás en Reseñas → Fallidas.',
        'detail_not_found' => 'Google dice que esta reseña ya no existe. Puede que su autor la haya borrado o que Google la haya filtrado. No hay nada que hacer: el borrador se ha apartado y no se reintentará.',
        'detail_unauthorized' => 'La conexión con Google no tiene permiso para responder en esta ubicación, así que no seguiremos reintentando. Vuelve a conectar la cuenta y publica la respuesta de nuevo desde la aplicación.',
        'cta' => 'Abrir aprobaciones',
    ],

    'post_failed' => [
        'subject' => 'No hemos podido publicar tu publicación de Google',
        'intro' => 'Hemos intentado publicar una publicación de Google para :business, pero ha fallado. La publicación está en tu calendario con el error.',
        'detail' => 'Vuelve a intentar publicarla desde la aplicación.',
        'detail_reason' => 'Motivo: :reason',
        'cta' => 'Abrir publicaciones',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies esperando aprobación',
        'intro' => 'Tienes :count :replies esperando tu aprobación. Revísalas y apruébalas para que se publiquen.',
        'reply_word' => '{1}respuesta|[2,*]respuestas',
        'reply_label' => 'Respuesta propuesta',
        'cta' => 'Revisar aprobaciones',
    ],

    'review_goal' => [
        'subject_mid' => 'Tu objetivo de reseñas: cómo va el mes',
        'subject_recap' => 'Resumen de reseñas de :month',
        'intro_mid_ahead' => '¡Buen ritmo! Llevas :actual reseñas nuevas este mes, por encima de las :expected previstas a estas alturas (objetivo :goal). Sigue así.',
        'intro_mid_on_track' => 'Vas bien: :actual reseñas nuevas este mes, justo en torno a las :expected previstas a estas alturas (objetivo :goal).',
        'intro_mid_behind' => 'Un empujón: llevas :actual reseñas nuevas este mes, por debajo de las :expected previstas a estas alturas (objetivo :goal). Un pequeño esfuerzo ayuda.',
        'intro_recap' => 'Así ha terminado :month: :actual reseñas nuevas frente a un objetivo de :goal.',
        'col_location' => 'Ubicación',
        'col_goal' => 'Objetivo',
        'col_so_far' => 'Hasta ahora',
        'col_projected' => 'Previsión',
        'col_pace' => 'Ritmo',
        'col_got' => 'Conseguidas',
        'col_vs_goal' => 'frente al objetivo',
        'col_vs_prev' => 'frente al mes pasado',
        'status_ahead' => 'Por delante',
        'status_on_track' => 'En ritmo',
        'status_behind' => 'Por detrás',
        'cta' => 'Ver reseñas',
    ],

    'coaching' => [
        'subject' => 'Tu objetivo de reseñas: sigamos con ello',
        'intro_almost' => '¡Ya casi! Solo te faltan :remaining para llegar a tu objetivo de :goal este mes. ¡Tú puedes!',
        'intro_behind' => 'Vas por :actual de :goal este mes. Un empujón constante esta semana te devuelve al ritmo. Aquí van unas ideas.',
        'intro_on_track' => '¡Buen trabajo! :actual de :goal y justo en ritmo. Pedir unas cuantas reseñas esta semana mantiene la inercia.',
        'intro_ahead' => '¡Qué buena inercia! :actual de :goal, por delante de lo previsto. Sigue así con estas ideas.',
        'steady' => 'Un consejo: reparte las peticiones a lo largo de los días. Una avalancha repentina de reseñas le resulta sospechosa a Google y puede acabar filtrada. La constancia gana.',
        'cta' => 'Abrir reseñas',
    ],

    'goal_reached' => [
        'subject' => '¡Objetivo conseguido! ¡:goal reseñas este mes! 🎉',
        'intro' => '¡Enhorabuena! Has alcanzado tu objetivo de :goal reseñas nuevas este mes. Eso es inercia de verdad para tu reputación.',
        'note' => 'Mantén el hábito a un ritmo constante y el mes que viene será aún más fácil.',
        'cta' => 'Abrir reseñas',
    ],

    'review_anomaly' => [
        'subject' => 'Atención: :count cosa(s) que revisar en tus reseñas',
        'intro' => 'Hemos visto algo que merece un vistazo en tus reseñas:',
        'stalled' => 'sin reseñas nuevas desde hace :days días, aunque suele estar activa.',
        'negative_streak' => ':count reseñas de pocas estrellas en 3 días. Responde rápido para limitar el daño.',
        'spike' => 'pico inusual: :recent reseñas en 7 días (normalmente unas :baseline por semana). Buena noticia, o conviene comprobar que no sea spam.',
        'rating_drop' => 'la valoración está bajando: :recent★ últimamente frente a :prior★ antes.',
        'cta' => 'Abrir reseñas',
    ],

    'invite' => [
        'subject' => 'Te han invitado a unirte a :workspace en Repunio',
        'greeting' => 'Hola:',
        'intro' => ':inviter te ha invitado a unirte a :workspace en Repunio como :role.',
        'note' => 'Esta invitación caduca en 14 días. Si no la esperabas, puedes ignorar este correo.',
        'cta' => 'Aceptar la invitación',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Todas las reseñas, una sola bandeja',
        'intro' => 'Todas las reseñas de tus ubicaciones llegan a una única bandeja. Filtra por valoración, ubicación o sin responder, y responde en dos clics.',
        'tip' => 'Pruébalo ahora: abre una reseña y pulsa Generar con IA. Obtienes un borrador listo con tu tono, que puedes editar antes de publicar.',
        'cta' => 'Abrir tus reseñas',
    ],
    'drip_automation' => [
        'subject' => 'Pon las respuestas en piloto automático',
        'intro' => 'Crea un agente de IA que conozca tu negocio y tu tono, y deja que las reglas de respuesta automática contesten las reseñas rutinarias por ti.',
        'tip' => '¿Aún no te fías del piloto automático? Usa la cola de aprobación: la IA redacta y tú apruebas con un clic.',
        'cta' => 'Configurar automatizaciones',
    ],
    'drip_growth' => [
        'subject' => 'Consigue más reseñas este mes',
        'intro' => 'Fija un objetivo mensual de reseñas por ubicación y nosotros seguimos el ritmo, celebramos los hitos y te avisamos de las anomalías.',
        'tip' => 'Crea tu página de captación de reseñas: un enlace corto y un QR que llevan a tus clientes contentos directos al formulario de Google o TripAdvisor.',
        'cta' => 'Crear tu página de reseñas',
    ],
    'drip_reports' => [
        'subject' => 'Informes que de verdad se leen',
        'intro' => 'Monta un informe de rendimiento por bloques: KPI, resumen con IA, menciones al personal, temas. Descárgalo en PDF o comparte un enlace.',
        'tip' => 'Configúralo una vez y envíalo cada mes: programa el informe y llegará solo a las bandejas de entrada, en el idioma que elijas.',
        'cta' => 'Crear un informe',
    ],
    'drip_team' => [
        'subject' => 'Suma a tu equipo',
        'intro' => 'Invita a compañeros con roles, o añade invitados que solo reciban notificaciones e informes, sin necesidad de cuenta.',
        'tip' => 'Decide quién recibe cada correo en Ajustes y dirige los avisos de reseñas nuevas a quienes las gestionan.',
        'cta' => 'Invitar a tu equipo',
    ],
    'drip_member' => [
        'subject' => 'Moverte por Repunio',
        'intro' => 'Te han añadido a un espacio de trabajo. La bandeja de Reseñas es donde ocurre todo: filtrar, responder, listo.',
        'tip' => 'Elige el idioma de la interfaz y de los correos en tu perfil para que todo llegue como te gusta.',
        'cta' => 'Abrir Repunio',
    ],
    'drip_unsubscribe' => '¿Demasiados consejos? :link',
    'drip_unsubscribe_link' => 'Darse de baja de estos correos',

    'unsubscribed_title' => 'Te has dado de baja',
    'unsubscribed_body' => 'Ya no recibirás consejos del producto ni correos de bienvenida. Los correos importantes de cuenta y facturación siguen llegando. ¿Has cambiado de idea? Vuelve a activarlos en :link.',
    'unsubscribed_profile' => 'tu perfil',
];
