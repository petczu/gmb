<?php

declare(strict_types=1);

return [
    // Deletion-scheduled section
    'deletion_section' => '⚠️ 削除が予定されています',
    'deletion_section_desc' => 'このワークスペースは完全な削除が予定されています。維持するには上の「削除をキャンセル」を使用してください。',
    'permanent_deletion_on' => '完全削除日',

    // Company profile section
    'profile_section' => '会社プロフィール',
    'profile_section_desc' => 'アプリ全体およびレポートで使用されます。',
    'entity_type' => 'このアカウントの帰属先',
    'entity_company' => '会社',
    'entity_individual' => '個人',
    'display_name' => '表示名',
    'legal_name' => '正式な会社名',
    'contact_email' => '連絡先メール',
    'contact_phone' => '連絡先電話',

    // Billing details section
    'billing_section' => '請求情報',
    'billing_section_desc' => 'チェックアウトと請求書の事前入力に使用されます。',
    'country' => '国',
    'vat_number' => 'VAT番号',
    'vat_helper' => 'チェックアウトに事業者向け / リバースチャージのVATを追加します。',
    'address_line1' => '住所1',
    'address_line2' => '住所2',
    'postal_code' => '郵便番号',
    'city' => '市区町村',

    // Branding section
    'branding_section' => 'ブランディング',
    'branding_section_pro' => 'ブランディング（Pro）',
    'branding_desc' => 'あなたのロゴと色がホワイトラベルレポートに表示されます。',
    'branding_desc_locked' => '🔒 ホワイトラベルブランディング（レポート上のロゴ + 色）はProプランでご利用いただけます。',
    'business_category' => '業種',
    'business_category_placeholder' => 'カテゴリを選択',
    'business_category_helper' => 'コーチングメール内のレビュー収集のヒントを最適化するために使用されます。',
    'logo' => 'ロゴ',
    'logo_helper' => 'ワークスペースの切り替え画面に表示されます。Proプランでは、レポートにもブランドとして表示されます。',
    'brand_color' => 'ブランドカラー',

    // Header actions
    'cancel_deletion' => '削除をキャンセル',
    'cancel_deletion_heading' => '予定された削除をキャンセル',
    'cancel_deletion_desc' => 'あなたのワークスペースとそのすべてのデータが維持され、削除リクエストが取り消されます。',
    'cancel_deletion_submit' => 'ワークスペースを維持する',
    'delete_workspace' => 'ワークスペースを削除',
    'delete_workspace_heading' => 'このワークスペースを削除',
    'delete_workspace_desc' => 'これにより、このワークスペース内のすべて、レビュー、店舗、レポート、AIコンテンツの完全な削除が予定され、サブスクリプションが解約されます。30日以内であれば取り消せます。それ以降は元に戻せません。',
    'delete_workspace_submit' => '削除を予定',
    'confirm_name' => '確認のためワークスペース名を入力してください',

    // Notifications
    'deletion_cancelled' => '削除をキャンセルしました。ワークスペースは安全です',
    'name_mismatch' => '名前が一致しなかったため、何も削除されませんでした',
    'deletion_scheduled' => 'ワークスペースの削除を予定しました',
    'deletion_scheduled_body' => 'このページから30日以内であればキャンセルできます。',
    'settings_saved' => '会社設定を保存しました',
];
