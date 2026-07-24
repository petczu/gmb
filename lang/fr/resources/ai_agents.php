<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Aucun agent IA pour l’instant',
    'empty_desc' => 'Créez un agent IA pour rédiger des réponses et alimenter vos automatisations avec la voix de votre marque.',
    'empty_cta' => 'Nouvel agent IA',

    // Table
    'col_native_lang' => 'Langue d’origine',
    'col_default' => 'Par défaut',
    'col_updated' => 'Mis à jour',
    'test_preview' => 'Tester et prévisualiser',
    'test_heading' => 'Tester la réponse',
    'close' => 'Fermer',
    'no_reviews_to_test' => 'Aucun avis sur lequel tester pour l’instant, synchronisez-en d’abord.',
    'generation_failed' => 'Échec de la génération : :error',
    'set_default' => 'Définir par défaut',

    // Form
    'section' => 'Votre agent IA',
    'section_desc' => 'Donnez un nom à l’agent et décrivez comment il doit répondre. Utilisé par les automatisations de réponse et le bouton « rédiger avec l’IA ».',
    'describe' => 'Décrivez votre agent',
    'describe_helper' => 'Les consignes complètes : comment classer l’avis et comment répondre, ton et style, règles de personnalisation, etc.',
    'tone' => 'Ton',
    'reply_native' => 'Répondre dans la langue de l’avis',
    'reply_native_helper' => 'L’agent répond dans la même langue que celle de l’avis.',
    'default_agent' => 'Agent par défaut',
    'default_agent_helper' => 'Utilisé quand une automatisation ne précise pas d’agent.',

    // Knowledge base
    'knowledge' => 'Base de connaissances (facultatif)',
    'knowledge_helper' => 'Les informations sur votre établissement que l’agent peut utiliser dans ses réponses : horaires, règles, noms des salles ou prestations, offres, questions fréquentes. Il s’en tient aux faits et n’invente rien au-delà.',
    'knowledge_ph' => 'ex. Ouvert du lundi au dimanche de 10h à 22h. Salles : Le Braquage, Évasion, Le Manoir. Groupes de 2 à 6. Réservation sur example.com ou au +33 ...',

    // Test panel
    'test_section' => 'Tester sur un avis',
    'test_section_desc' => 'Choisissez un avis réel et générez un brouillon avec les réglages actuels (non enregistrés), puis ajustez.',
    'test_pick_review' => 'Avis',
    'test_pick_placeholder' => 'Choisissez un avis synchronisé…',
    'test_review_text' => 'Avis',
    'test_generate' => 'Générer un brouillon',
    'test_result' => 'Brouillon généré',
    'test_need_review' => 'Choisissez d’abord un avis pour le test.',

    // AI description generator
    'generate_label' => 'Générer avec l’IA',
    'generate_heading' => 'Générer la description avec l’IA',
    'generate_desc' => 'Indiquez votre site web et/ou quelques mots sur l’établissement, et l’IA rédigera les consignes de l’agent. Vous pourrez modifier le résultat ensuite.',
    'generate_submit' => 'Générer',
    'generate_url' => 'URL du site web',
    'generate_notes' => 'Quelque chose à ajouter (facultatif)',
    'generate_notes_ph' => 'ex. restaurant italien familial, accent sur l’accueil chaleureux, mentionner la terrasse en été',
    'generate_need_input' => 'Ajoutez d’abord l’URL d’un site ou une courte description.',
    'generate_rate_limited' => 'Trop de générations. Patientez un peu et réessayez.',
    'generate_done' => 'Description générée, relisez-la et ajustez si besoin.',
    'generate_failed' => 'Impossible de générer la description. Réessayez ou rédigez-la à la main.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Règles communes',
    'shared_rules_heading' => 'Règles de réponse communes',
    'shared_rules_desc' => 'Ces règles s’appliquent par-dessus tous les agents, dans chaque réponse IA. Parfait pour les corrections de style que vous ne voulez pas répéter agent par agent.',
    'shared_rules_placeholder' => "ex.\nEn français, écrire « salle » et jamais « room ».\nNe jamais promettre de remise ni de remboursement.\nSigner les réponses sans nom.",
    'shared_rules_save' => 'Enregistrer les règles',
    'shared_rules_saved' => 'Règles communes enregistrées',
];
