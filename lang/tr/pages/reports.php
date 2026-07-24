<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Demo Restoran',
    'demo_period' => 'Performans raporu · son 30 gün',
    'demo_five_star' => '5 yıldız oranı',
    'demo_summary_label' => 'Yönetici özeti',
    'demo_summary' => 'Demo Restoran son 30 günde 38 yorum aldı (önceki döneme göre +9), ortalama 4,60★. Yorumların %84\'ü olumluydu ve yanıt oranı %92\'ye ulaştı. Misafirler tekrar tekrar güler yüzlü ekibi ve hızlı hizmeti övdü.',

    'location' => 'Konum',
    'business_multi' => ':name + :count tane daha',
    'compare' => 'Karşılaştır',
    'compare_options' => [
        'none' => 'Karşılaştırma',
        'previous' => 'Önceki dönem',
        'custom' => 'Özel aralık…',
    ],
    'compare_from' => 'Karşılaştırma başlangıcı',
    'compare_to' => 'Karşılaştırma bitişi',
    'report_language' => 'Rapor dili',

    'content_section' => 'Rapor içeriği',
    'content_section_desc' => 'Bir hazır ayar seçin, ardından raporda hangi blokların görüneceğine ince ayar yapın.',
    'preset' => 'Hazır ayar',
    'blocks' => 'Bloklar',
    'competitors_block_hint' => 'Henüz takip edilen rakip yok. Önce İşletme kayıtları > Rakipler altından ekleyin.',
    'ai_instructions' => 'Yapay zeka talimatları',
    'ai_instructions_help' => 'Yapay zeka anlatısı için isteğe bağlı yönlendirme. En çok personel adları için faydalıdır: ekibinizi ve her türlü takma adı listeleyin, böylece anmalar doğru kişiyle eşleştirilir. Bir kez kaydedilir ve zamanlanmışlar dahil gelecekteki her rapora uygulanır.',
    'ai_instructions_placeholder' => 'Personelimiz: Eva, Alette, Suleyman (Suly olarak da yazılır), Lisa. Takma adları tam adla birleştir.',
    'ai_improve' => 'Yapay zeka ile iyileştir',
    'ai_improve_empty' => 'Önce birkaç not yazın, sonra iyileştirin.',
    'ai_improve_rate_limited' => 'Çok fazla deneme, daha sonra tekrar deneyin.',
    'ai_improve_done' => 'Talimatlar iyileştirildi',
    'ai_improve_failed' => 'Talimatlar iyileştirilemedi, lütfen tekrar deneyin.',

    'schedule_report' => 'Zamanlanmış gönder',
    'schedule_heading' => 'Bu raporu zamanla',
    'schedule_desc' => 'Mevcut seçim (dönem, konum, karşılaştırma, bloklar) düzenli bir zamanlamayla PDF olarak e-postayla gönderilecek.',
    'schedule_submit' => 'Zamanlama oluştur',
    'schedule_created' => 'Zamanlama oluşturuldu',
    'schedule_created_body' => 'Raporlar → Zamanlanmış raporlar altından yönetin.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'bu ay :cap yapay zeka raporundan :left tanesi kaldı',

    // Generate modal
    'generate_heading' => 'Yapay zeka raporu oluşturulsun mu?',
    'generate_desc' => 'Mevcut seçim için yapay zeka yönetici özetini oluşturun.',
    'generate_desc_left' => 'Bu, aylık yapay zeka raporlarınızdan 1 tanesini kullanır, :left tane kaldı.',
    'generate_submit' => 'Oluştur',

    // Generate notifications
    'report_generated' => 'Rapor oluşturuldu',
    'report_generated_body' => 'Yapay zeka özeti hazır, önizleme güncellendi. PDF\'yi kaydetmek için İndir\'i kullanın.',
    'limit_reached' => 'Aylık rapor sınırına ulaşıldı',
    'limit_reached_body' => 'Yapay zeka olmadan temel bir rapor gösteriliyor. Daha yüksek bir aylık sınır için yükseltin.',

    // Blade view
    'generate_report' => 'Rapor oluştur',
    'generating' => 'Oluşturuluyor…',
    'download_pdf' => 'PDF indir',
    'download_first_tooltip' => 'Önce raporu oluşturun',
    'building' => 'Rapor hazırlanıyor…',
    'preview_title' => 'Rapor önizlemesi',
];
