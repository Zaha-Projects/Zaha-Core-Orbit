<?php

return [
    'title' => 'تقارير العجلات الخضراء',
    'subtitle' => 'تابع أداء العجلات الخضراء على مستوى الفروع.',
    'filters' => [
        'start' => 'تاريخ البداية',
        'end' => 'تاريخ النهاية',
        'branch' => 'فرع زها',
        'all_branches' => 'كل الفروع',
        'apply' => 'تطبيق الفلاتر',
        'reset' => 'إعادة تعيين',
    ],
    'kpis' => [
        'exact_weight' => 'إجمالي الوزن الدقيق',
        'weight_unit' => 'كغم',
        'ambassadors' => 'عدد السفراء',
        'ambassadors_hint' => 'سفراء العجلات الخضراء',
        'support_in_kind' => 'قيمة الدعم العيني',
        'support_financial' => 'قيمة الدعم النقدي',
        'support_unit' => 'دينار',
        'average_delay' => 'متوسط تأخير التفريغ',
        'delay_unit' => 'يوم',
    ],
    'charts' => [
        'exact_weight' => [
            'title' => 'الوزن الدقيق حسب الفرع',
            'description' => 'مجموع الأوزان الدقيقة (الميزان) لكل فرع خلال الفترة المحددة.',
            'dataset' => 'الوزن الدقيق (كغم)',
        ],
        'ambassadors' => [
            'title' => 'السفراء حسب الفرع',
            'description' => 'عدد سفراء العجلات الخضراء لكل فرع.',
            'dataset' => 'عدد السفراء',
        ],
        'support_in_kind' => [
            'title' => 'الدعم العيني حسب الفرع',
            'description' => 'قيمة الدعم العيني المُسجّل لكل فرع.',
            'dataset' => 'الدعم العيني (دينار)',
        ],
        'support_financial' => [
            'title' => 'الدعم النقدي حسب الفرع',
            'description' => 'قيمة الدعم النقدي المُسجّل لكل فرع.',
            'dataset' => 'الدعم النقدي (دينار)',
        ],
        'unloading_delay' => [
            'title' => 'تأخير التفريغ حسب الفرع',
            'description' => 'متوسط فرق الأيام بين طلب التفريغ وتاريخ التفريغ الفعلي.',
            'dataset' => 'أيام التأخير',
        ],
    ],
    'table' => [
        'title' => 'التفاصيل',
        'empty' => 'لا توجد بيانات مطابقة للفلاتر المحددة.',
        'branch' => 'الفرع',
        'exact_weight' => 'الوزن الدقيق (كغم)',
        'ambassadors' => 'عدد السفراء',
        'support_in_kind' => 'الدعم العيني (دينار)',
        'support_financial' => 'الدعم النقدي (دينار)',
        'last_requested' => 'آخر طلب تفريغ',
        'last_unloaded' => 'آخر تفريغ فعلي',
        'average_delay' => 'متوسط التأخير (أيام)',
        'scheduled_count' => 'عدد الطلبات',
        'unloaded_count' => 'عدد التفريغات',
        'unknown_branch' => 'فرع غير معروف',
    ],
    'actions' => [
        'view_details' => 'عرض التفاصيل',
        'back_to_reports' => 'العودة للتقارير',
    ],
    'breadcrumbs' => [
        'dashboard' => 'لوحة التحكم',
        'reports' => 'تقارير العجلات الخضراء',
    ],
];
