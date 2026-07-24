<?php

declare(strict_types=1);

return [
    'nav' => 'المنافسون',
    'title' => 'المنافسون',
    'intro' => 'تابع الأنشطة التجارية القريبة وقارن تقييمها على Google وعدد مراجعاتها بمواقعك. تُحدَّث الأرقام تلقائيًا كل يوم.',

    'empty' => 'لا يوجد منافسون بعد.',
    'empty_desc' => 'أضف منافسًا لتتبّع تقييمه على Google ونمو مراجعاته.',

    'not_configured_title' => 'لم يتم تكوين متابعة المنافسين',
    'not_configured_body' => 'اضبط GOOGLE_PLACES_API_KEY في بيئة الخادم (مفتاح Google Places API) لتفعيل مقارنة المنافسين.',

    'col_battle' => 'المنافس',
    'col_name' => 'المنافس',
    'col_rating' => 'التقييم',
    'col_reviews' => 'المراجعات',
    'filter_location' => 'الموقع',
    'filter_city' => 'المدينة',
    'col_vs' => 'مقابلك',
    'col_location' => 'جانبك',
    'col_checked' => 'حُدّث',

    'untitled_battle' => 'منافسة بلا عنوان',
    'default_battle_name' => '{1} :location مقابل منافس واحد|[2,*] :location مقابل :count منافسين',
    'own_locations_count' => ':count مواقع',
    'rating_weighted_hint' => 'التقييم بمتوسّط عبر المنافسين، مرجّحًا بأعداد مراجعاتهم.',

    'vs_ahead' => 'تتقدّم بـ :delta ★',
    'vs_behind' => 'يتقدّمون بـ :delta ★',
    'vs_tied' => 'تعادل',
    'vs_unknown' => '—',

    'add' => 'إضافة منافس',
    'add_heading' => 'إضافة منافس',
    'edit' => 'تحرير',
    'edit_heading' => 'تحرير المنافسين',
    'field_name' => 'اسم المنافسة',
    'field_name_placeholder' => 'مثال: الشارع الرئيسي مقابل الحي',
    'field_your_locations' => 'مواقعك',
    'field_your_locations_helper' => 'اختر واحدًا أو أكثر من مواقعك لجانبك.',
    'field_place' => 'المنافس',
    'field_places' => 'المنافسون',
    'field_places_helper' => 'اكتب اسم نشاط تجاري (والمدينة) للبحث في Google Places.',
    'already_tracked' => 'أنت تتابع هذا المنافس بالفعل.',
    'saved' => 'تم حفظ المنافس',
    'some_failed' => 'تعذّر جلب :count منافس وتم تخطّيهم.',

    'duplicate' => 'تكرار',
    'duplicate_heading' => 'تكرار المنافس',
    'copy_name' => ':name (نسخة)',
    'remove' => 'إزالة',
    'removed' => 'تمت إزالة المنافس',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'إنشاء مجموعة',
    'group_heading' => 'تجميع المنافسين',
    'group_need_two' => 'اختر منافسَين على الأقل للتجميع.',
    'group_created' => 'تم إنشاء المجموعة',
    'group_removed' => 'تمت إزالة المجموعة',
    'ungroup' => 'إزالة من المجموعة',
    'ungrouped' => 'تمت الإزالة من المجموعة',
    'field_group_name' => 'اسم المجموعة',
    'field_group_competitors' => 'المنافسون',
    'field_group_competitors_helper' => 'يندمج هؤلاء المنافسون في خط واحد على مخطط النمو، مع جمع مراجعاتهم.',
    'col_group' => 'المجموعة',

    'col_new_reviews' => 'مراجعات جديدة',
    'col_rating_trend' => 'تغيّر التقييم',
    'col_trend' => 'الاتجاه',
    'you_delta' => 'أنت: :delta',
    'trend_hint' => 'المراجعات الجديدة في الفترة المختارة.',
    'trend_collecting' => 'جارٍ الجمع…',
    'period_4w' => '4 أسابيع',
    'period_12w' => '3 أشهر',

    'collecting' => 'جارٍ الجمع…',
    'prev_delta' => 'السابق: :delta',
    'period_7d' => '7 أيام',
    'period_6m' => '6 أشهر',
    'no_change' => 'لا تغيير',
    'search_failed' => 'بحث المنافسين غير متاح مؤقتًا',

    // Competitor detail modal
    'view' => 'عرض التفاصيل',
    'close' => 'إغلاق',
    'you' => 'أنت',
    'reviews_count' => '{1} مراجعة واحدة|[2,*] :count مراجعات',
    'no_distribution' => 'تفصيل النجوم غير متاح بعد (يُحدَّث في التحديث التالي).',
];
