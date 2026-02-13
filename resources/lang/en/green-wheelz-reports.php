<?php

return [
    'title' => 'Green Wheelz reports',
    'subtitle' => 'Track Green Wheelz performance with branch-level insights.',
    'filters' => [
        'start' => 'Start date',
        'end' => 'End date',
        'branch' => 'Zaha branch',
        'all_branches' => 'All branches',
        'apply' => 'Apply filters',
        'reset' => 'Reset',
    ],
    'kpis' => [
        'exact_weight' => 'Total exact weight',
        'weight_unit' => 'kg',
        'ambassadors' => 'Ambassadors registered',
        'ambassadors_hint' => 'Green Wheelz ambassadors',
        'support_in_kind' => 'In-kind support value',
        'support_financial' => 'Financial support value',
        'support_unit' => 'JOD',
        'average_delay' => 'Average unloading delay',
        'delay_unit' => 'days',
    ],
    'charts' => [
        'exact_weight' => [
            'title' => 'Exact weight by branch',
            'description' => 'Sum of exact (scale) intake weights per branch within the selected period.',
            'dataset' => 'Exact weight (kg)',
        ],
        'ambassadors' => [
            'title' => 'Ambassadors by branch',
            'description' => 'Number of Green Wheelz ambassadors registered per branch.',
            'dataset' => 'Ambassadors',
        ],
        'support_in_kind' => [
            'title' => 'In-kind support by branch',
            'description' => 'Reported in-kind support value per branch.',
            'dataset' => 'In-kind support (JOD)',
        ],
        'support_financial' => [
            'title' => 'Financial support by branch',
            'description' => 'Reported financial support value per branch.',
            'dataset' => 'Financial support (JOD)',
        ],
        'unloading_delay' => [
            'title' => 'Unloading delay by branch',
            'description' => 'Average delay (days) between unloading request and actual unloading date.',
            'dataset' => 'Delay days',
        ],
    ],
    'table' => [
        'title' => 'Details',
        'empty' => 'No data available for the selected filters.',
        'branch' => 'Branch',
        'exact_weight' => 'Exact weight (kg)',
        'ambassadors' => 'Ambassadors',
        'support_in_kind' => 'In-kind support (JOD)',
        'support_financial' => 'Financial support (JOD)',
        'last_requested' => 'Last request date',
        'last_unloaded' => 'Last unloading date',
        'average_delay' => 'Average delay (days)',
        'scheduled_count' => 'Requests',
        'unloaded_count' => 'Completed unloadings',
        'unknown_branch' => 'Unknown branch',
    ],
    'actions' => [
        'view_details' => 'View details',
        'back_to_reports' => 'Back to reports',
    ],
    'breadcrumbs' => [
        'dashboard' => 'Dashboard',
        'reports' => 'Green Wheelz reports',
    ],
];
