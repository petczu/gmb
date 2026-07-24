<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Konum',
    'col_author' => 'Yazar',
    'col_review' => 'Yorum',
    'col_rating' => 'Puan',
    'only_rating' => 'Yalnızca puan',
    'col_reply' => 'Yanıt',
    'col_status' => 'Durum',
    'col_replied_by' => 'Yanıtlayan',
    'col_date' => 'Tarih',
    'replied_ai' => 'Yapay zeka',
    'replied_human' => 'Ekip',
    'replied_assistant' => 'Asistan',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— yanıt yok —',
    'status_replied' => 'Yanıtlandı',
    'status_pending' => 'Beklemede',
    'status_scheduled' => 'Zamanlandı',
    'scheduled_for' => ':datetime tarihinde gönderilir',
    'replied_at' => ':datetime tarihinde yanıtlandı',
    'status_failed' => 'Başarısız',

    // Filters
    'review_date' => 'Yorum tarihi',
    'filter_from' => ':date tarihinden',
    'filter_to' => ':date tarihine',
    'reply_status' => 'Yanıt durumu',
    'review_text' => 'Yorum metni',
    'with_text' => 'Metinli',
    'rating_only' => 'Yalnızca puan',
    'photos' => 'Fotoğraflar',
    'with_photos' => 'Fotoğraflı',
    'without_photos' => 'Fotoğrafsız',

    // Reply action
    'edit_reply' => 'Yanıtı düzenle',
    'save_reply' => 'Kaydet',
    'reply' => 'Yanıtla',
    'reply_to_review' => 'Yoruma yanıt ver',
    'no_written_review' => 'Yazılı yorum yok, yalnızca puan.',
    'translated_by_google' => '🌐 Google tarafından çevrildi',
    'ai_agent' => 'Yapay zeka aracısı',
    'default_agent' => 'Varsayılan aracı',
    'your_reply' => 'Yanıtınız',
    'generate_with_ai' => 'Yapay zeka ile oluştur',
    'generate' => 'Oluştur',
    'generating' => 'Yanıtınız oluşturuluyor…',
    'cancel' => 'İptal',
    'add_emoji' => 'Emoji ekle',
    'show_translation' => 'Çeviriyi göster (:language)',
    'translation_label' => 'Çeviri (:language)',
    'translation_failed' => 'Çeviri başarısız',
    'hide_emoji' => 'Emojiyi gizle',
    'delete_reply' => 'Yanıtı sil',
    'delete_reply_desc' => 'Bu, yanıtı Google\'dan kaldırır. Yorumun kendisi etkilenmez.',
    'delete_confirm' => 'Sil',
    'submit_heading' => 'Yanıtınız yayınlansın mı?',
    'submit_desc' => 'Bu, yanıtınızı Google\'da herkese açık olarak gönderir, yorumu gören herkese görünür.',
    'submit_confirm' => 'Yayınla',

    // AI cost hints
    'cost_generic' => 'Bu, yapay zeka ile bir yanıt oluşturur.',
    'cost_all_used' => 'Bu ay tüm yapay zeka yanıtlarınızı kullandınız. Bir paket yükleyin, yükseltin ya da yanıtı manuel yazın.',
    'cost_credit' => 'Bu 1 kredi kullanır (:count kaldı).',
    'cost_monthly' => 'Bu, aylık yapay zeka yanıtlarınızdan 1 tanesini kullanır, :count kaldı.',

    // Notifications
    'reply_deleted' => 'Yanıt silindi',
    'no_changes' => 'Kaydedilecek değişiklik yok',
    'reply_published' => 'Yanıt yayınlandı',
    'reply_failed' => 'Yanıt gönderilemedi',
    'ai_limit_reached' => 'Yapay zeka sınırına ulaşıldı',
    'ai_limit_body' => 'Bu ay tüm yapay zeka yanıtlarını kullandınız. Manuel olarak düzenleyin ya da daha yüksek bir sınır için yükseltin.',
    'generation_failed' => 'Oluşturma başarısız',
    'reply_generated' => 'Yanıt oluşturuldu',
    'retry' => 'Tekrar dene',
    'retry_heading' => 'Bu yanıt tekrar denensin mi?',
    'retry_desc' => 'Tekrar deneyeceğiz: taslaklanan yanıtı yeniden gönderelim ya da yapay zeka adımı başarısız olduysa yeniden oluşturalım.',
    'retry_queued' => 'Yanıt yeniden kuyruğa alındı',
    'retry_nothing' => 'Tekrar denenecek bir şey yok. Bunun yerine manuel yanıtlayın.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'Tümü',
    'tab_needs_approval' => 'Onay gerekiyor',
    'tab_scheduled' => 'Zamanlanmış',
    'tab_published' => 'Yayınlanan',
    'tab_failed' => 'Başarısız',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} E-posta bildiriminizden 1 yorum gösteriliyor|[2,*] E-posta bildiriminizden :count yorum gösteriliyor',
    'from_email_clear' => 'Tüm yorumları göster',
];
