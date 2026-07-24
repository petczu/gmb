<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Şirket bilgilerinizi ekleyin',
    'step_company_hint' => 'Faturalarda ve raporlarda kullanılan ülke ve fatura bilgileri.',
    'step_plan_label' => 'Bir plan seçin',
    'step_plan_hint' => '14 günlük ücretsiz denemenizi başlatın, kart gerekmez.',
    'step_location_label' => 'İlk konumunuzu bağlayın',
    'step_location_hint' => 'Yorumları çekmeye başlamak için bir Google İşletme Profili bağlayın.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Çalışma alanınızı kurun',
    'wiz_plan_done' => '✓ Planınız etkin. Sonraki adıma geçin.',
    'wiz_plan_pick' => 'Bir plan seçin',
    'wiz_interval' => 'Faturalandırma aralığı',
    'wiz_monthly' => 'Aylık',
    'wiz_yearly' => 'Yıllık',
    'wiz_start_trial' => '14 günlük ücretsiz denemeyi başlat',
    'wiz_trial_note' => '14 günlük ücretsiz denemeniz devam ettiğiniz anda başlar. Kart gerekmez.',
    'wiz_go_checkout' => 'Ödemeye devam et',
    'wiz_plan_required' => 'Devam etmek için bir plan seçin ve ödemeyi tamamlayın.',
    'wiz_location_body' => 'Yorumlarınızı çekebilmemiz için Google İşletme Profilinizi bağlayın. Erişimi yetkilendirmek üzere Google\'a yönlendirileceksiniz, ardından bağlanacak konumu seçeceksiniz.',
    'wiz_connect_google' => 'Google İşletme Profilini bağla',
    'wiz_skip_location' => 'Şimdilik atla',
    'skipped_title' => 'Her şey hazır',
    'skipped_body' => 'Google İşletme Profilinizi istediğiniz zaman Konumlar sayfasından bağlayabilirsiniz.',
    'wiz_per_location' => 'konum başına / ay',
    'wiz_plan_desc_starter' => 'Yorum gelen kutusu, manuel yanıtlar ve temel raporlar.',
    'wiz_plan_desc_growth' => 'Yapay zeka otomatik yanıtları, zamanlanmış raporlar ve karşılaştırmalar ekler.',
    'wiz_plan_desc_pro' => 'Her şey, ayrıca beyaz etiket, API, MCP ve müşteri erişimi.',

    // Onboarding overlay
    'welcome_title' => 'Hoş geldiniz, hesabınızı kuralım',
    'welcome_subtitle' => 'Birkaç hızlı adım ve hazırsınız.',
    'continue_step' => 'Devam: :label',
    'enter_app' => 'Uygulamaya gir →',
    'sign_out' => 'Çıkış yap',

    // Pending-deletion overlay
    'deletion_title' => 'Bu çalışma alanı silinmek üzere zamanlandı',
    'deletion_body' => 'Tüm veriler <strong>:date</strong> tarihinde kalıcı olarak silinecek. Yine de iptal edip çalışma alanınızı koruyabilirsiniz.',
    'cancel_deletion' => 'Silmeyi iptal et',

    // Grace banner
    'grace_banner' => '⚠️ Son ödemenizi işleyemedik. Hizmetiniz <strong>:date</strong> tarihine kadar etkin kalır, lütfen',
    'update_your_billing' => 'fatura bilgilerinizi güncelleyin',

    // Paywall overlay
    'payment_problem_title' => 'Ödemenizde bir sorun var',
    'needs_plan_title' => 'Başlamak için bir plan seçin',
    'payment_problem_body' => 'Ödemeyi işleyemediğimiz için erişiminiz duraklatıldı. Devam etmek için fatura bilgilerinizi güncelleyin.',
    'needs_plan_body' => 'Konumlarınız için yorumları, yapay zeka yanıtlarını ve raporları etkinleştirmek üzere bir plan seçin. 14 günlük ücretsiz deneme.',
    'update_billing' => 'Fatura bilgilerini güncelle',
    'view_plans' => 'Planları görüntüle',

    // Connect-select-location page
    'connecting_location' => 'Konum bağlanıyor…',
    'choose_location' => 'Bu çalışma alanına bağlanacak Google İşletme konumunu seçin.',
    'could_not_load' => 'Konumlar yüklenemedi',
    'pending_expired_title' => 'Google oturumunun süresi doldu',
    'pending_expired' => 'Google yetkilendirmesi yalnızca kısa bir süre geçerlidir ve bunun süresi dolmuş. Yeniden bağlanıp konumlarınızı tekrar seçin, yalnızca bir dakika sürer.',
    'reconnect_google' => 'Google\'ı yeniden bağla',
    'back' => 'Geri',
    'no_locations_available' => 'Kullanılabilir konum yok',
    'no_locations_body' => 'Hiçbir Google İşletme konumu döndürülmedi. Google tarafında hâlâ yükleniyor olabilir, kısa süre sonra tekrar deneyin.',
    'connect_then_done' => 'Bir veya daha fazla konum bağlayın, ardından Bitti\'ye tıklayın.',
    'done' => 'Bitti',
    'connected' => 'Bağlandı',
    'connect' => 'Bağla',
    'connecting' => 'Bağlanıyor…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'İşletme konumu seçin',
    'connect_failed' => 'Konum bağlanamadı',
    'connected_title' => 'Bağlandı: :name',
    'connected_body' => 'Yorumlar arka planda senkronize ediliyor, kısa süre sonra Konumlar sayfasında görünecekler.',
    'location_fallback' => 'konum',
    'trial_started_title' => '14 günlük denemeniz başladı',
    'trial_started_body' => ':date tarihine kadar tam erişim, kart gerekmez. İyi eğlenceler!',
];
