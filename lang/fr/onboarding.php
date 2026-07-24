<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Renseignez les informations de votre entreprise',
    'step_company_hint' => 'Pays et informations de facturation utilisés sur les factures et les rapports.',
    'step_plan_label' => 'Choisissez un plan',
    'step_plan_hint' => 'Démarrez votre essai gratuit de 14 jours, sans carte bancaire.',
    'step_location_label' => 'Connectez votre premier établissement',
    'step_location_hint' => 'Liez une fiche d’établissement Google pour commencer à récupérer les avis.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Configurez votre espace de travail',
    'wiz_plan_done' => '✓ Votre plan est actif. Passez à l’étape suivante.',
    'wiz_plan_pick' => 'Choisissez un plan',
    'wiz_interval' => 'Périodicité de facturation',
    'wiz_monthly' => 'Mensuelle',
    'wiz_yearly' => 'Annuelle',
    'wiz_start_trial' => 'Démarrer l’essai gratuit de 14 jours',
    'wiz_trial_note' => 'Votre essai gratuit de 14 jours démarre dès que vous continuez. Sans carte bancaire.',
    'wiz_go_checkout' => 'Continuer vers le paiement',
    'wiz_plan_required' => 'Choisissez un plan et finalisez le paiement pour continuer.',
    'wiz_location_body' => 'Liez votre fiche d’établissement Google pour que nous puissions récupérer vos avis. Vous serez redirigé vers Google pour autoriser l’accès, puis vous choisirez l’établissement à connecter.',
    'wiz_connect_google' => 'Connecter la fiche d’établissement Google',
    'wiz_skip_location' => 'Plus tard',
    'skipped_title' => 'Tout est prêt',
    'skipped_body' => 'Vous pouvez connecter votre fiche d’établissement Google à tout moment depuis la page Établissements.',
    'wiz_per_location' => 'par établissement / mois',
    'wiz_plan_desc_starter' => 'Boîte de réception des avis, réponses manuelles et rapports de base.',
    'wiz_plan_desc_growth' => 'Ajoute les réponses automatiques par IA, les rapports programmés et les comparatifs.',
    'wiz_plan_desc_pro' => 'Tout, plus la marque blanche, l’API, MCP et l’accès client.',

    // Onboarding overlay
    'welcome_title' => 'Bienvenue, configurons votre compte',
    'welcome_subtitle' => 'Quelques étapes rapides et vous êtes prêt.',
    'continue_step' => 'Continuer : :label',
    'enter_app' => 'Entrer dans l’application →',
    'sign_out' => 'Se déconnecter',

    // Pending-deletion overlay
    'deletion_title' => 'La suppression de cet espace est programmée',
    'deletion_body' => 'Toutes les données seront définitivement supprimées le <strong>:date</strong>. Vous pouvez encore annuler et conserver votre espace.',
    'cancel_deletion' => 'Annuler la suppression',

    // Grace banner
    'grace_banner' => '⚠️ Nous n’avons pas pu traiter votre dernier paiement. Votre service reste actif jusqu’au <strong>:date</strong>, merci de',
    'update_your_billing' => 'mettre à jour votre facturation',

    // Paywall overlay
    'payment_problem_title' => 'Un problème est survenu avec votre paiement',
    'needs_plan_title' => 'Choisissez un plan pour commencer',
    'payment_problem_body' => 'Votre accès est suspendu car nous n’avons pas pu traiter le paiement. Mettez à jour votre facturation pour continuer.',
    'needs_plan_body' => 'Choisissez un plan pour activer les avis, les réponses IA et les rapports pour vos établissements. Essai gratuit de 14 jours.',
    'update_billing' => 'Mettre à jour la facturation',
    'view_plans' => 'Voir les plans',

    // Connect-select-location page
    'connecting_location' => 'Connexion de l’établissement…',
    'choose_location' => 'Choisissez l’établissement Google à connecter à cet espace.',
    'could_not_load' => 'Impossible de charger les établissements',
    'pending_expired_title' => 'Session Google expirée',
    'pending_expired' => 'L’autorisation Google n’est valable que peu de temps et celle-ci a expiré. Reconnectez-vous et choisissez à nouveau vos établissements, ce sera rapide.',
    'reconnect_google' => 'Se reconnecter à Google',
    'back' => 'Retour',
    'no_locations_available' => 'Aucun établissement disponible',
    'no_locations_body' => 'Aucun établissement Google n’a été renvoyé. Ils sont peut-être encore en cours de chargement côté Google, réessayez dans un instant.',
    'connect_then_done' => 'Connectez un ou plusieurs établissements, puis cliquez sur Terminé.',
    'done' => 'Terminé',
    'connected' => 'Connecté',
    'connect' => 'Connecter',
    'connecting' => 'Connexion…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Sélectionner l’établissement',
    'connect_failed' => 'Impossible de connecter l’établissement',
    'connected_title' => 'Connecté : :name',
    'connected_body' => 'Les avis se synchronisent en arrière-plan ; ils apparaîtront sur la page Établissements sous peu.',
    'location_fallback' => 'établissement',
    'trial_started_title' => 'Votre essai de 14 jours a commencé',
    'trial_started_body' => 'Accès complet jusqu’au :date, sans carte bancaire. Bonne découverte !',
];
