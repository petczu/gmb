<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Añade los datos de tu empresa',
    'step_company_hint' => 'País y datos de facturación que se usan en facturas e informes.',
    'step_plan_label' => 'Elige un plan',
    'step_plan_hint' => 'Empieza tu prueba gratuita de 14 días, sin tarjeta.',
    'step_location_label' => 'Conecta tu primera ubicación',
    'step_location_hint' => 'Vincula un Perfil de Empresa de Google para empezar a recibir reseñas.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Configura tu espacio de trabajo',
    'wiz_plan_done' => '✓ Tu plan está activo. Sigue al paso siguiente.',
    'wiz_plan_pick' => 'Elige un plan',
    'wiz_interval' => 'Periodo de facturación',
    'wiz_monthly' => 'Mensual',
    'wiz_yearly' => 'Anual',
    'wiz_start_trial' => 'Empezar la prueba gratuita de 14 días',
    'wiz_trial_note' => 'Tu prueba gratuita de 14 días empieza en cuanto continúes. Sin tarjeta.',
    'wiz_go_checkout' => 'Continuar al pago',
    'wiz_plan_required' => 'Elige un plan y completa el pago para continuar.',
    'wiz_location_body' => 'Vincula tu Perfil de Empresa de Google para que podamos traer tus reseñas. Te llevaremos a Google para autorizar el acceso y después eliges la ubicación que quieres conectar.',
    'wiz_connect_google' => 'Conectar el Perfil de Empresa de Google',
    'wiz_skip_location' => 'Ahora no',
    'skipped_title' => 'Todo listo',
    'skipped_body' => 'Puedes conectar tu Perfil de Empresa de Google cuando quieras desde la página de Ubicaciones.',
    'wiz_per_location' => 'por ubicación / mes',
    'wiz_plan_desc_starter' => 'Bandeja de reseñas, respuestas manuales e informes básicos.',
    'wiz_plan_desc_growth' => 'Añade respuestas automáticas con IA, informes programados y comparativas.',
    'wiz_plan_desc_pro' => 'Todo, más marca blanca, API, MCP y acceso para clientes.',

    // Onboarding overlay
    'welcome_title' => 'Te damos la bienvenida, vamos a configurar tu cuenta',
    'welcome_subtitle' => 'Unos pasos rápidos y listo.',
    'continue_step' => 'Continuar: :label',
    'enter_app' => 'Entrar en la app →',
    'sign_out' => 'Cerrar sesión',

    // Pending-deletion overlay
    'deletion_title' => 'Este espacio está programado para eliminarse',
    'deletion_body' => 'Todos los datos se eliminarán de forma permanente el <strong>:date</strong>. Todavía puedes cancelarlo y conservar tu espacio.',
    'cancel_deletion' => 'Cancelar la eliminación',

    // Grace banner
    'grace_banner' => '⚠️ No hemos podido procesar tu último pago. Tu servicio sigue activo hasta el <strong>:date</strong>; por favor,',
    'update_your_billing' => 'actualiza tus datos de pago',

    // Paywall overlay
    'payment_problem_title' => 'Hay un problema con tu pago',
    'needs_plan_title' => 'Elige un plan para empezar',
    'payment_problem_body' => 'Tu acceso está en pausa porque no hemos podido procesar el pago. Actualiza tus datos de facturación para continuar.',
    'needs_plan_body' => 'Elige un plan para activar reseñas, respuestas con IA e informes para tus ubicaciones. Prueba gratuita de 14 días.',
    'update_billing' => 'Actualizar la facturación',
    'view_plans' => 'Ver planes',

    // Connect-select-location page
    'connecting_location' => 'Conectando la ubicación…',
    'choose_location' => 'Elige qué ubicación de Google Business quieres conectar a este espacio.',
    'could_not_load' => 'No se han podido cargar las ubicaciones',
    'pending_expired_title' => 'La sesión de Google ha caducado',
    'pending_expired' => 'La autorización de Google solo es válida un rato corto y esta ya ha caducado. Vuelve a conectar y elige de nuevo tus ubicaciones, es cuestión de un momento.',
    'reconnect_google' => 'Volver a conectar con Google',
    'back' => 'Atrás',
    'no_locations_available' => 'No hay ubicaciones disponibles',
    'no_locations_body' => 'No se ha devuelto ninguna ubicación de Google Business. Puede que todavía se estén cargando en Google; inténtalo dentro de un momento.',
    'connect_then_done' => 'Conecta una o varias ubicaciones y luego pulsa Hecho.',
    'done' => 'Hecho',
    'connected' => 'Conectada',
    'connect' => 'Conectar',
    'connecting' => 'Conectando…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Selecciona la ubicación del negocio',
    'connect_failed' => 'No se ha podido conectar la ubicación',
    'connected_title' => 'Conectada: :name',
    'connected_body' => 'Las reseñas se están sincronizando en segundo plano; aparecerán en la página de Ubicaciones en breve.',
    'location_fallback' => 'ubicación',
    'trial_started_title' => 'Tu prueba de 14 días ha comenzado',
    'trial_started_body' => 'Acceso completo hasta el :date, sin tarjeta. ¡Que la disfrutes!',
];
