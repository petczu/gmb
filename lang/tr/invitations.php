<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'Davet',

    // Accept page
    'youre_invited' => 'Davet edildiniz',
    'join_title' => ':workspace çalışma alanına katılın',
    'join_body' => 'Repunio\'da :workspace çalışma alanına :role olarak davet edildiniz.',
    'accept_button' => 'Kabul et & katıl',

    // Invalid / expired page
    'invalid_title' => 'Davet kullanılamıyor',
    'invalid_body' => 'Bu davet bağlantısı artık geçerli değil. Süresi dolmuş ya da daha önce kullanılmış olabilir. Lütfen sizi davet eden kişiden yeni bir tane isteyin.',
    'go_to_app' => 'Repunio\'ya git',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'Bu davet başka birine ait',
    'wrong_body' => ':invited adresine gönderildi ama siz :current olarak giriş yaptınız.',
    'wrong_hint' => 'Bağlantıyı onlara iletin ya da çıkış yapıp bu e-posta ile giriş yaparak daveti kendiniz kabul edin.',
    'back_to_app' => 'Uygulamaya dön',
    'sign_out' => 'Çıkış yap',

    'roles' => [
        'owner' => 'Sahip',
        'admin' => 'Yönetici',
        'manager' => 'Müdür',
        'member' => 'Üye',
        'viewer' => 'Görüntüleyici',
        'guest' => 'Misafir',
    ],
];
