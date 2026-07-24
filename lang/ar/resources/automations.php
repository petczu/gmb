<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'لا توجد أتمتة بعد',
    'empty_desc' => 'أعِدّ أتمتة للرد على التقييمات الجديدة تلقائيًا، حسب التقييم والموقع.',
    'empty_cta' => 'أتمتة جديدة',

    // Table columns
    'col_rating' => 'التقييم',
    'rating_any' => 'أي',
    'col_reply' => 'الرد',
    'reply_ai' => 'ذكاء اصطناعي: :agent',
    'reply_default' => 'الرسالة الافتراضية',
    'col_mode' => 'الوضع',
    'mode_approval' => 'موافقة',
    'mode_auto' => 'النشر التلقائي',
    'col_scope' => 'النطاق',
    'scope_all' => 'كل المواقع',
    'scope_count' => ':count موقع',

    // Run action
    'run_now' => 'تشغيل الآن',
    'run_heading' => 'تشغيل هذه الأتمتة الآن',
    'run_desc' => 'طبّق هذه الأتمتة على التقييمات المطابقة غير المُجاب عنها. يمكنك اختياريًا تقييدها بفترة تاريخ تقييم؛ اترك الخانتين فارغتين لتشمل الكل.',
    'run_from' => 'التقييمات من',
    'run_until' => 'التقييمات حتى',
    'run_title' => 'تم تشغيل ":name"',
    'run_queued_title' => 'تمت إضافة ":name" إلى الطابور',
    'run_queued_body' => 'يجري التشغيل في الخلفية. تصل المسودات الجديدة إلى الموافقات وتظهر الردود المنشورة تلقائيًا على التقييمات خلال الدقائق القادمة.',
    'run_body' => 'تم إنشاء :generated، ونشر :published، وإضافة :queued إلى الطابور، وتخطّي :skipped.',

    // Form — Flow section
    'flow_section' => 'التدفّق',
    'flow_section_desc' => 'متى تعمل الأتمتة وأي التقييمات تنطبق عليها.',
    'trigger' => 'المُشغِّل',
    'trigger_new_review' => 'تقييم جديد على Google',
    'rating_is' => 'التقييم هو…',
    'rating_helper' => 'اترك الكل بلا تحديد للتطبيق على أي تقييم.',
    'all_locations' => 'كل المواقع',
    'locations' => 'المواقع',
    'all_locations_helper' => 'يعمل كخيار شامل: الأتمتة المقيّدة بمواقع محددة لها الأولوية لمواقعها.',
    'covered_by' => 'مشمول بالفعل في ":name" (:ratings)',
    'any_rating' => 'أي تقييم',
    'overlap_title' => 'تتداخل مع أتمتة أخرى',
    'overlap_body' => 'تطابق أيضًا التقييمات نفسها: :list. يُعالَج كل تقييم بأتمتة واحدة تمامًا: المواقع المحددة تتغلب على "كل المواقع"، وإلا فتعمل الأقدم.',
    'respect_working_hours' => 'احترام ساعات العمل',
    'respect_working_hours_helper' => 'الرد فقط خلال ساعات عمل الموقع.',
    'reply_to_previous' => 'الرد على التقييمات السابقة',
    'reply_to_previous_helper' => 'عالج أيضًا التقييمات الحالية غير المُجاب عنها (تُحتسب ضمن مخصصك الشهري للذكاء الاصطناعي).',
    'approve_before_posting' => 'الموافقة قبل النشر',
    'approve_before_posting_helper' => 'إيقاف = نشر تلقائي على Google. تفعيل = إرسال إلى الموافقات أولًا.',

    // Form — Timing section
    'timing_section' => 'التوقيت',
    'timing_section_desc' => 'أضف تأخيرًا عشوائيًا (وساعات عمل اختيارية) كي تُنشر الردود في أوقات عضوية بوتيرة بشرية بدلًا من الفورية.',
    'reply_delay_min' => 'الحد الأدنى للتأخير',
    'reply_delay_max' => 'الحد الأقصى للتأخير',
    'minutes_suffix' => 'دقيقة',
    'reply_delay_helper' => 'تُنشر الردود بعد تأخير عشوائي بين الحد الأدنى والأقصى، كي تبدو عضوية. اضبط كليهما على 0 للنشر فورًا.',
    'reply_delay_max_error' => 'يجب أن يكون الحد الأقصى للتأخير أكبر من أو يساوي الحد الأدنى للتأخير.',
    'working_days' => 'أيام العمل',
    'working_start' => 'وقت البدء',
    'working_end' => 'وقت الانتهاء',
    'day_mon' => 'إثنين',
    'day_tue' => 'ثلاثاء',
    'day_wed' => 'أربعاء',
    'day_thu' => 'خميس',
    'day_fri' => 'جمعة',
    'day_sat' => 'سبت',
    'day_sun' => 'أحد',

    // Form — Content section
    'content_section' => 'المحتوى',
    'content_section_desc' => 'الرد الذي يُرسَل.',
    'content_ai_agent' => 'وكيل الذكاء الاصطناعي',
    'content_default_message' => 'الرسالة الافتراضية',
    'ai_agent' => 'وكيل الذكاء الاصطناعي',
    'default_message' => 'الرسالة الافتراضية',
];
