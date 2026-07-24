<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Onaylanacak bir şey yok',
    'empty_desc' => 'Otomasyonlar onay gerektiren yanıtlar taslakladığında burada görünürler.',

    // Columns
    'col_location' => 'Konum',
    'col_author' => 'Yazar',
    'col_rating' => 'Puan',
    'col_review' => 'Yorum',
    'col_ai_reply' => 'Yapay zeka yanıtı',
    'col_status' => 'Durum',
    'col_source' => 'Kaynak',
    'col_generated' => 'Oluşturuldu',
    'source_ai' => 'Yapay zeka',
    'source_template' => 'Şablon',

    // Statuses
    'status_pending' => 'Beklemede',
    'status_scheduled' => 'Zamanlandı',
    'status_published' => 'Yayınlandı',
    'status_skipped' => 'Atlandı',
    'status_failed' => 'Başarısız',
    'status_indicator' => 'Durum: :status',
    'scheduled_for' => ':time gönderilir',

    // Actions
    'approve' => 'Onayla & yayınla',
    'approve_publish' => 'Onayla & yayınla',
    'edit_publish' => 'Düzenle & yayınla',
    'review_reply' => 'İncele & yanıtla',
    'reply' => 'Yanıtla',
    'reject' => 'Reddet',

    // Filters
    'filter_date' => 'Yorum tarihi',
    'filter_from' => ':date tarihinden',
    'filter_to' => ':date tarihine',

    // Notifications
    'reply_published' => 'Yanıt yayınlandı',

    'approve_selected' => 'Seçilenleri onayla & yayınla',
    'reject_selected' => 'Seçilenleri reddet',
    'bulk_approve_confirm' => 'Seçili tüm yanıtlar Google\'a yayınlansın mı? Kuyruğa alınırlar ve önümüzdeki dakikalarda otomatik olarak gönderilirler.',
    'bulk_reject_confirm' => 'Seçili tüm taslaklar reddedilsin mi?',
    'bulk_queued' => ':count yanıt yayınlanmak üzere kuyruğa alındı',
    'bulk_queued_body' => 'Önümüzdeki dakikalarda otomatik olarak yayınlanırlar. Her başarısızlık, nedeniyle birlikte Başarısız filtresinde görünür.',
    'bulk_rejected' => ':count taslak reddedildi',
    'publish_failed_title' => 'Yayınlama başarısız',
    'publish_not_found' => 'Google, bu yorumun artık var olmadığını söylüyor. Yazarı tarafından silinmiş ya da konum yeni bir hesap altında yeniden bağlanmış olabilir. Taslak başarısız olarak işaretlendi.',
    'publish_error' => 'Yanıt yayınlanamadı. Taslak başarısız olarak işaretlendi: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google, yanıt verilecek bu yorumu ya da konumu bulamadı. Kaldırılmış olabilir ya da bu konum için yanıtlar kullanılamıyor olabilir.',
    'error_rate_limited' => 'Google, yanıtların ne kadar hızlı gönderilebileceğini sınırlıyor. Otomatik olarak tekrar denenecek.',
    'error_unauthorized' => 'Google bağlantısı burada yanıt vermeye yetkili değil. Hesabı yeniden bağlayıp tekrar deneyin.',
    'error_generic' => 'Yanıt gönderilemedi. Lütfen daha sonra tekrar deneyin.',
    'draft_rejected' => 'Taslak reddedildi',

    // Scheduled items
    'post_now' => 'Şimdi gönder',
    'post_now_confirm' => 'Yanıt, zamanlanmış saatini atlayarak hemen Google\'a yayınlanır.',
    'post_now_queued' => 'Yanıt yayınlanmak üzere kuyruğa alındı',
    'post_now_queued_body' => 'Önümüzdeki birkaç dakika içinde gönderilir.',
    'cancel_scheduled' => 'İptal et',
    'cancel_scheduled_confirm' => 'Bu zamanlanmış yanıt iptal edilsin mi? Gönderilmeyecek.',
    'schedule_cancelled' => 'Zamanlanmış yanıt iptal edildi',

    // List tabs
    'tab_pending' => 'Onay gerekiyor',
    'tab_all' => 'Tümü',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Seçilenleri şimdi yayınla',
    'bulk_publish_now_confirm' => 'Seçili yanıtlar zamanlanmış saatlerini atlar ve önümüzdeki birkaç dakika içinde gönderilir.',
    'cancel_scheduled_selected' => 'Zamanlamayı iptal et',
];
