<?php

declare(strict_types=1);

return [
    'nav' => 'İşletme bilgileri',
    'title' => 'İşletme bilgileri',

    'not_configured_title' => 'İşletme kaydı yönetimi yapılandırılmadı',
    'not_configured_body' => 'Google İşletme profillerini düzenlemek için sunucu ortamında ZERNIO_API_KEY değerini ayarlayın.',

    'pick_location' => 'Konum',
    'status_live' => 'Google\'da yayında',
    'status_suspended' => 'Google tarafından askıya alındı',
    'status_disabled' => 'Devre dışı',
    'status_unverified' => 'Doğrulanmadı',

    'section_basics' => 'Profil',
    'field_logo' => 'Konum logosu',
    'field_logo_helper' => 'Google gönderi önizlemesinde gösterilir. Boş olduğunda çalışma alanı logosuna döner.',
    'field_description' => 'İşletme açıklaması',
    'field_description_helper' => 'Google profilinizde gösterilir. En fazla 750 karakter. Form, mevcut canlı değerleri Google\'dan yükler.',
    'field_phone' => 'Telefon numarası',
    'field_website' => 'Web sitesi',

    'section_hours' => 'Çalışma saatleri',
    'section_hours_desc' => 'Her zaman aralığı için bir satır. Bölünmüş saatler için (örn. öğle molası) aynı güne iki satır ekleyin.',
    'add_hours' => 'Zaman aralığı ekle',
    'field_day' => 'Gün',
    'field_open' => 'Açılış',
    'field_close' => 'Kapanış',

    'day_monday' => 'Pazartesi',
    'day_tuesday' => 'Salı',
    'day_wednesday' => 'Çarşamba',
    'day_thursday' => 'Perşembe',
    'day_friday' => 'Cuma',
    'day_saturday' => 'Cumartesi',
    'day_sunday' => 'Pazar',

    'section_special' => 'Özel saatler',
    'section_special_desc' => 'Tatiller ve istisnalar: bunlar verilen tarihlerde normal saatleri geçersiz kılar.',

    'section_socials' => 'Sosyal profiller',
    'section_socials_desc' => 'Google kaydınızda gösterilen sosyal medya profillerinize bağlantılar. Yalnızca doldurulmuş alanlar yayınlanır; Google\'daki mevcut değeri korumak için bir alanı boş bırakın.',
    'add_special' => 'Özel saat ekle',
    'field_start_date' => 'Başlangıç',
    'field_end_date' => 'Bitiş',
    'field_closed' => 'Bu günlerde kapalı',

    'save' => 'Google\'a yayınla',
    'saved' => 'Profil güncellemesi Google\'a gönderildi',
    'save_failed' => 'Güncelleme başarısız',
    'unmatched' => 'Bu konum henüz bir Google kaydıyla eşleştirilemedi.',

    'field_additional_phones' => 'Ek telefon numaraları',
    'field_additional_phones_placeholder' => 'numara ekle + Enter',
    'field_additional_phones_help' => 'Profilde gösterilen en fazla iki ek numara.',
    'field_timezone' => 'Saat dilimi',
    'field_timezone_helper' => 'Otomatik yanıt çalışma saatleri bu saat diliminde yorumlanır. Bağlanırken otomatik algılanır; yanlışsa buradan geçersiz kılın.',
    'loading_live' => 'Mevcut profil verileri Google\'dan yükleniyor…',
];
