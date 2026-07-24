<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Convite',

    // Accept page
    'youre_invited' => 'Você foi convidado',
    'join_title' => 'Entrar em :workspace',
    'join_body' => 'Você foi convidado para :workspace no Repunio como :role.',
    'accept_button' => 'Aceitar e entrar',

    // Invalid / expired page
    'invalid_title' => 'Convite indisponível',
    'invalid_body' => 'Este link de convite não é mais válido. Ele pode ter expirado ou já ter sido usado. Peça um novo para quem convidou você.',
    'go_to_app' => 'Ir para o Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'Este convite é para outra pessoa',
    'wrong_body' => 'Ele foi enviado para :invited, mas você está conectado como :current.',
    'wrong_hint' => 'Encaminhe o link para essa pessoa, ou saia e entre com aquele e-mail para aceitar você mesmo.',
    'back_to_app' => 'Voltar ao aplicativo',
    'sign_out' => 'Sair',

    'roles' => [
        'owner' => 'Proprietário',
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'member' => 'Membro',
        'viewer' => 'Visualizador',
        'guest' => 'Convidado',
    ],
];
