<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'أضف تفاصيل شركتك',
    'step_company_hint' => 'بلدك ومعلومات الفوترة المستخدمة في الفواتير والتقارير.',
    'step_plan_label' => 'اختر خطة',
    'step_plan_hint' => 'ابدأ فترتك التجريبية المجانية لمدة 14 يومًا، دون الحاجة إلى بطاقة.',
    'step_location_label' => 'اربط موقعك الأول',
    'step_location_hint' => 'اربط ملفًا تجاريًا على Google لبدء جلب التقييمات.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'إعداد مساحة عملك',
    'wiz_plan_done' => '✓ خطتك مُفعّلة. تابع إلى الخطوة التالية.',
    'wiz_plan_pick' => 'اختر خطة',
    'wiz_interval' => 'دورة الفوترة',
    'wiz_monthly' => 'شهريًا',
    'wiz_yearly' => 'سنويًا',
    'wiz_start_trial' => 'ابدأ التجربة المجانية لمدة 14 يومًا',
    'wiz_trial_note' => 'تبدأ فترتك التجريبية المجانية لمدة 14 يومًا فور متابعتك. دون الحاجة إلى بطاقة.',
    'wiz_go_checkout' => 'المتابعة إلى الدفع',
    'wiz_plan_required' => 'اختر خطة وأكمل الدفع للمتابعة.',
    'wiz_location_body' => 'اربط ملفك التجاري على Google كي نتمكن من جلب تقييماتك. ستتم إعادة توجيهك إلى Google للسماح بالوصول، ثم تختار الموقع المراد ربطه.',
    'wiz_connect_google' => 'ربط الملف التجاري على Google',
    'wiz_skip_location' => 'تخطي الآن',
    'skipped_title' => 'كل شيء جاهز',
    'skipped_body' => 'يمكنك ربط ملفك التجاري على Google في أي وقت من صفحة المواقع.',
    'wiz_per_location' => 'لكل موقع / شهريًا',
    'wiz_plan_desc_starter' => 'صندوق وارد للتقييمات، وردود يدوية، وتقارير أساسية.',
    'wiz_plan_desc_growth' => 'يضيف الردود التلقائية بالذكاء الاصطناعي، والتقارير المجدولة، والمقارنات.',
    'wiz_plan_desc_pro' => 'كل شيء، بالإضافة إلى العلامة البيضاء، وواجهة API، وMCP، ووصول العملاء.',

    // Onboarding overlay
    'welcome_title' => 'مرحبًا، لنُعِدّ حسابك',
    'welcome_subtitle' => 'بضع خطوات سريعة وستكون جاهزًا للانطلاق.',
    'continue_step' => 'المتابعة: :label',
    'enter_app' => 'الدخول إلى التطبيق ←',
    'sign_out' => 'تسجيل الخروج',

    // Pending-deletion overlay
    'deletion_title' => 'مساحة العمل هذه مجدولة للحذف',
    'deletion_body' => 'سيتم حذف جميع البيانات نهائيًا في <strong>:date</strong>. لا يزال بإمكانك الإلغاء والاحتفاظ بمساحة عملك.',
    'cancel_deletion' => 'إلغاء الحذف',

    // Grace banner
    'grace_banner' => '⚠️ لم نتمكن من معالجة دفعتك الأخيرة. تبقى خدمتك مُفعّلة حتى <strong>:date</strong>، يُرجى',
    'update_your_billing' => 'تحديث معلومات الفوترة',

    // Paywall overlay
    'payment_problem_title' => 'هناك مشكلة في دفعتك',
    'needs_plan_title' => 'اختر خطة للبدء',
    'payment_problem_body' => 'تم إيقاف وصولك مؤقتًا لأننا لم نتمكن من معالجة الدفع. حدّث معلومات الفوترة للمتابعة.',
    'needs_plan_body' => 'اختر خطة لتفعيل التقييمات وردود الذكاء الاصطناعي والتقارير لمواقعك. تجربة مجانية لمدة 14 يومًا.',
    'update_billing' => 'تحديث الفوترة',
    'view_plans' => 'عرض الخطط',

    // Connect-select-location page
    'connecting_location' => 'جارٍ ربط الموقع…',
    'choose_location' => 'اختر موقع Google Business الذي تريد ربطه بمساحة العمل هذه.',
    'could_not_load' => 'تعذّر تحميل المواقع',
    'pending_expired_title' => 'انتهت جلسة Google',
    'pending_expired' => 'صلاحية تفويض Google قصيرة الأمد وقد انتهت هذه الصلاحية. أعد الربط واختر مواقعك مجددًا، لن يستغرق الأمر سوى لحظة.',
    'reconnect_google' => 'إعادة الربط بـ Google',
    'back' => 'رجوع',
    'no_locations_available' => 'لا توجد مواقع متاحة',
    'no_locations_body' => 'لم تُعَد أي مواقع Google Business. قد تكون لا تزال قيد التحميل من جهة Google، حاول مجددًا بعد قليل.',
    'connect_then_done' => 'اربط موقعًا واحدًا أو أكثر، ثم انقر تم.',
    'done' => 'تم',
    'connected' => 'متصل',
    'connect' => 'ربط',
    'connecting' => 'جارٍ الربط…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'اختر موقع النشاط التجاري',
    'connect_failed' => 'تعذّر ربط الموقع',
    'connected_title' => 'تم الربط: :name',
    'connected_body' => 'تتم مزامنة التقييمات في الخلفية، وستظهر في صفحة المواقع بعد قليل.',
    'location_fallback' => 'موقع',
    'trial_started_title' => 'بدأت فترتك التجريبية لمدة 14 يومًا',
    'trial_started_body' => 'وصول كامل حتى :date، دون الحاجة إلى بطاقة. استمتع!',
];
