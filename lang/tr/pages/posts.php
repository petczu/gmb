<?php

declare(strict_types=1);

return [
    'nav' => 'Gönderiler',
    'title' => 'Google gönderileri',

    'empty' => 'Henüz gönderi yok.',
    'empty_desc' => 'Google profilinizde haberleri, teklifleri ya da etkinlikleri göstermek için ilk gönderinizi oluşturun.',

    'not_configured_title' => 'İçerik yayınlama yapılandırılmadı',
    'not_configured_body' => 'Google gönderilerini etkinleştirmek için sunucu ortamında ZERNIO_API_KEY değerini ayarlayın.',

    'col_created' => 'Oluşturuldu',
    'col_type' => 'Tür',
    'col_caption' => 'Metin',
    'col_locations' => 'Konumlar',
    'col_status' => 'Durum',
    'col_scheduled' => 'Zamanlandığı tarih',

    'type_update' => 'Güncelleme',
    'type_offer' => 'Teklif',
    'type_event' => 'Etkinlik',
    'type_photo' => 'Fotoğraf',

    'status_published' => 'Yayınlandı',
    'status_scheduled' => 'Zamanlandı',
    'status_in_progress' => 'Yayınlanıyor…',
    'status_failed' => 'Başarısız',
    'status_draft' => 'Taslak',

    'create' => 'Yeni gönderi',
    'create_heading' => 'Yeni Google gönderisi',
    'submit' => 'Yayınla',

    'field_type' => 'Gönderi türü',
    'field_locations' => 'Konumlar',
    'field_caption' => 'Metin',
    'field_image' => 'Görsel',
    'field_image_helper' => 'Google\'ın görseli getirebilmesi için herkese açık şekilde erişilebilir olması gerekir: yüklemeler yalnızca herkese açık bir sunucudan çalışır, yerel bir makineden değil.',
    'field_photo_category' => 'Fotoğraf kategorisi',
    'field_title' => 'Başlık',
    'field_starts' => 'Başlangıç',
    'field_ends' => 'Bitiş',
    'field_voucher' => 'Kupon kodu',
    'field_redeem_url' => 'Kullanma bağlantısı',
    'field_terms_url' => 'Şartlar & koşullar bağlantısı',
    'field_cta' => 'Eylem çağrısı düğmesi',
    'field_cta_url' => 'Düğme bağlantısı',
    'field_schedule' => 'Sonrası için zamanla',
    'field_schedule_helper' => 'Hemen yayınlamak için boş bırakın. Saatler UTC\'dir.',

    'cta_none' => 'Düğme yok',
    'cta_book' => 'Rezervasyon yap',
    'cta_order' => 'Çevrimiçi sipariş ver',
    'cta_shop' => 'Alışveriş yap',
    'cta_learn_more' => 'Daha fazla bilgi',
    'cta_sign_up' => 'Kaydol',
    'cta_call' => 'Hemen ara',

    'no_locations' => 'En az bir konum seçin.',
    'unmatched' => 'Bu konumlar henüz bir Google kaydıyla eşleştirilemedi:',
    'publish_failed' => 'Yayınlama başarısız',
    'published_ok' => 'Gönderi yayınlandı',
    'scheduled_ok' => 'Gönderi zamanlandı',

    'delete' => 'Kaldır',
    'delete_desc' => 'Bu yalnızca girdiyi bu listeden kaldırır, gönderiyi Google\'dan silmez.',
    'deleted' => 'Girdi kaldırıldı',

    // Calendar view
    'view_calendar' => 'Takvim',
    'view_list' => 'Liste',
    'view_month' => 'Ay',
    'view_week' => 'Hafta',
    'today' => 'Bugün',
    'all_locations' => 'Tüm konumlar',
    'location_plus' => ':name +:count',
    'close' => 'Kapat',
    'location_count' => '{1} 1 konum|[2,*] :count konum',
    'add_post' => 'Gönderi',
    'add_note' => 'Not',

    // Drafts
    'save_draft' => 'Taslağı kaydet',

    // Imported Google posts
    'view' => 'Görüntüle',
    'duplicate_draft' => 'Taslak olarak çoğalt',
    'duplicated_draft' => 'Taslak oluşturuldu',
    'draft_heading' => 'Taslağı düzenle',
    'draft_saved' => 'Taslak kaydedildi',
    'draft_delete' => 'Taslağı sil',
    'draft_delete_desc' => 'Taslak kaldırılacak. Google\'a hiçbir şey yayınlanmadı.',
    'draft_deleted' => 'Taslak silindi',

    // Live preview
    'preview_label' => 'Önizleme',
    'preview_business' => 'İşletmeniz',
    'preview_now' => 'az önce',
    'preview_no_image' => 'Görsel yok',
    'preview_placeholder' => 'Gönderi metniniz burada görünecek.',

    // Sticky notes
    'note_placeholder' => 'Özel bir not yazın…',
    'note_color' => 'Not rengi',
    'note_tag' => '# etiket',
    'note_delete' => 'Notu sil',
    'note_delete_confirm' => 'Bu not silinsin mi?',
    'filter' => 'Filtrele',
    'notes_filter' => 'Notlar',
    'notes_filter_title' => 'Etikete göre notlar',
    'notes_filter_hint' => 'İşaretlenmemiş etiketler takvimden gizlenir.',
    'notes_filter_untagged' => 'Etiketsiz',

    'color_yellow' => 'Sarı',
    'color_orange' => 'Turuncu',
    'color_red' => 'Kırmızı',
    'color_pink' => 'Pembe',
    'color_purple' => 'Mor',
    'color_blue' => 'Mavi',
    'color_teal' => 'Turkuaz',
    'color_green' => 'Yeşil',
    'color_gray' => 'Gri',

    // External calendars
    'calendars_button' => '{0} Takvimler|{1} 1 takvim|[2,*] :count takvim',
    'calendars_connect' => 'Harici takvim',
    'calendars_title' => 'Harici takvimler',
    'calendars_empty' => 'Bu görünüme herkese açık takvimleri katmanlayın: tatiller, rezervasyonlar ya da diğer içerik planları.',
    'calendars_synced_ago' => ':ago senkronize edildi',
    'calendars_refresh' => 'Şimdi senkronize et',
    'calendars_synced' => 'Takvimler senkronize edildi',
    'calendars_sync_failed' => 'Bazı takvimler senkronize edilemedi',
    'calendar_add' => 'Harici takvim ekle',
    'calendar_add_submit' => 'Takvim ekle',
    'calendar_name' => 'Ad',
    'calendar_name_placeholder' => 'örn. Avusturya tatilleri',
    'calendar_url' => 'ICS bağlantısı',
    'calendar_url_helper' => 'Herkese açık bir iCal/ICS akış URL\'si. Google Takvim\'de: Ayarlar, ardından "Takvimi entegre et", ardından "iCal biçiminde herkese açık adres".',
    'calendar_color' => 'Renk',
    'calendar_added' => 'Takvim eklendi',
    'calendar_events_count' => '{0} Akışta etkinlik bulunamadı.|{1} 1 etkinlik içe aktarıldı.|[2,*] :count etkinlik içe aktarıldı.',
    'calendar_sync_error' => 'Takvim eklendi ama akış senkronize edilemedi',
    'calendar_delete' => 'Takvimi kaldır',
    'calendar_delete_confirm' => 'Bu takvim ve etkinlikleri görünümden kaldırılsın mı?',
];
