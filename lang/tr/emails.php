<?php

declare(strict_types=1);

return [
    'greeting' => 'Merhaba :name,',
    'signoff' => 'Teşekkürler,',
    'team' => 'Repunio ekibi',

    'drip_competitors' => [
        'subject' => 'Yan taraftaki işletmenin nasıl gittiğini biliyor musunuz?',
        'intro' => 'Kendi yorumlarınız kontrol altında. Her işletme sahibinin sonraki sorusu şu: rekabetin önünde miyim yoksa geride mi kalıyorum? Repunio bunu sizin için izleyebilir, Google\'daki her işletme için günlük puan ve yorum sayılarıyla.',
        'tip' => 'İki dakika sürer: Rakipler\'i açın, adı arayın, ekleyin. O andan itibaren kimin öne geçtiğini, ne kadar farkla ve puanınızın ayak uydurup uydurmadığını göreceksiniz.',
        'cta' => 'İlk rakibinizi ekleyin',
    ],

    'location_connected' => [
        'subject' => ':location bağlandı',
        'intro' => ':location konumunuz artık bağlı. Yorumlarını şu anda Google\'dan içe aktarıyoruz; sayısına bağlı olarak bu birkaç dakika sürebilir.',
        'note' => 'Yorumlar geldiği anda size bir e-posta daha göndereceğiz.',
        'cta' => 'Konumları görüntüle',
    ],

    'location_synced' => [
        'subject' => 'Yorumlarınız geldi',
        'intro' => 'İlk içe aktarma tamamlandı. Gelenler şöyle:',
        'note' => 'Bundan sonra yeni yorumlar otomatik olarak gelir ve otomasyon kurallarınız bunlara uygulanır.',
        'cta' => 'Yorum gelen kutusunu aç',
    ],

    'drip_connect' => [
        'subject' => 'Hesabınız hazır. Bir adım kaldı',
        'intro' => 'Repunio çalışma alanınız kuruldu ama hâlâ boş: yorumlar, puanlar ve raporların hepsi Google İşletme Profilinizden gelir ve henüz hiçbiri bağlı değil.',
        'tip' => 'Yaklaşık iki dakika sürer: Konumlar\'ı açın, Bağla\'ya tıklayın, Google ile giriş yapın ve işletmenizi seçin. Yorumlarınız hemen akmaya başlar.',
        'cta' => 'Konumunuzu bağlayın',
    ],

    'signup_code' => [
        'subject' => ':code Repunio kayıt kodunuz',
        'intro' => 'E-posta adresinizi onaylamak için bu kodu kayıt sayfasına girin:',
        'note' => 'Kod :minutes dakika geçerlidir. Siz istemediyseniz bu e-postayı güvenle yok sayabilirsiniz.',
    ],

    'beta_received' => [
        'subject' => 'Teşekkürler! Erişim talebiniz alındı',
        'intro' => 'Kaydolduğunuz için teşekkürler! Repunio şu anda özel beta aşamasında ve yeni hesapları küçük gruplar halinde etkinleştiriyoruz.',
        'note' => 'Erişiminiz hazır olur olmaz size e-posta göndereceğiz. Şu anda yapmanız gereken başka bir şey yok.',
    ],

    'beta_approved' => [
        'subject' => 'Repunio erişiminiz hazır',
        'intro' => 'İyi haber: hesabınız etkinleştirildi. Artık giriş yapıp her şeyi kurabilirsiniz.',
        'note' => 'Google İşletme Profilinizi bağlayarak başlayın, yorumlarınız birkaç dakika içinde içe aktarılır.',
        'cta' => 'Repunio\'yu aç',
    ],

    'welcome' => [
        'subject' => 'Repunio\'ya hoş geldiniz',
        'intro' => 'Hesabınız hazır. Repunio, Google yorumlarınızı toplamanıza, yanıtlamanıza ve raporlamanıza yardımcı olur, hepsi tek yerde.',
        'next' => 'Sıradaki: ilk konumunuzu bağlayın ve 14 günlük ücretsiz denemenizi başlatmak için bir plan seçin.',
        'cta' => 'Repunio\'yu aç',
    ],

    'trial_ending' => [
        'subject' => 'Ücretsiz denemeniz :days gün içinde bitiyor',
        'intro' => 'Repunio ücretsiz denemeniz :date tarihinde bitiyor. Hiçbir şey durmasın diye şimdi bir ödeme yöntemi ekleyin, yorumlarınız senkronize olmaya, yapay zeka yanıtları çalışmaya devam etsin.',
        'note' => 'Deneme bitene kadar sizden ücret alınmaz ve istediğiniz zaman iptal edebilirsiniz.',
        'cta' => 'Ödeme yöntemi ekle',
    ],

    'payment_succeeded' => [
        'subject' => 'Ödeme alındı',
        'intro' => ':amount tutarındaki ödemenizi aldık. Repunio aboneliğiniz etkin.',
        'cta' => 'Faturalandırmayı görüntüle',
    ],

    'payment_failed' => [
        'subject' => 'Ödeme başarısız, işlem gerekiyor',
        'intro' => 'Son ödemenizi işleyemedik. Hesabınız :days gün daha çalışmaya devam eder, kesinti yaşamamak için lütfen fatura bilgilerinizi güncelleyin.',
        'cta' => 'Fatura bilgilerini güncelle',
    ],

    'subscription_canceled' => [
        'subject' => 'Aboneliğiniz iptal olacak şekilde ayarlandı',
        'intro' => 'Repunio aboneliğiniz iptal edildi. :date tarihine kadar tam erişiminiz devam eder, ardından yenilenmez.',
        'note' => 'Fikrinizi mi değiştirdiniz? O tarihten önce istediğiniz zaman, ücretsiz olarak devam ettirebilirsiniz.',
        'cta' => 'Aboneliği sürdür',
    ],

    'subscription_resumed' => [
        'subject' => 'Aboneliğiniz tekrar etkin',
        'intro' => 'Repunio aboneliğiniz sürdürüldü ve normal şekilde yenilenmeye devam edecek. Yapmanız gereken başka bir şey yok.',
        'cta' => 'Faturalandırmayı görüntüle',
    ],

    'ai_limit' => [
        'subject' => 'Bu ayki tüm yapay zeka yanıtlarınızı kullandınız',
        'intro' => ':plan planındaki aylık yapay zeka yanıt sınırınıza ulaştınız. Daha yüksek bir sınır için yükseltin ya da gelecek aya kadar manuel yanıt vermeye devam edin.',
        'cta' => 'Planları gör',
    ],

    'auto_recharge_failed' => [
        'subject' => 'Yapay zeka yükleme ödemesi başarısız',
        'intro' => 'Yapay zeka yanıtlarınızı otomatik olarak yüklemeye çalıştık ama ödeme gerçekleşmedi. Otomatik yüklemenin çalışmaya devam etmesi için lütfen kartınızı güncelleyin.',
        'cta' => 'Fatura bilgilerini güncelle',
    ],

    'new_reviews' => [
        'subject' => 'İşletmeniz için :count yeni yorum',
        'intro' => ':location için :count yeni yorumunuz var.',
        'col_author' => 'Yazar',
        'col_rating' => 'Puan',
        'col_location' => 'Konum',
        'col_review' => 'Yorum',
        'cta' => 'Yorumları görüntüle',
    ],

    'account_disconnected' => [
        'subject' => 'İşlem gerekiyor: Google bağlantınız çalışmayı durdurdu',
        'intro' => '":account" için Google bağlantısı çalışmayı durdurdu, bu yüzden yorumlarınız artık senkronize edilmiyor.',
        'detail' => 'Yorumları senkronize etmeye ve yanıt göndermeye devam etmek için hesabı yeniden bağlayın.',
        'cta' => 'Yeniden bağla',
    ],

    'sync_restored' => [
        'subject' => 'Google bağlantınız geri geldi',
        'intro' => 'İyi haber: ":account" için bağlantı geri geldi ve senkronizasyon yeniden başladı. Yorumlarınız yeniden güncel.',
        'cta' => 'Repunio\'yu aç',
    ],

    'negative_review' => [
        'subject' => ':rating★ yorum ilginizi bekliyor',
        'intro' => ':business için yeni bir yorum ilginizi bekliyor.',
        'col_author' => 'Yazar',
        'col_rating' => 'Puan',
        'col_review' => 'Yorum',
        'cta' => 'Şimdi yanıtla',
    ],

    'reply_failed' => [
        'subject' => 'Yanıtınızı gönderemedik',
        'intro' => ':business için bir yoruma yanıt göndermeye çalıştık ama başarısız oldu.',
        'col_author' => 'Yazar',
        'col_review' => 'Yorum',
        'detail' => 'Lütfen yanıtı uygulamadan tekrar göndermeyi deneyin.',
        'detail_retry' => 'Bu geçici görünüyor, bu yüzden önümüzdeki birkaç saat içinde otomatik olarak tekrar göndermeyi deneyeceğiz. İşlem gerekmez. Başarısız olmaya devam ederse Yorumlar → Başarısız altında bulacaksınız.',
        'detail_not_found' => 'Google, bu yorumun artık var olmadığını söylüyor. Yazarı tarafından silinmiş ya da Google tarafından filtrelenmiş olabilir. Yapılacak bir şey yok: taslak bekletildi ve yeniden denenmeyecek.',
        'detail_unauthorized' => 'Google bağlantısı bu konum için yanıt vermeye yetkili değil, bu yüzden tekrar denemeyeceğiz. Hesabı yeniden bağlayın, ardından yanıtı uygulamadan tekrar gönderin.',
        'cta' => 'Onayları aç',
    ],

    'post_failed' => [
        'subject' => 'Google gönderinizi yayınlayamadık',
        'intro' => ':business için bir Google gönderisi yayınlamaya çalıştık ama başarısız oldu. Gönderi, hatasıyla birlikte takviminizde.',
        'detail' => 'Lütfen gönderiyi uygulamadan tekrar yayınlamayı deneyin.',
        'detail_reason' => 'Neden: :reason',
        'cta' => 'Gönderileri aç',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies onay bekliyor',
        'intro' => 'Onayınızı bekleyen :count :replies var. Gönderilmeleri için inceleyip onaylayın.',
        'reply_word' => '{1}yanıt|[2,*]yanıt',
        'reply_label' => 'Önerilen yanıt',
        'cta' => 'Onayları incele',
    ],

    'review_goal' => [
        'subject_mid' => 'Yorum hedefiniz: ay nasıl gidiyor',
        'subject_recap' => ':month için yorum özeti',
        'intro_mid_ahead' => 'Harika tempo! Bu ay :actual yeni yorumunuz var, şu ana kadar beklenen :expected sayısının önünde (hedef :goal). Böyle devam.',
        'intro_mid_on_track' => 'Yolundasınız: bu ay :actual yeni yorum, şu ana kadar beklenen :expected sayısının hemen çevresinde (hedef :goal).',
        'intro_mid_behind' => 'Küçük bir dürtme: bu ay :actual yeni yorumunuz var, şu ana kadar beklenen :expected sayısının altında (hedef :goal). Ufak bir gayret işe yarar.',
        'intro_recap' => ':month şöyle sona erdi: :goal hedefine karşılık :actual yeni yorum.',
        'col_location' => 'Konum',
        'col_goal' => 'Hedef',
        'col_so_far' => 'Şu ana kadar',
        'col_projected' => 'Öngörülen',
        'col_pace' => 'Tempo',
        'col_got' => 'Gelen',
        'col_vs_goal' => 'hedefe göre',
        'col_vs_prev' => 'geçen aya göre',
        'status_ahead' => 'Önde',
        'status_on_track' => 'Yolunda',
        'status_behind' => 'Geride',
        'cta' => 'Yorumları görüntüle',
    ],

    'coaching' => [
        'subject' => 'Yorum hedefiniz: devam ettirelim',
        'intro_almost' => 'Çok az kaldı! Bu ay :goal hedefinize ulaşmak için yalnızca :remaining tane daha. Başarabilirsiniz!',
        'intro_behind' => 'Bu ay :goal hedefinin :actual tanesindesiniz. Bu hafta istikrarlı bir gayret sizi yeniden tempoya sokar. İşte birkaç fikir.',
        'intro_on_track' => 'Güzel iş! :goal hedefinin :actual tanesi ve tam tempoda. Bu hafta birkaç istek momentumu koruyacak.',
        'intro_ahead' => 'Harika momentum! :goal hedefinin :actual tanesi, planın önünde. Bu fikirlerle böyle devam edin.',
        'steady' => 'Bir şey: istekleri günlere yayın. Yorumların aniden akın etmesi Google\'a şüpheli görünür ve filtrelenebilir. İstikrar kazandırır.',
        'cta' => 'Yorumları aç',
    ],

    'goal_reached' => [
        'subject' => 'Hedef ezildi! Bu ay :goal yorum! 🎉',
        'intro' => 'Tebrikler! Bu ay :goal yeni yorum hedefinize ulaştınız! Bu, itibarınız için gerçek bir momentum.',
        'note' => 'Alışkanlığı istikrarlı bir tempoyla sürdürün, gelecek ay daha da kolay olacak.',
        'cta' => 'Yorumları aç',
    ],

    'review_anomaly' => [
        'subject' => 'Dikkat: yorumlarınızda kontrol edilecek :count şey',
        'intro' => 'Yorumlarınızda bakmaya değer bir şey fark ettik:',
        'stalled' => 'genellikle etkin olmasına rağmen :days gündür yeni yorum yok.',
        'negative_streak' => '3 gün içinde :count düşük yıldızlı yorum. Zararı sınırlamak için hızlıca yanıt verin.',
        'spike' => 'olağandışı artış: 7 günde :recent yorum (normalde haftada yaklaşık :baseline). İyi haber olabilir ya da spam açısından kontrol etmeye değer.',
        'rating_drop' => 'puan düşüyor: son zamanlarda :recent★, öncesinde :prior★.',
        'cta' => 'Yorumları aç',
    ],

    'invite' => [
        'subject' => 'Repunio\'da :workspace çalışma alanına katılmaya davet edildiniz',
        'greeting' => 'Merhaba,',
        'intro' => ':inviter sizi Repunio\'da :workspace çalışma alanına :role olarak katılmaya davet etti.',
        'note' => 'Bu davetin süresi 14 gün içinde dolar. Beklemiyorsanız bu e-postayı yok sayabilirsiniz.',
        'cta' => 'Daveti kabul et',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Her yorum, tek gelen kutusu',
        'intro' => 'Konumlarınızdan gelen tüm yorumlar tek bir gelen kutusuna düşer. Puana, konuma ya da yanıtsızlara göre filtreleyin ve iki tıklamayla yanıtlayın.',
        'tip' => 'Şimdi deneyin: bir yorumu açın ve Yapay zeka ile oluştur\'a basın. Yayınlamadan önce düzenleyebileceğiniz, kendi üslubunuzda hazır bir taslak alırsınız.',
        'cta' => 'Yorumlarınızı açın',
    ],
    'drip_automation' => [
        'subject' => 'Yanıtları otomatik pilota alın',
        'intro' => 'İşletmenizi ve üslubunuzu bilen bir yapay zeka aracısı oluşturun, ardından otomatik yanıt kurallarının rutin yorumları sizin için yanıtlamasına izin verin.',
        'tip' => 'Tam otomatik pilota hazır değil misiniz? Onay kuyruğunu kullanın: yapay zeka taslak hazırlar, siz tek tıklamayla onaylarsınız.',
        'cta' => 'Otomasyonları kur',
    ],
    'drip_growth' => [
        'subject' => 'Bu ay daha fazla yorum toplayın',
        'intro' => 'Konum başına aylık bir yorum hedefi belirleyin, biz de tempoyu takip edelim, dönüm noktalarını kutlayalım ve anormallikler konusunda sizi uyaralım.',
        'tip' => 'Yorum toplama sayfanızı oluşturun: mutlu müşterileri doğrudan Google ya da TripAdvisor yorum formunuza gönderen kısa bir bağlantı ve QR kodu.',
        'cta' => 'Yorum sayfanızı oluşturun',
    ],
    'drip_reports' => [
        'subject' => 'Gerçekten okunan raporlar',
        'intro' => 'Bloklardan bir performans raporu oluşturun: KPI\'lar, yapay zeka özeti, personel anmaları, temalar. PDF olarak indirin ya da bir bağlantı paylaşın.',
        'tip' => 'Bir kez ayarlayın, her ay gönderin: raporu zamanlayın, İngilizce ya da Almanca olarak gelen kutularına otomatik olarak düşsün.',
        'cta' => 'Bir rapor oluştur',
    ],
    'drip_team' => [
        'subject' => 'Ekibinizi dahil edin',
        'intro' => 'Ekip arkadaşlarınızı rollerle davet edin ya da yalnızca bildirim ve rapor alan, girişe gerek olmayan misafirler ekleyin.',
        'tip' => 'Ayarlar altında hangi e-postayı kimin alacağına karar verin, ardından yeni yorum uyarılarını bunları ele alan kişilere yönlendirin.',
        'cta' => 'Ekibinizi davet edin',
    ],
    'drip_member' => [
        'subject' => 'Repunio\'da yolunuzu bulmak',
        'intro' => 'Bir çalışma alanına eklendiniz. İşin yapıldığı yer Yorumlar gelen kutusudur: filtreleyin, yanıtlayın, bitti.',
        'tip' => 'Her şeyin istediğiniz gibi gelmesi için arayüz ve e-posta dilinizi profilinizde ayarlayın.',
        'cta' => 'Repunio\'yu aç',
    ],
    'drip_unsubscribe' => 'Çok mu fazla ipucu? :link',
    'drip_unsubscribe_link' => 'Bu e-postalardan çık',

    'unsubscribed_title' => 'Abonelikten çıktınız',
    'unsubscribed_body' => 'Artık ürün ipuçları ve karşılama e-postaları almayacaksınız. Önemli hesap ve faturalandırma e-postaları yine de gelir. Fikrinizi mi değiştirdiniz? Bunları :link üzerinden tekrar açın.',
    'unsubscribed_profile' => 'profiliniz',
];
