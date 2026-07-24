<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'مطعم تجريبي',
    'demo_period' => 'تقرير الأداء · آخر 30 يومًا',
    'demo_five_star' => 'نسبة تقييمات 5 نجوم',
    'demo_summary_label' => 'الملخص التنفيذي',
    'demo_summary' => 'استقبل المطعم التجريبي 38 تقييمًا خلال آخر 30 يومًا (+9 مقارنةً بالفترة السابقة)، بمتوسط 4.60★. كانت 84% من التقييمات إيجابية وبلغ معدل الرد 92%. وأثنى الضيوف مرارًا على الفريق الودود والخدمة السريعة.',

    'location' => 'الموقع',
    'business_multi' => ':name + :count آخرون',
    'compare' => 'مقارنة',
    'compare_options' => [
        'none' => 'عدم المقارنة',
        'previous' => 'الفترة السابقة',
        'custom' => 'نطاق مخصص…',
    ],
    'compare_from' => 'المقارنة من',
    'compare_to' => 'المقارنة إلى',
    'report_language' => 'لغة التقرير',

    'content_section' => 'محتوى التقرير',
    'content_section_desc' => 'اختر إعدادًا مسبقًا، ثم اضبط بدقة المكوّنات التي تظهر في التقرير.',
    'preset' => 'إعداد مسبق',
    'blocks' => 'المكوّنات',
    'competitors_block_hint' => 'لا يوجد منافسون متابَعون بعد. أضفهم أولًا من القوائم ← المنافسون.',
    'ai_instructions' => 'تعليمات الذكاء الاصطناعي',
    'ai_instructions_help' => 'إرشاد اختياري للسرد بالذكاء الاصطناعي. مفيد جدًا لأسماء الموظفين: اذكر فريقك وأي ألقاب كي تُطابَق الإشارات بالشخص الصحيح. يُحفظ مرة واحدة ويُطبَّق على كل تقرير مستقبلي، بما في ذلك المجدولة.',
    'ai_instructions_placeholder' => 'موظفونا: إيفا، أليت، سليمان (يُكتب أيضًا سولي)، ليزا. ادمج الألقاب في الاسم الكامل.',
    'ai_improve' => 'التحسين بالذكاء الاصطناعي',
    'ai_improve_empty' => 'اكتب بضع ملاحظات أولًا، ثم حسّنها.',
    'ai_improve_rate_limited' => 'محاولات كثيرة، حاول مجددًا لاحقًا.',
    'ai_improve_done' => 'تم تحسين التعليمات',
    'ai_improve_failed' => 'تعذّر تحسين التعليمات، يُرجى المحاولة مجددًا.',

    'schedule_report' => 'الإرسال وفق جدول',
    'schedule_heading' => 'جدولة هذا التقرير',
    'schedule_desc' => 'سيُرسَل التحديد الحالي (الفترة، الموقع، المقارنة، المكوّنات) بصيغة PDF وفق جدول متكرر.',
    'schedule_submit' => 'إنشاء جدول',
    'schedule_created' => 'تم إنشاء الجدول',
    'schedule_created_body' => 'أدره من التقارير ← التقارير المجدولة.',

    // Usage line ("N of M AI reports left this month")
    'usage' => 'بقي :left من أصل :cap تقرير ذكاء اصطناعي هذا الشهر',

    // Generate modal
    'generate_heading' => 'إنشاء تقرير بالذكاء الاصطناعي؟',
    'generate_desc' => 'أنشئ الملخص التنفيذي بالذكاء الاصطناعي للتحديد الحالي.',
    'generate_desc_left' => 'يستخدم هذا 1 من تقارير الذكاء الاصطناعي الشهرية لديك، بقي :left.',
    'generate_submit' => 'إنشاء',

    // Generate notifications
    'report_generated' => 'تم إنشاء التقرير',
    'report_generated_body' => 'ملخص الذكاء الاصطناعي جاهز، وتم تحديث المعاينة. استخدم تنزيل لحفظ ملف PDF.',
    'limit_reached' => 'تم بلوغ الحد الشهري للتقارير',
    'limit_reached_body' => 'يُعرض تقرير أساسي بدون ذكاء اصطناعي. قم بالترقية للحصول على حد شهري أعلى.',

    // Blade view
    'generate_report' => 'إنشاء تقرير',
    'generating' => 'جارٍ الإنشاء…',
    'download_pdf' => 'تنزيل PDF',
    'download_first_tooltip' => 'أنشئ التقرير أولًا',
    'building' => 'جارٍ بناء التقرير…',
    'preview_title' => 'معاينة التقرير',
];
