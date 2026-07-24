<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Henüz yapay zeka aracısı yok',
    'empty_desc' => 'Marka üslubunuzla yanıtlar taslaklaması ve otomatik yanıt otomasyonlarınıza güç vermesi için bir yapay zeka aracısı oluşturun.',
    'empty_cta' => 'Yeni yapay zeka aracısı',

    // Table
    'col_native_lang' => 'Ana dil',
    'col_default' => 'Varsayılan',
    'col_updated' => 'Güncellendi',
    'test_preview' => 'Test & önizleme',
    'test_heading' => 'Yanıtı test et',
    'close' => 'Kapat',
    'no_reviews_to_test' => 'Test edilecek yorum yok, önce birkaç yorum senkronize edin.',
    'generation_failed' => 'Oluşturma başarısız: :error',
    'set_default' => 'Varsayılan yap',

    // Form
    'section' => 'Yapay zeka aracınız',
    'section_desc' => 'Aracıya bir ad verin ve nasıl yanıt vermesi gerektiğini açıklayın. Otomatik yanıt otomasyonları ve "yapay zeka ile taslakla" düğmesi tarafından kullanılır.',
    'describe' => 'Aracınızı tanımlayın',
    'describe_helper' => 'Tam talimatlar / kişilik, yorumu nasıl sınıflandıracağı ve nasıl yanıt vereceği, üslup & stil, kişiselleştirme kuralları vb.',
    'tone' => 'Ses tonu',
    'reply_native' => 'Yorumun dilinde yanıtla',
    'reply_native_helper' => 'Aracı, yorumla aynı dilde yanıt verir.',
    'default_agent' => 'Varsayılan aracı',
    'default_agent_helper' => 'Bir otomasyon aracı belirtmediğinde kullanılır.',

    // Knowledge base
    'knowledge' => 'Bilgi tabanı (isteğe bağlı)',
    'knowledge_helper' => 'Aracının yanıtlarda kullanabileceği işletme bilgileri: çalışma saatleri, politikalar, oda/hizmet adları, teklifler, SSS\'ler. Bilgiye dayalı tutulur, bunun ötesinde asla uydurulmaz.',
    'knowledge_ph' => 'örn. Pzt–Paz 10:00–22:00 açık. Odalar: The Heist, Prison Break, Haunted Manor. 2–6 kişilik gruplar. example.com veya +43 ... üzerinden rezervasyon.',

    // Test panel
    'test_section' => 'Bir yorum üzerinde test et',
    'test_section_desc' => 'Gerçek bir yorum seçin ve mevcut (kaydedilmemiş) ayarlarla bir taslak oluşturun, ardından ince ayar yapın.',
    'test_pick_review' => 'Yorum',
    'test_pick_placeholder' => 'Senkronize edilmiş bir yorum seçin…',
    'test_review_text' => 'Yorum',
    'test_generate' => 'Taslak oluştur',
    'test_result' => 'Oluşturulan taslak',
    'test_need_review' => 'Önce test edilecek bir yorum seçin.',

    // AI description generator
    'generate_label' => 'Yapay zeka ile oluştur',
    'generate_heading' => 'Açıklamayı yapay zeka ile oluştur',
    'generate_desc' => 'Web sitenizi ve/veya işletme hakkında birkaç kelime ekleyin, yapay zeka sizin için aracı talimatlarını taslaklasın. Sonucu daha sonra düzenleyebilirsiniz.',
    'generate_submit' => 'Oluştur',
    'generate_url' => 'Web sitesi URL\'si',
    'generate_notes' => 'Eklenecek bir şey (isteğe bağlı)',
    'generate_notes_ph' => 'örn. aile işletmesi İtalyan restoranı, güler yüzlü hizmete odaklı, yazın terasımızdan söz et',
    'generate_need_input' => 'Önce bir web sitesi URL\'si ya da kısa bir açıklama ekleyin.',
    'generate_rate_limited' => 'Çok fazla oluşturma. Lütfen biraz bekleyip tekrar deneyin.',
    'generate_done' => 'Açıklama oluşturuldu, gerektiği gibi inceleyip ince ayar yapın.',
    'generate_failed' => 'Açıklama oluşturulamadı. Lütfen tekrar deneyin ya da manuel yazın.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Ortak kurallar',
    'shared_rules_heading' => 'Ortak yanıt kuralları',
    'shared_rules_desc' => 'Bu kurallar her aracının üzerine, her yapay zeka yanıtında uygulanır. Her aracı için asla tekrarlamak istemediğiniz stil düzeltmeleri için idealdir.',
    'shared_rules_placeholder' => "örn.\nAlmanca yanıtlarda \"Room\" yerine \"Raum\" ya da \"Escape Room\" de, Almanca isim olarak asla \"Room\" deme.\nAsla indirim ya da iade vaat etme.\nYanıtları isim olmadan imzala.",
    'shared_rules_save' => 'Kuralları kaydet',
    'shared_rules_saved' => 'Ortak kurallar kaydedildi',
];
