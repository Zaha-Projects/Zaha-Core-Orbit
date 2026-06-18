<?php

return [
    'cache' => [
        'enabled_key' => 'admin_reports_cache_enabled',
        'ttl_key' => 'admin_reports_cache_ttl_minutes',
        'prefix_key' => 'admin_reports_cache_prefix',
        'default_enabled' => true,
        'default_ttl_minutes' => 30,
        'default_prefix' => 'super-admin.plan-reports',
    ],

    'relations' => [
        'default_tab' => 'relations',
        'available_tabs' => [
            'overview' => 'نظرة عامة',
            'relations' => 'العلاقات والأجندة والخطط الشهرية',
            'operations' => 'التشغيل',
            'enterprise' => 'المؤشرات المؤسسية',
        ],
    ],
];
