<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhook\'lar bir Pro özelliğidir',
    'pro_only_body' => 'Bir yorum geldiği, bir yanıt yayınlandığı, bir hedefe ulaşıldığı ya da bir anormallik tespit edildiği anda imzalı bir HTTP POST alın. Uç noktalar eklemek için Pro\'ya yükseltin.',
    'see_plans' => 'Planları gör',

    'intro' => 'Abone olunan her olayda uç noktanıza imzalı bir JSON yükü, yeniden denemelerle POST ederiz. X-Webhook-Signature başlığını uç nokta gizli anahtarınıza karşı doğrulayın.',

    'docs_link' => 'Webhook dokümantasyonu',
    'empty' => 'Henüz webhook uç noktası yok.',
    'col_url' => 'URL',
    'col_events' => 'Olaylar',
    'col_active' => 'Etkin',
    'col_last' => 'Son tetiklenme',

    'create' => 'Uç nokta ekle',
    'create_heading' => 'Webhook uç noktası ekle',
    'edit' => 'Düzenle',
    'delete' => 'Sil',
    'saved' => 'Uç nokta kaydedildi',
    'created' => 'Uç nokta eklendi',
    'deleted' => 'Uç nokta silindi',

    'field_name' => 'Ad (isteğe bağlı)',
    'field_url' => 'Uç nokta URL\'si',
    'field_events' => 'Olaylar',
    'field_active' => 'Etkin',

    'secret' => 'Gizli anahtar',
    'secret_heading' => 'İmzalama gizli anahtarı',
    'secret_desc' => 'Yük imzasını doğrulamak için bunu kullanın.',
    'signature_hint' => 'Her istek imzalanır:',

    'deliveries' => 'İletimler',
    'deliveries_heading' => 'Son iletimler',
    'no_deliveries' => 'Henüz iletim yok.',
    'attempts' => 'deneme',
    'resend' => 'Yeniden gönder',
    'resent' => 'İletim yeniden kuyruğa alındı',
    'status_pending' => 'Beklemede',
    'status_success' => 'İletildi',
    'status_failed' => 'Başarısız',

    'event_review_created' => 'Yeni yorum',
    'event_reply_published' => 'Yanıt yayınlandı',
    'event_goal_reached' => 'Hedefe ulaşıldı',
    'event_anomaly_detected' => 'Anormallik tespit edildi',
];
