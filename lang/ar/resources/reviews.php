<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'الموقع',
    'col_author' => 'الكاتب',
    'col_review' => 'التقييم',
    'col_rating' => 'التقييم',
    'only_rating' => 'تقييم بالنجوم فقط',
    'col_reply' => 'الرد',
    'col_status' => 'الحالة',
    'col_replied_by' => 'رَدّ بواسطة',
    'col_date' => 'التاريخ',
    'replied_ai' => 'ذكاء اصطناعي',
    'replied_human' => 'الفريق',
    'replied_assistant' => 'المساعد',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— لا يوجد رد —',
    'status_replied' => 'تم الرد',
    'status_pending' => 'قيد الانتظار',
    'status_scheduled' => 'مجدول',
    'scheduled_for' => 'يُنشر في :datetime',
    'replied_at' => 'تم الرد :datetime',
    'status_failed' => 'فشل',

    // Filters
    'review_date' => 'تاريخ التقييم',
    'filter_from' => 'من :date',
    'filter_to' => 'إلى :date',
    'reply_status' => 'حالة الرد',
    'review_text' => 'نص التقييم',
    'with_text' => 'بنص',
    'rating_only' => 'تقييم بالنجوم فقط',
    'photos' => 'الصور',
    'with_photos' => 'بصور',
    'without_photos' => 'بدون صور',

    // Reply action
    'edit_reply' => 'تحرير الرد',
    'save_reply' => 'حفظ',
    'reply' => 'رد',
    'reply_to_review' => 'الرد على التقييم',
    'no_written_review' => 'لا يوجد تقييم مكتوب، بالنجوم فقط.',
    'translated_by_google' => '🌐 تُرجم بواسطة Google',
    'ai_agent' => 'وكيل الذكاء الاصطناعي',
    'default_agent' => 'الوكيل الافتراضي',
    'your_reply' => 'ردك',
    'generate_with_ai' => 'إنشاء بالذكاء الاصطناعي',
    'generate' => 'إنشاء',
    'generating' => 'جارٍ إنشاء ردك…',
    'cancel' => 'إلغاء',
    'add_emoji' => 'إضافة رمز تعبيري',
    'show_translation' => 'إظهار الترجمة (:language)',
    'translation_label' => 'الترجمة (:language)',
    'translation_failed' => 'فشلت الترجمة',
    'hide_emoji' => 'إخفاء الرموز التعبيرية',
    'delete_reply' => 'حذف الرد',
    'delete_reply_desc' => 'يزيل هذا الرد من Google. أما التقييم نفسه فلا يتأثر.',
    'delete_confirm' => 'حذف',
    'submit_heading' => 'نشر ردك؟',
    'submit_desc' => 'ينشر هذا ردك علنًا على Google، مرئيًا لكل من يرى التقييم.',
    'submit_confirm' => 'نشر',

    // AI cost hints
    'cost_generic' => 'يُنشئ هذا ردًا بالذكاء الاصطناعي.',
    'cost_all_used' => 'لقد استهلكت كل ردود الذكاء الاصطناعي هذا الشهر. عبّئ حزمة، أو قم بالترقية، أو اكتب الرد يدويًا.',
    'cost_credit' => 'يستخدم هذا 1 رصيد (بقي :count).',
    'cost_monthly' => 'يستخدم هذا 1 من ردود الذكاء الاصطناعي الشهرية لديك، بقي :count.',

    // Notifications
    'reply_deleted' => 'تم حذف الرد',
    'no_changes' => 'لا توجد تغييرات للحفظ',
    'reply_published' => 'تم نشر الرد',
    'reply_failed' => 'تعذّر نشر الرد',
    'ai_limit_reached' => 'تم بلوغ حد الذكاء الاصطناعي',
    'ai_limit_body' => 'لقد استهلكت كل ردود الذكاء الاصطناعي هذا الشهر. حرّر يدويًا، أو قم بالترقية للحصول على حد أعلى.',
    'generation_failed' => 'فشل الإنشاء',
    'reply_generated' => 'تم إنشاء الرد',
    'retry' => 'إعادة المحاولة',
    'retry_heading' => 'إعادة محاولة هذا الرد؟',
    'retry_desc' => 'سنحاول مجددًا: إعادة نشر الرد المُسوَّد، أو إعادة إنشائه إن فشلت خطوة الذكاء الاصطناعي.',
    'retry_queued' => 'تمت إعادة الرد إلى الطابور',
    'retry_nothing' => 'لا شيء لإعادة المحاولة. رُدّ يدويًا بدلًا من ذلك.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'الكل',
    'tab_needs_approval' => 'يحتاج إلى موافقة',
    'tab_scheduled' => 'مجدول',
    'tab_published' => 'منشور',
    'tab_failed' => 'فاشل',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} عرض تقييم واحد من إشعار بريدك|[2,*] عرض :count تقييمات من إشعار بريدك',
    'from_email_clear' => 'عرض كل التقييمات',
];
