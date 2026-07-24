<?php

declare(strict_types=1);

return [
    'nav' => 'Rakipler',
    'title' => 'Rakipler',
    'intro' => 'Yakınınızdaki işletmeleri takip edin ve Google puanlarını ve yorum sayılarını konumlarınızla karşılaştırın. Sayılar her gün otomatik olarak yenilenir.',

    'empty' => 'Henüz rakip yok.',
    'empty_desc' => 'Google puanını ve yorum artışını takip etmek için bir rakip ekleyin.',

    'not_configured_title' => 'Rakip takibi yapılandırılmadı',
    'not_configured_body' => 'Rakip kıyaslamasını etkinleştirmek için sunucu ortamında GOOGLE_PLACES_API_KEY değerini (bir Google Places API anahtarı) ayarlayın.',

    'col_battle' => 'Rakip',
    'col_name' => 'Rakip',
    'col_rating' => 'Puan',
    'col_reviews' => 'Yorumlar',
    'filter_location' => 'Konum',
    'filter_city' => 'Şehir',
    'col_vs' => 'Size karşı',
    'col_location' => 'Sizin tarafınız',
    'col_checked' => 'Güncellendi',

    'untitled_battle' => 'Başlıksız karşılaşma',
    'default_battle_name' => '{1} :location vs 1 rakip|[2,*] :location vs :count rakip',
    'own_locations_count' => ':count konum',
    'rating_weighted_hint' => 'Puan, rakipler genelinde yorum sayılarına göre ağırlıklandırılarak ortalandı.',

    'vs_ahead' => ':delta ★ önünüzdesiniz',
    'vs_behind' => ':delta ★ öndeler',
    'vs_tied' => 'Berabere',
    'vs_unknown' => '—',

    'add' => 'Rakip ekle',
    'add_heading' => 'Rakip ekle',
    'edit' => 'Düzenle',
    'edit_heading' => 'Rakipleri düzenle',
    'field_name' => 'Karşılaşma adı',
    'field_name_placeholder' => 'örn. Ana Cadde vs mahalle',
    'field_your_locations' => 'Konumlarınız',
    'field_your_locations_helper' => 'Kendi tarafınız için konumlarınızdan bir veya daha fazlasını seçin.',
    'field_place' => 'Rakip',
    'field_places' => 'Rakipler',
    'field_places_helper' => 'Google Places\'te aramak için bir işletme adı (ve şehir) yazın.',
    'already_tracked' => 'Bu rakibi zaten takip ediyorsunuz.',
    'saved' => 'Rakip kaydedildi',
    'some_failed' => ':count rakip getirilemedi ve atlandı.',

    'duplicate' => 'Çoğalt',
    'duplicate_heading' => 'Rakibi çoğalt',
    'copy_name' => ':name (kopya)',
    'remove' => 'Kaldır',
    'removed' => 'Rakip kaldırıldı',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Grup oluştur',
    'group_heading' => 'Rakipleri grupla',
    'group_need_two' => 'Gruplamak için en az iki rakip seçin.',
    'group_created' => 'Grup oluşturuldu',
    'group_removed' => 'Grup kaldırıldı',
    'ungroup' => 'Gruptan çıkar',
    'ungrouped' => 'Gruptan çıkarıldı',
    'field_group_name' => 'Grup adı',
    'field_group_competitors' => 'Rakipler',
    'field_group_competitors_helper' => 'Bu rakipler, yorumları toplanarak artış grafiğinde tek bir çizgide birleşir.',
    'col_group' => 'Grup',

    'col_new_reviews' => 'Yeni yorumlar',
    'col_rating_trend' => 'Puan değişimi',
    'col_trend' => 'Trend',
    'you_delta' => 'siz: :delta',
    'trend_hint' => 'Seçili dönemdeki yeni yorumlar.',
    'trend_collecting' => 'toplanıyor…',
    'period_4w' => '4 hafta',
    'period_12w' => '3 ay',

    'collecting' => 'toplanıyor…',
    'prev_delta' => 'önceki: :delta',
    'period_7d' => '7 gün',
    'period_6m' => '6 ay',
    'no_change' => 'değişiklik yok',
    'search_failed' => 'Rakip araması geçici olarak kullanılamıyor',

    // Competitor detail modal
    'view' => 'Ayrıntıları gör',
    'close' => 'Kapat',
    'you' => 'Siz',
    'reviews_count' => '{1} 1 yorum|[2,*] :count yorum',
    'no_distribution' => 'Yıldız dağılımı henüz mevcut değil (sonraki yenilemede güncellenir).',
];
