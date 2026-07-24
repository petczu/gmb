<?php

declare(strict_types=1);

return [
    'col_name' => '名前',
    'col_email' => 'メール',
    'col_role' => '役割',

    'edit' => '編集',
    'location_access' => '店舗へのアクセス',
    'location_access_helper' => 'すべての店舗にアクセスできるようにするには空欄のままにしてください。',
    'guest_location_helper' => 'すべての店舗について通知するには空欄のままにするか、特定の店舗を選択してください。',

    'change_role' => '役割を変更',
    'remove' => '削除',
    'add_member' => 'メンバーを追加',
    'add_member_email_helper' => 'このワークスペースに参加する招待をメールで送信します。',
    'add_guest' => 'ゲストを追加',
    'add_guest_helper' => 'ゲストは、あなたが振り分けた通知のみを受け取ります。ログインもワークスペースへのアクセスもありません。',
    'guest_language' => '言語',
    'guest_language_helper' => 'この連絡先への通知とレポートはこの言語で送信されます。',
    'name' => '名前',

    // Notifications
    'member_updated' => 'メンバーを更新しました',
    'role_updated' => '役割を:roleに更新しました',
    'member_removed' => 'メンバーを削除しました',
    'invitation_sent' => '招待を送信しました',
    'guest_added' => 'ゲストを追加しました',

    // Pending invitations
    'pending_hint' => '送信済みですが、まだ承認されていません。誤ったアドレスに送った場合は、メールを再送するか招待を無効化してください。',
    'invite_resend' => '再送',
    'invite_revoke' => '無効化',
    'invite_revoke_desc' => '招待リンクがただちに機能しなくなります。相手には通知されません。',
    'invite_revoked' => '招待を無効化しました',
    'col_status' => 'ステータス',
    'status_active' => 'アクティブ',
    'status_pending' => '保留中',
    'role_hint_member' => 'メールで招待を受け取り、自分のアカウントでサインインします。',
    'role_hint_guest' => 'ゲストはサインインできません。あなたが振り分けた通知（新しいレビュー、レポート）のみを受け取ります。',
];
