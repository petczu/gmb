<?php

declare(strict_types=1);

return [
    'greeting' => 'Bonjour :name,',
    'signoff' => 'Merci,',
    'team' => 'L’équipe Repunio',

    'drip_competitors' => [
        'subject' => 'Savez-vous comment se porte le commerce d’à côté ?',
        'intro' => 'Vos avis sont sous contrôle. La question suivante que se pose tout gérant : suis-je devant la concurrence ou en train de décrocher ? Repunio peut le surveiller pour vous, avec la note et le nombre d’avis de n’importe quel établissement sur Google, chaque jour.',
        'tip' => 'Deux minutes suffisent : ouvrez Concurrents, cherchez le nom, ajoutez-le. Vous verrez ensuite qui prend de l’avance, de combien, et si votre note suit le rythme.',
        'cta' => 'Ajouter votre premier concurrent',
    ],

    'location_connected' => [
        'subject' => ':location est connecté',
        'intro' => 'Votre établissement :location est maintenant connecté. Nous importons ses avis depuis Google en ce moment même ; selon leur nombre, cela peut prendre quelques minutes.',
        'note' => 'Vous recevrez un autre e-mail dès que les avis seront arrivés.',
        'cta' => 'Voir les établissements',
    ],

    'location_synced' => [
        'subject' => 'Vos avis sont arrivés',
        'intro' => 'Le premier import est terminé. Voici ce qui est arrivé :',
        'note' => 'Désormais, les nouveaux avis arrivent automatiquement et vos règles d’automatisation s’y appliquent.',
        'cta' => 'Ouvrir la boîte de réception des avis',
    ],

    'drip_connect' => [
        'subject' => 'Votre compte est prêt. Il reste une étape',
        'intro' => 'Votre espace Repunio est en place, mais il est encore vide : les avis, les notes et les rapports viennent tous de votre fiche d’établissement Google, et aucune n’est connectée pour l’instant.',
        'tip' => 'Comptez deux minutes : ouvrez Établissements, cliquez sur Connecter, connectez-vous avec Google et choisissez votre établissement. Vos avis commencent à arriver aussitôt.',
        'cta' => 'Connecter votre établissement',
    ],

    'signup_code' => [
        'subject' => ':code est votre code d’inscription Repunio',
        'intro' => 'Saisissez ce code sur la page d’inscription pour confirmer votre adresse e-mail :',
        'note' => 'Le code est valable :minutes minutes. Si vous n’en êtes pas à l’origine, vous pouvez ignorer cet e-mail.',
    ],

    'beta_received' => [
        'subject' => 'Merci ! Votre demande d’accès est enregistrée',
        'intro' => 'Merci pour votre inscription ! Repunio est en bêta privée et nous activons les nouveaux comptes par petites vagues.',
        'note' => 'Nous vous écrirons dès que votre accès sera prêt. Rien d’autre à faire pour le moment.',
    ],

    'beta_approved' => [
        'subject' => 'Votre accès à Repunio est prêt',
        'intro' => 'Bonne nouvelle : votre compte a été activé. Vous pouvez maintenant vous connecter et tout configurer.',
        'note' => 'Commencez par connecter votre fiche d’établissement Google, vos avis sont importés en quelques minutes.',
        'cta' => 'Ouvrir Repunio',
    ],

    'welcome' => [
        'subject' => 'Bienvenue sur Repunio',
        'intro' => 'Votre compte est prêt. Repunio vous aide à collecter vos avis Google, à y répondre et à en rendre compte, le tout au même endroit.',
        'next' => 'Ensuite : connectez votre premier établissement et choisissez un plan pour démarrer votre essai gratuit de 14 jours.',
        'cta' => 'Ouvrir Repunio',
    ],

    'trial_ending' => [
        'subject' => 'Votre essai gratuit se termine dans :days jours',
        'intro' => 'Votre essai gratuit Repunio se termine le :date. Ajoutez un moyen de paiement dès maintenant pour que rien ne s’arrête : vos avis continuent de se synchroniser et les réponses IA de fonctionner.',
        'note' => 'Vous ne serez pas débité avant la fin de l’essai, et vous pouvez annuler à tout moment.',
        'cta' => 'Ajouter un moyen de paiement',
    ],

    'payment_succeeded' => [
        'subject' => 'Paiement reçu',
        'intro' => 'Nous avons bien reçu votre paiement de :amount. Votre abonnement Repunio est actif.',
        'cta' => 'Voir la facturation',
    ],

    'payment_failed' => [
        'subject' => 'Échec du paiement, action requise',
        'intro' => 'Nous n’avons pas pu traiter votre dernier paiement. Votre compte reste actif pendant :days jours ; merci de mettre à jour vos informations de facturation pour éviter toute interruption.',
        'cta' => 'Mettre à jour la facturation',
    ],

    'subscription_canceled' => [
        'subject' => 'Votre abonnement va être résilié',
        'intro' => 'Votre abonnement Repunio a été résilié. Vous conservez un accès complet jusqu’au :date, après quoi il ne sera pas renouvelé.',
        'note' => 'Vous avez changé d’avis ? Vous pouvez le reprendre à tout moment avant cette date, sans frais.',
        'cta' => 'Reprendre l’abonnement',
    ],

    'subscription_resumed' => [
        'subject' => 'Votre abonnement est de nouveau actif',
        'intro' => 'Votre abonnement Repunio a repris et se renouvellera normalement. Rien d’autre à faire.',
        'cta' => 'Voir la facturation',
    ],

    'ai_limit' => [
        'subject' => 'Vous avez utilisé toutes vos réponses IA ce mois-ci',
        'intro' => 'Vous avez atteint la limite mensuelle de réponses IA de votre plan :plan. Changez de plan pour une limite plus élevée, ou continuez à répondre à la main jusqu’au mois prochain.',
        'cta' => 'Voir les plans',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Échec de la recharge IA',
        'intro' => 'Nous avons tenté de recharger automatiquement vos réponses IA, mais le paiement n’est pas passé. Merci de mettre à jour votre carte pour que la recharge automatique continue de fonctionner.',
        'cta' => 'Mettre à jour la facturation',
    ],

    'new_reviews' => [
        'subject' => ':count nouvel(le)s avis pour votre établissement',
        'intro' => 'Vous avez :count nouvel(le)s avis pour :location.',
        'col_author' => 'Auteur',
        'col_rating' => 'Note',
        'col_location' => 'Établissement',
        'col_review' => 'Avis',
        'cta' => 'Voir les avis',
    ],

    'account_disconnected' => [
        'subject' => 'Action requise : votre connexion Google ne fonctionne plus',
        'intro' => 'La connexion Google de « :account » ne fonctionne plus, vos avis ne se synchronisent donc plus.',
        'detail' => 'Reconnectez le compte pour reprendre la synchronisation des avis et la publication des réponses.',
        'cta' => 'Reconnecter',
    ],

    'sync_restored' => [
        'subject' => 'Votre connexion Google est rétablie',
        'intro' => 'Bonne nouvelle : la connexion de « :account » est rétablie et la synchronisation a repris. Vos avis sont de nouveau à jour.',
        'cta' => 'Ouvrir Repunio',
    ],

    'negative_review' => [
        'subject' => 'Un avis :rating★ demande votre attention',
        'intro' => 'Un nouvel avis pour :business demande votre attention.',
        'col_author' => 'Auteur',
        'col_rating' => 'Note',
        'col_review' => 'Avis',
        'cta' => 'Répondre maintenant',
    ],

    'reply_failed' => [
        'subject' => 'Nous n’avons pas pu publier votre réponse',
        'intro' => 'Nous avons tenté de publier une réponse à un avis pour :business, mais cela a échoué.',
        'col_author' => 'Auteur',
        'col_review' => 'Avis',
        'detail' => 'Merci de réessayer de publier la réponse depuis l’application.',
        'detail_retry' => 'Cela semble temporaire, nous réessaierons donc automatiquement dans les prochaines heures. Rien à faire de votre côté. Si l’échec persiste, vous la retrouverez dans Avis → En échec.',
        'detail_not_found' => 'Google indique que cet avis n’existe plus. Il a peut-être été supprimé par son auteur ou filtré par Google. Rien à faire : le brouillon a été mis de côté et ne sera pas réessayé.',
        'detail_unauthorized' => 'La connexion Google n’est pas autorisée à répondre pour cet établissement, nous n’insisterons donc pas. Reconnectez le compte, puis publiez à nouveau la réponse depuis l’application.',
        'cta' => 'Ouvrir les validations',
    ],

    'post_failed' => [
        'subject' => 'Nous n’avons pas pu publier votre post Google',
        'intro' => 'Nous avons tenté de publier un post Google pour :business, mais cela a échoué. Le post figure dans votre calendrier avec l’erreur.',
        'detail' => 'Merci de réessayer de publier le post depuis l’application.',
        'detail_reason' => 'Motif : :reason',
        'cta' => 'Ouvrir les posts',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies en attente de validation',
        'intro' => 'Vous avez :count :replies en attente de votre validation. Relisez-les et validez-les pour qu’elles soient publiées.',
        'reply_word' => '{1}réponse|[2,*]réponses',
        'reply_label' => 'Réponse proposée',
        'cta' => 'Voir les validations',
    ],

    'review_goal' => [
        'subject_mid' => 'Votre objectif d’avis : où en est le mois',
        'subject_recap' => 'Bilan des avis pour :month',
        'intro_mid_ahead' => 'Beau rythme ! Vous avez :actual nouveaux avis ce mois-ci, au-dessus des :expected attendus à ce stade (objectif :goal). Continuez comme ça.',
        'intro_mid_on_track' => 'Vous êtes dans les temps : :actual nouveaux avis ce mois-ci, tout près des :expected attendus à ce stade (objectif :goal).',
        'intro_mid_behind' => 'Un petit rappel : vous avez :actual nouveaux avis ce mois-ci, en dessous des :expected attendus à ce stade (objectif :goal). Un coup d’accélérateur aiderait.',
        'intro_recap' => 'Voici comment :month s’est terminé : :actual nouveaux avis pour un objectif de :goal.',
        'col_location' => 'Établissement',
        'col_goal' => 'Objectif',
        'col_so_far' => 'À ce jour',
        'col_projected' => 'Projection',
        'col_pace' => 'Rythme',
        'col_got' => 'Obtenus',
        'col_vs_goal' => 'vs objectif',
        'col_vs_prev' => 'vs mois dernier',
        'status_ahead' => 'En avance',
        'status_on_track' => 'Dans les temps',
        'status_behind' => 'En retard',
        'cta' => 'Voir les avis',
    ],

    'coaching' => [
        'subject' => 'Votre objectif d’avis : gardons le cap',
        'intro_almost' => 'Si près du but ! Plus que :remaining pour atteindre votre objectif de :goal ce mois-ci. Vous y êtes presque !',
        'intro_behind' => 'Vous en êtes à :actual sur :goal ce mois-ci. Un effort régulier cette semaine vous remet dans le rythme. Voici quelques idées.',
        'intro_on_track' => 'Beau travail ! :actual sur :goal, pile dans le rythme. Quelques demandes cette semaine entretiennent l’élan.',
        'intro_ahead' => 'Quel élan ! :actual sur :goal, en avance sur le plan. Continuez avec ces idées.',
        'steady' => 'Un conseil : étalez vos demandes sur plusieurs jours. Un afflux soudain d’avis paraît suspect à Google et peut être filtré. La régularité paie.',
        'cta' => 'Ouvrir les avis',
    ],

    'goal_reached' => [
        'subject' => 'Objectif atteint ! :goal avis ce mois-ci ! 🎉',
        'intro' => 'Félicitations ! Vous avez atteint votre objectif de :goal nouveaux avis ce mois-ci. C’est un vrai élan pour votre réputation.',
        'note' => 'Gardez cette habitude à un rythme régulier et le mois prochain sera encore plus simple.',
        'cta' => 'Ouvrir les avis',
    ],

    'review_anomaly' => [
        'subject' => 'À surveiller : :count point(s) à vérifier sur vos avis',
        'intro' => 'Nous avons repéré quelque chose qui mérite un coup d’œil sur vos avis :',
        'stalled' => 'aucun nouvel avis depuis :days jours, alors que cet établissement est habituellement actif.',
        'negative_streak' => ':count avis à faible note en 3 jours. Répondez vite pour limiter les dégâts.',
        'spike' => 'pic inhabituel : :recent avis en 7 jours (normalement environ :baseline par semaine). Bonne nouvelle, ou à vérifier côté spam.',
        'rating_drop' => 'la note baisse : :recent★ récemment contre :prior★ avant.',
        'cta' => 'Ouvrir les avis',
    ],

    'invite' => [
        'subject' => 'Vous avez été invité à rejoindre :workspace sur Repunio',
        'greeting' => 'Bonjour,',
        'intro' => ':inviter vous invite à rejoindre :workspace sur Repunio en tant que :role.',
        'note' => 'Cette invitation expire dans 14 jours. Si vous ne l’attendiez pas, vous pouvez ignorer cet e-mail.',
        'cta' => 'Accepter l’invitation',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Tous les avis, une seule boîte de réception',
        'intro' => 'Tous les avis de vos établissements arrivent dans une seule boîte de réception. Filtrez par note, établissement ou avis sans réponse, et répondez en deux clics.',
        'tip' => 'Essayez tout de suite : ouvrez un avis et cliquez sur Générer avec l’IA. Vous obtenez un brouillon prêt, dans votre ton, que vous pouvez modifier avant publication.',
        'cta' => 'Ouvrir vos avis',
    ],
    'drip_automation' => [
        'subject' => 'Passez vos réponses en pilote automatique',
        'intro' => 'Créez un agent IA qui connaît votre établissement et votre ton, puis laissez les règles de réponse automatique traiter les avis courants à votre place.',
        'tip' => 'Pas encore prêt pour le pilote automatique ? Utilisez la file de validation : l’IA rédige, vous validez d’un clic.',
        'cta' => 'Configurer les automatisations',
    ],
    'drip_growth' => [
        'subject' => 'Récoltez plus d’avis ce mois-ci',
        'intro' => 'Fixez un objectif mensuel d’avis par établissement : nous suivons le rythme, célébrons les étapes et vous alertons en cas d’anomalie.',
        'tip' => 'Créez votre page de collecte d’avis : un lien court et un QR code qui envoient vos clients satisfaits directement vers votre formulaire d’avis Google ou TripAdvisor.',
        'cta' => 'Créer votre page d’avis',
    ],
    'drip_reports' => [
        'subject' => 'Des rapports vraiment lus',
        'intro' => 'Composez un rapport de performance à partir de blocs : indicateurs, résumé IA, mentions du personnel, thématiques. Téléchargez-le en PDF ou partagez un lien.',
        'tip' => 'Réglez-le une fois, envoyez-le chaque mois : programmez le rapport et il arrive automatiquement dans les boîtes de réception, dans la langue de votre choix.',
        'cta' => 'Créer un rapport',
    ],
    'drip_team' => [
        'subject' => 'Embarquez votre équipe',
        'intro' => 'Invitez vos collègues avec des rôles, ou ajoutez des invités qui reçoivent seulement les notifications et les rapports, sans connexion.',
        'tip' => 'Décidez qui reçoit quel e-mail dans les Paramètres, puis dirigez les alertes de nouveaux avis vers les personnes qui les traitent.',
        'cta' => 'Inviter votre équipe',
    ],
    'drip_member' => [
        'subject' => 'Se repérer dans Repunio',
        'intro' => 'Vous avez été ajouté à un espace de travail. La boîte de réception des avis, c’est là que tout se passe : filtrer, répondre, terminé.',
        'tip' => 'Choisissez la langue de l’interface et des e-mails dans votre profil pour que tout arrive comme vous le souhaitez.',
        'cta' => 'Ouvrir Repunio',
    ],
    'drip_unsubscribe' => 'Trop de conseils ? :link',
    'drip_unsubscribe_link' => 'Se désabonner de ces e-mails',

    'unsubscribed_title' => 'Vous êtes désabonné',
    'unsubscribed_body' => 'Vous ne recevrez plus les conseils produit et les e-mails de prise en main. Les e-mails importants de compte et de facturation continuent d’arriver. Vous avez changé d’avis ? Réactivez-les dans :link.',
    'unsubscribed_profile' => 'votre profil',
];
