<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'لا شيء للموافقة عليه',
    'empty_desc' => 'عندما تصوغ الأتمتة ردودًا تحتاج إلى موافقة، تظهر هنا.',

    // Columns
    'col_location' => 'الموقع',
    'col_author' => 'الكاتب',
    'col_rating' => 'التقييم',
    'col_review' => 'التقييم',
    'col_ai_reply' => 'رد الذكاء الاصطناعي',
    'col_status' => 'الحالة',
    'col_source' => 'المصدر',
    'col_generated' => 'أُنشئ',
    'source_ai' => 'ذكاء اصطناعي',
    'source_template' => 'قالب',

    // Statuses
    'status_pending' => 'قيد الانتظار',
    'status_scheduled' => 'مجدول',
    'status_published' => 'منشور',
    'status_skipped' => 'تم تخطّيه',
    'status_failed' => 'فشل',
    'status_indicator' => 'الحالة: :status',
    'scheduled_for' => 'يُنشر :time',

    // Actions
    'approve' => 'موافقة ونشر',
    'approve_publish' => 'موافقة ونشر',
    'edit_publish' => 'تحرير ونشر',
    'review_reply' => 'مراجعة ورد',
    'reply' => 'رد',
    'reject' => 'رفض',

    // Filters
    'filter_date' => 'تاريخ التقييم',
    'filter_from' => 'من :date',
    'filter_to' => 'حتى :date',

    // Notifications
    'reply_published' => 'تم نشر الرد',

    'approve_selected' => 'موافقة ونشر المحدد',
    'reject_selected' => 'رفض المحدد',
    'bulk_approve_confirm' => 'نشر كل الردود المحددة على Google؟ تُضاف إلى الطابور وتُنشر تلقائيًا خلال الدقائق القادمة.',
    'bulk_reject_confirm' => 'رفض كل المسودات المحددة؟',
    'bulk_queued' => 'تمت إضافة :count رد إلى طابور النشر',
    'bulk_queued_body' => 'تُنشر تلقائيًا خلال الدقائق القادمة. ويظهر أي فشل ضمن عامل تصفية الفاشلة مع السبب.',
    'bulk_rejected' => 'تم رفض :count مسودة',
    'publish_failed_title' => 'فشل النشر',
    'publish_not_found' => 'يقول Google إن هذا التقييم لم يعد موجودًا. ربما حذفه كاتبه، أو أُعيد ربط الموقع تحت حساب جديد. تم وسم المسودة كفاشلة.',
    'publish_error' => 'تعذّر نشر الرد. تم وسم المسودة كفاشلة: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'لم يتمكن Google من العثور على هذا التقييم أو الموقع للرد عليه. ربما أُزيل، أو أن الردود غير متاحة لهذا الموقع.',
    'error_rate_limited' => 'يقيّد Google سرعة نشر الردود. ستُعاد المحاولة تلقائيًا.',
    'error_unauthorized' => 'اتصال Google غير مخوّل بالرد هنا. أعد ربط الحساب وحاول مجددًا.',
    'error_generic' => 'تعذّر نشر الرد. يُرجى المحاولة لاحقًا.',
    'draft_rejected' => 'تم رفض المسودة',

    // Scheduled items
    'post_now' => 'انشر الآن',
    'post_now_confirm' => 'يُنشر الرد على Google فورًا، متجاوزًا وقته المجدول.',
    'post_now_queued' => 'تمت إضافة الرد إلى طابور النشر',
    'post_now_queued_body' => 'يُنشر خلال الدقائق القليلة القادمة.',
    'cancel_scheduled' => 'إلغاء',
    'cancel_scheduled_confirm' => 'إلغاء هذا الرد المجدول؟ لن يتم نشره.',
    'schedule_cancelled' => 'تم إلغاء الرد المجدول',

    // List tabs
    'tab_pending' => 'يحتاج إلى موافقة',
    'tab_all' => 'الكل',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'نشر المحدد الآن',
    'bulk_publish_now_confirm' => 'تتجاوز الردود المحددة وقتها المجدول وتُنشر خلال الدقائق القليلة القادمة.',
    'cancel_scheduled_selected' => 'إلغاء الجدولة',
];
