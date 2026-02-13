<?php

return [
    'title' => 'لوحة متابعة العمليات',
    'subtitle' => 'راقب الفرص، الطلبات، ومشاركة المتطوعين من مكان واحد.',
    'stats' => [
        'opportunities_total' => 'إجمالي الفرص',
        'opportunities_published' => 'فرص منشورة',
        'opportunities_upcoming' => 'فرص قادمة',
        'applications_pending' => 'طلبات بانتظار المراجعة',
        'applications_approved' => 'طلبات مقبولة',
        'volunteers_total' => 'عدد المتطوعين المسجلين',
        'volunteers_active' => 'متطوعون نشطون',
        'certificates_issued' => 'شهادات صادرة',
        'blacklist_active' => 'حسابات مجمّدة',
    ],
    'breakdown' => [
        'heading' => 'حالة طلبات التطوع',
        'subtitle' => 'توزيع جميع الطلبات عبر مراحل الاعتماد.',
        'statuses' => [
            'pending' => 'قيد الانتظار',
            'under_review' => 'قيد المراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
            'waitlisted' => 'قائمة انتظار',
            'cancelled' => 'ملغى',
        ],
    ],
    'table' => [
        'status' => 'الحالة',
        'count' => 'العدد',
        'progress' => 'النسبة',
        'opportunity' => 'الفرصة',
        'organization' => 'الجهة',
        'starts_at' => 'تبدأ في',
        'volunteers_needed' => 'المتطوعون المطلوبون',
        'volunteer' => 'المتطوّع',
        'submitted_at' => 'تاريخ التقديم',
        'not_available' => 'غير متوفر',
    ],
    'recent' => [
        'opportunities' => 'أحدث الفرص',
        'applications' => 'أحدث الطلبات',
        'empty' => 'لا توجد سجلات متاحة حالياً.',
    ],
    'approvals' => [
        'heading' => 'الموافقات والمتابعة',
        'supervisor_pending' => 'قرارات المشرف المعلقة',
        'liaison_pending' => 'قرارات ضابط الارتباط المعلقة',
        'drafts_owned' => 'مسودات أعددتها',
        'awaiting_publication' => 'مسودات بانتظار النشر',
        'empty' => 'لا توجد إجراءات مطلوبة حالياً.',
    ],
    'reminders' => [
        'heading' => 'التذكيرات المطلوب إرسالها',
        'empty' => 'لا توجد تذكيرات مستحقة خلال :days يوم/أيام القادمة.',
        'days_before' => 'تذكير قبل :days يوم من البداية',
    ],
    'evaluations' => [
        'heading' => 'التقييمات والمتابعة',
        'supervisor' => 'تقييمات المشرفين',
        'volunteer' => 'ملاحظات المتطوعين',
        'average_score' => 'متوسط تقييم المشرف',
    ],
    'levels' => [
        'heading' => 'مستويات المتطوعين ومزاياهم',
        'assigned' => 'تعيينات المستويات',
        'unassigned' => 'متطوعون بلا مستوى',
        'points' => 'نطاق النقاط: :min – :max',
        'empty' => 'لم يتم إعداد مستويات بعد.',
    ],
    'features' => [
        'heading' => 'تغطية الوظائف الأساسية',
        'items' => [
            'announcement' => [
                'title' => 'إعلان الفرص التطوعية',
                'description' => 'بوابة عامة ببطاقات قابلة للمشاركة تعرض الاسم، الساعات، الموعد، والشروط.',
            ],
            'roles' => [
                'title' => 'الأدوار التشغيلية المحددة',
                'description' => 'ضباط ارتباط ومشرفون بإدارات وصلاحيات دقيقة.',
            ],
            'opportunity_data' => [
                'title' => 'بيانات الفرصة المتكاملة',
                'description' => 'توثيق للموقع، المهارات، اللغات، العدد المطلوب، وآخر موعد للتقديم.',
            ],
            'registration_rules' => [
                'title' => 'ضوابط أهلية المتطوعين',
                'description' => 'يتم التحقق من العمر، الجنس، موافقة ولي الأمر، وتوفر المركبة عند التقديم.',
            ],
            'supervisor_flow' => [
                'title' => 'مسار اعتماد المشرفين',
                'description' => 'المشرف ورئيس الوحدة يراجعان الطلبات ويوافقان أو يرفضان مع توثيق الملاحظات.',
            ],
            'volunteer_profile' => [
                'title' => 'سجل المتطوع',
                'description' => 'ملف متكامل لكل متطوع يتضمن البيانات، الخبرات، وسجل المشاركة.',
            ],
            'acceptance_commitment' => [
                'title' => 'التعهدات والموافقات',
                'description' => 'إدارة توقيع الالتزام، موافقة التصوير، وجدولة التذكيرات قبل الفرصة.',
            ],
            'evaluations' => [
                'title' => 'تقييم ثنائي الاتجاه',
                'description' => 'المشرف يمنح علامة وتعليقاً بينما يقيم المتطوع الفرصة التطوعية.',
            ],
            'levels' => [
                'title' => 'مستويات ومزايا المتطوعين',
                'description' => 'مستويات متعددة بنقاط ومكافآت يمكن تخصيصها.',
            ],
            'certificates' => [
                'title' => 'إصدار الشهادات',
                'description' => 'شهادات تصدر من مركز زها، ومع الشراكات يتم إصدار شهادة إضافية من "نحن".',
            ],
            'post_evaluation' => [
                'title' => 'التواصل بعد المشاركة',
                'description' => 'إدارة النقاط والساعات لإرسال رسائل الشكر والمكافآت.',
            ],
            'blacklist' => [
                'title' => 'قائمة الحظر',
                'description' => 'تجميد حسابات المخالفين مع إشعار فوري بالقرار.',
            ],
        ],
    ],
];
