<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhooks ميزة في خطة Pro',
    'pro_only_body' => 'احصل على طلب HTTP POST موقّع لحظة ورود تقييم، أو نشر رد، أو بلوغ هدف، أو رصد حالة شاذة. قم بالترقية إلى Pro لإضافة نقاط النهاية.',
    'see_plans' => 'عرض الخطط',

    'intro' => 'نرسل حمولة JSON موقّعة عبر POST إلى نقطة النهاية لديك عند كل حدث مشترَك، مع إعادة المحاولة. تحقّق من ترويسة X-Webhook-Signature مقابل سر نقطة النهاية الخاصة بك.',

    'docs_link' => 'توثيق Webhook',
    'empty' => 'لا توجد نقاط نهاية webhook بعد.',
    'col_url' => 'عنوان URL',
    'col_events' => 'الأحداث',
    'col_active' => 'نشط',
    'col_last' => 'آخر إطلاق',

    'create' => 'إضافة نقطة نهاية',
    'create_heading' => 'إضافة نقطة نهاية webhook',
    'edit' => 'تحرير',
    'delete' => 'حذف',
    'saved' => 'تم حفظ نقطة النهاية',
    'created' => 'تمت إضافة نقطة النهاية',
    'deleted' => 'تم حذف نقطة النهاية',

    'field_name' => 'الاسم (اختياري)',
    'field_url' => 'عنوان URL لنقطة النهاية',
    'field_events' => 'الأحداث',
    'field_active' => 'نشط',

    'secret' => 'السر',
    'secret_heading' => 'سر التوقيع',
    'secret_desc' => 'استخدم هذا للتحقق من توقيع الحمولة.',
    'signature_hint' => 'كل طلب موقّع:',

    'deliveries' => 'عمليات التسليم',
    'deliveries_heading' => 'عمليات التسليم الأخيرة',
    'no_deliveries' => 'لا توجد عمليات تسليم بعد.',
    'attempts' => 'محاولات',
    'resend' => 'إعادة الإرسال',
    'resent' => 'تمت إعادة التسليم إلى الطابور',
    'status_pending' => 'قيد الانتظار',
    'status_success' => 'تم التسليم',
    'status_failed' => 'فشل',

    'event_review_created' => 'تقييم جديد',
    'event_reply_published' => 'تم نشر الرد',
    'event_goal_reached' => 'تم بلوغ الهدف',
    'event_anomaly_detected' => 'رُصدت حالة شاذة',
];
