<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عند تحقق الشرط المحدد.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'boolean' => 'يجب اختيار قيمة صحيحة لحقل :attribute.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'mimes' => 'يجب أن يكون ملف :attribute من نوع: :values.',
    'max' => [
        'file' => 'يجب ألا يتجاوز حجم ملف :attribute :max كيلوبايت.',
        'string' => 'يجب ألا يتجاوز طول :attribute :max حرفاً.',
    ],

    'custom' => [
        'outside_place_name' => [
            'required_if' => 'يرجى تعبئة اسم الموقع الخارجي عند اختيار نوع المكان خارج المركز.',
        ],
        'outside_google_maps_url' => [
            'required_if' => 'يرجى إضافة رابط الموقع عند اختيار نوع المكان خارج المركز.',
        ],
        'outside_contact_number' => [
            'required_if' => 'يرجى تعبئة رقم تواصل المكان الخارجي عند اختيار نوع المكان خارج المركز.',
            'regex' => 'يجب إدخال رقم تواصل أردني صحيح للمكان الخارجي مثل 0791234567 أو 065001234 أو +962791234567.',
        ],
        'external_liaison_name' => [
            'required_if' => 'يرجى تعبئة اسم ضابط الارتباط عند اختيار نوع المكان خارج المركز.',
        ],
        'external_liaison_phone' => [
            'required_if' => 'يرجى تعبئة رقم ضابط الارتباط عند اختيار نوع المكان خارج المركز.',
            'regex' => 'يجب إدخال رقم هاتف خلوي أردني صحيح لضابط الارتباط مثل 0791234567 أو +962791234567.',
        ],
        'supplies.*.provider_name' => [
            'required' => 'يرجى تعبئة تفاصيل التأمين عندما تكون آلية تأمين المستلزم "أخرى".',
            'required_if' => 'يرجى تعبئة "اسم الجهة الموفِّرة" عندما تكون حالة المستلزم "غير متوفر".',
        ],
        'supplies.*.provider_type' => [
            'required' => 'يرجى اختيار آلية تأمين المستلزم عندما تكون حالة المستلزم "غير متوفر".',
            'required_if' => 'يرجى اختيار "جهة التوفير" عندما تكون حالة المستلزم "غير متوفر".',
        ],
    ],

    'attributes' => [
        'title' => 'عنوان النشاط',
        'activity_date' => 'تاريخ النشاط',
        'proposed_date' => 'تاريخ النشاط المخطط',
        'branch_id' => 'الفرع',
        'internal_location' => 'القاعة / الموقع الداخلي',
        'outside_place_name' => 'اسم الموقع الخارجي',
        'outside_google_maps_url' => 'رابط الموقع',
        'outside_contact_number' => 'رقم تواصل المكان الخارجي',
        'external_liaison_name' => 'اسم ضابط الارتباط',
        'external_liaison_phone' => 'رقم ضابط الارتباط',
        'outside_address' => 'العنوان التفصيلي',
        'description' => 'الوصف التفصيلي',
        'supplies.*.item_name' => 'اسم المستلزم',
        'supplies.*.available' => 'حالة التوفر',
        'supplies.*.provider_type' => 'جهة التوفير',
        'supplies.*.provider_name' => 'اسم الجهة الموفِّرة',
    ],
];
