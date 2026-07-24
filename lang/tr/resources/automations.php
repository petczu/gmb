<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Henüz otomasyon yok',
    'empty_desc' => 'Yeni yorumlara puana ve konuma göre otomatik olarak yanıt vermek için bir otomasyon kurun.',
    'empty_cta' => 'Yeni otomasyon',

    // Table columns
    'col_name' => 'Ad',
    'col_enabled' => 'Etkin',
    'name' => 'Ad',
    'enabled' => 'Etkin',
    'col_rating' => 'Puan',
    'rating_any' => 'herhangi',
    'col_reply' => 'Yanıt',
    'reply_ai' => 'Yapay zeka: :agent',
    'reply_default' => 'Varsayılan mesaj',
    'col_mode' => 'Mod',
    'mode_approval' => 'Onay',
    'mode_auto' => 'Otomatik yayınla',
    'col_scope' => 'Kapsam',
    'scope_all' => 'Tüm konumlar',
    'scope_count' => ':count konum',

    // Run action
    'run_now' => 'Şimdi çalıştır',
    'run_heading' => 'Bu otomasyonu şimdi çalıştır',
    'run_desc' => 'Bu otomasyonu eşleşen yanıtsız yorumlara uygulayın. İsteğe bağlı olarak bir yorum tarihi dönemiyle sınırlayın; tümünü dahil etmek için her iki alanı da boş bırakın.',
    'run_from' => 'Şu tarihten yorumlar',
    'run_until' => 'Şu tarihe kadar yorumlar',
    'run_title' => '":name" çalıştırıldı',
    'run_queued_title' => '":name" kuyruğa alındı',
    'run_queued_body' => 'Çalıştırma arka planda gerçekleşir. Yeni taslaklar Onaylar\'a düşer ve otomatik yayınlanan yanıtlar önümüzdeki dakikalarda yorumlarda görünür.',
    'run_body' => ':generated oluşturuldu, :published yayınlandı, :queued kuyruğa alındı, :skipped atlandı.',

    // Form — Flow section
    'flow_section' => 'Akış',
    'flow_section_desc' => 'Otomasyonun ne zaman çalıştığı ve hangi yorumlara uygulandığı.',
    'trigger' => 'Tetikleyici',
    'trigger_new_review' => 'Google\'da yeni yorum',
    'rating_is' => 'Puan şu ise…',
    'rating_helper' => 'Herhangi bir puana uygulamak için tümünü işaretsiz bırakın.',
    'all_locations' => 'Tüm konumlar',
    'locations' => 'Konumlar',
    'all_locations_helper' => 'Bir toplayıcı görevi görür: belirli konumlarla sınırlı otomasyonlar, kendi konumları için öncelik alır.',
    'covered_by' => 'zaten ":name" içinde (:ratings)',
    'any_rating' => 'herhangi bir puan',
    'overlap_title' => 'Başka bir otomasyonla çakışıyor',
    'overlap_body' => 'Aynı yorumlarla da eşleşir: :list. Her yorum tam olarak bir otomasyon tarafından ele alınır: belirli konumlar "Tüm konumlar" karşısında kazanır, aksi halde eski olan çalışır.',
    'respect_working_hours' => 'Çalışma saatlerine uy',
    'respect_working_hours_helper' => 'Yalnızca konumun açık olduğu saatlerde yanıt ver.',
    'reply_to_previous' => 'Önceki yorumlara yanıt ver',
    'reply_to_previous_helper' => 'Mevcut yanıtsız yorumları da ele al (aylık yapay zeka kotanızdan düşer).',
    'approve_before_posting' => 'Göndermeden önce onayla',
    'approve_before_posting_helper' => 'Kapalı = Google\'a otomatik yayınla. Açık = önce Onaylar\'a gönder.',

    // Form — Timing section
    'timing_section' => 'Zamanlama',
    'timing_section_desc' => 'Rastgele bir gecikme (ve isteğe bağlı çalışma saatleri) ekleyin, böylece yanıtlar anında değil, insan hızında, organik zamanlarda gönderilir.',
    'reply_delay_min' => 'Minimum gecikme',
    'reply_delay_max' => 'Maksimum gecikme',
    'minutes_suffix' => 'dk',
    'reply_delay_helper' => 'Yanıtlar, minimum ve maksimum arasında rastgele bir gecikmeden sonra gönderilir, böylece organik görünürler. Hemen göndermek için her ikisini de 0 yapın.',
    'reply_delay_max_error' => 'Maksimum gecikme, minimum gecikmeye eşit ya da ondan büyük olmalıdır.',
    'working_days' => 'Çalışma günleri',
    'working_start' => 'Başlangıç saati',
    'working_end' => 'Bitiş saati',
    'day_mon' => 'Pzt',
    'day_tue' => 'Sal',
    'day_wed' => 'Çar',
    'day_thu' => 'Per',
    'day_fri' => 'Cum',
    'day_sat' => 'Cmt',
    'day_sun' => 'Paz',

    // Form — Content section
    'content_section' => 'İçerik',
    'content_section_desc' => 'Hangi yanıtın gönderileceği.',
    'content_ai_agent' => 'Yapay zeka aracısı',
    'content_default_message' => 'Varsayılan mesaj',
    'ai_agent' => 'Yapay zeka aracısı',
    'default_message' => 'Varsayılan mesaj',
];
