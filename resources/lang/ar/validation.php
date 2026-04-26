<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عند تحقق الشرط المحدد.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'boolean' => 'يجب اختيار قيمة صحيحة لحقل :attribute.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'mimes' => 'يجب أن يكون ملف :attribute من نوع: :values.',
    'max' => [
        'file' => 'يجب ألا يتجاوز حجم ملف :attribute :max كيلوبايت.',
        'string' => 'يجب ألا يتجاوز طول :attribute :max حرفاً.',
    ],

    'custom' => [
        'supplies.*.provider_name' => [
            'required_if' => 'يرجى تعبئة "اسم الجهة الموفِّرة" عندما تكون حالة المستلزم "غير متوفر".',
        ],
        'supplies.*.provider_type' => [
            'required_if' => 'يرجى اختيار "جهة التوفير" عندما تكون حالة المستلزم "غير متوفر".',
        ],
    ],

    'attributes' => [
        'title' => 'عنوان النشاط',
        'activity_date' => 'تاريخ النشاط',
        'proposed_date' => 'تاريخ النشاط المخطط',
        'branch_id' => 'الفرع',
        'internal_location' => 'القاعة / الموقع الداخلي',
        'description' => 'الوصف التفصيلي',
        'supplies.*.item_name' => 'اسم المستلزم',
        'supplies.*.available' => 'حالة التوفر',
        'supplies.*.provider_type' => 'جهة التوفير',
        'supplies.*.provider_name' => 'اسم الجهة الموفِّرة',
    ],
];
