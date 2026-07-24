<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => '招待',

    // Accept page
    'youre_invited' => '招待されています',
    'join_title' => ':workspaceに参加',
    'join_body' => 'Repunioの:workspaceに:roleとして招待されました。',
    'accept_button' => '承認して参加',

    // Invalid / expired page
    'invalid_title' => '招待を利用できません',
    'invalid_body' => 'この招待リンクは無効になっています。有効期限が切れたか、すでに使用された可能性があります。招待してくれた方に新しいリンクを依頼してください。',
    'go_to_app' => 'Repunioへ',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'この招待は別の方宛てです',
    'wrong_body' => 'この招待は:invited宛てに送信されましたが、現在:currentとしてサインインしています。',
    'wrong_hint' => 'リンクをその方に転送するか、サインアウトしてそのメールアドレスでサインインし、ご自身で承認してください。',
    'back_to_app' => 'アプリに戻る',
    'sign_out' => 'サインアウト',

    'roles' => [
        'owner' => 'オーナー',
        'admin' => '管理者',
        'manager' => 'マネージャー',
        'member' => 'メンバー',
        'viewer' => '閲覧者',
        'guest' => 'ゲスト',
    ],
];
