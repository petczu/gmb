<?php

declare(strict_types=1);

// Public workspace-invitation landing pages (accept / invalid / wrong-account).
// The locale follows the invitation the owner sent it in (App\Http\Controllers\
// InvitationController), so a German invite renders these in German too.
return [
    'badge' => 'دعوة',

    // Accept page
    'youre_invited' => 'أنت مدعو',
    'join_title' => 'انضم إلى :workspace',
    'join_body' => 'لقد تمت دعوتك للانضمام إلى :workspace على Repunio بصفة :role.',
    'accept_button' => 'قبول والانضمام',

    // Invalid / expired page
    'invalid_title' => 'الدعوة غير متاحة',
    'invalid_body' => 'لم يعد رابط هذه الدعوة صالحًا. ربما انتهت صلاحيته أو تم استخدامه بالفعل. يُرجى طلب رابط جديد ممن دعاك.',
    'go_to_app' => 'الذهاب إلى Repunio',

    // Wrong-account page (signed in as a different user)
    'wrong_title' => 'هذه الدعوة مخصصة لشخص آخر',
    'wrong_body' => 'لقد أُرسلت إلى :invited، لكنك مسجّل الدخول بصفة :current.',
    'wrong_hint' => 'أعد توجيه الرابط إليه، أو سجّل الخروج ثم سجّل الدخول بهذا البريد لقبولها بنفسك.',
    'back_to_app' => 'العودة إلى التطبيق',
    'sign_out' => 'تسجيل الخروج',

    'roles' => [
        'owner' => 'المالك',
        'admin' => 'مسؤول',
        'manager' => 'مدير',
        'member' => 'عضو',
        'viewer' => 'مشاهد',
        'guest' => 'ضيف',
    ],
];
