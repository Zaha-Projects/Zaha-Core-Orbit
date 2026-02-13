<?php

return [
    'hero' => [
        'eyebrow' => 'About the platform',
        'title' => 'Platform numbers & achievements',
        'subtitle' => 'A live snapshot of volunteer impact, open opportunities, and engagement from volunteers and partners over recent months.',
        'summary_badge' => 'Quick summary',
        'summary_title' => 'Unified visitor metrics board',
        'bullets' => [
            'Highlights the most active and in-demand sectors',
            'Shows volunteer growth over the last months',
            'Surfaces branch wins and standout volunteers',
        ],
    ],
    'metrics' => [
        'primary' => [
            'total_opportunities' => [
                'label' => 'Total opportunities',
                'meta' => 'Published opportunities',
            ],
            'partners' => [
                'label' => 'Partner organizations',
                'meta' => 'Approved partners',
            ],
            'active_volunteers' => [
                'label' => 'Active volunteers',
                'meta' => 'Approved participations',
            ],
            'branches' => [
                'label' => 'Branches',
                'meta' => 'Geographic coverage',
            ],
        ],
        'secondary' => [
            'upcoming' => [
                'label' => 'Upcoming opportunities',
                'meta' => 'Live this month',
            ],
            'total_volunteers' => [
                'label' => 'Total volunteers',
                'meta' => 'Registered profiles',
            ],
            'verified_volunteers' => [
                'label' => 'Verified volunteers',
                'meta' => 'Email-verified accounts',
            ],
            'new_volunteers' => [
                'label' => 'Joined in last 6 months',
                'meta' => 'Fresh monthly signups',
            ],
        ],
    ],
    'sectors' => [
        'offered' => [
            'eyebrow' => 'Top sectors with opportunities',
            'title' => 'Sectors publishing the most roles',
            'count' => ':count opportunities',
        ],
        'applied' => [
            'eyebrow' => 'Top sectors volunteers apply to',
            'title' => 'Most requested sectors by volunteers',
            'count' => ':count applications',
        ],
        'empty' => 'No sector data available yet.',
    ],
    'timeline' => [
        'eyebrow' => 'Last 6 months',
        'title' => 'New volunteers per month',
    ],
    'applications' => [
        'eyebrow' => 'Application status',
        'title' => 'Volunteer application flow',
        'total' => 'Total applications',
        'approved' => 'Approved requests',
        'pending' => 'Under review',
        'rejected' => 'Rejected/Cancelled',
        'rate' => 'Approval rate: :rate%',
    ],
    'insights' => [
        'eyebrow' => 'Supporter lens',
        'title' => 'Engagement depth for partners and donors',
        'approval_rate' => [
            'label' => 'Approval rate',
            'meta' => 'Share of accepted requests',
        ],
        'verified_share' => [
            'label' => 'Verified community',
            'meta' => ':count verified accounts',
        ],
        'returning_rate' => [
            'label' => 'Returning volunteers',
            'meta' => 'Applied to multiple roles',
        ],
        'engaged_share' => [
            'label' => 'Applicants share',
            'meta' => 'Volunteers who have applied',
        ],
        'average_per_opportunity' => [
            'label' => 'Applications per role',
            'meta' => 'Average interest level',
        ],
        'average_per_volunteer' => [
            'label' => 'Applications per volunteer',
            'meta' => 'Average activity',
        ],
        'top_skill' => [
            'label' => 'Top skill',
            'meta' => ':count volunteers highlight it',
            'pill' => 'Skills lead',
            'fallback' => 'Skill insight coming soon',
        ],
        'top_nationality' => [
            'label' => 'Leading nationality',
            'meta' => ':count volunteers',
            'pill' => 'Diversity',
            'fallback' => 'More data needed',
        ],
    ],
    'volunteers' => [
        'eyebrow' => 'Volunteer summary',
        'title' => 'Volunteer growth',
        'total' => 'Total volunteers',
        'verified' => 'Email verified',
        'new_last_month' => 'Joined this month',
        'note' => 'Active participation from :branches branches across cities.',
    ],
    'demographics' => [
        'eyebrow' => 'Volunteer mix',
        'title' => 'Gender, nationality, and age insights',
        'gender' => 'Gender split',
        'nationalities' => 'Top nationalities',
        'ages' => 'Age groups',
        'count_label' => ':count volunteers',
    ],
    'skills' => [
        'eyebrow' => 'Skills & fields',
        'title' => 'Volunteer skills and experience areas',
        'top_skills' => 'Most frequent skills',
        'fields' => 'Top volunteering fields',
        'empty' => 'No data yet.',
    ],
    'tiers' => [
        'diamond' => [
            'title' => 'Diamond volunteers',
            'description' => 'Elite volunteers with repeated participations and high approvals.',
        ],
        'gold' => [
            'title' => 'Gold volunteers',
            'description' => 'Committed volunteers with many approved roles.',
        ],
        'silver' => [
            'title' => 'Featured volunteers',
            'description' => 'Active volunteers early in their journey.',
        ],
    ],
    'branches' => [
        'best_branch' => [
            'eyebrow' => 'Top branch',
            'title' => 'Most engaging with volunteers',
            'volunteers' => ':count volunteers',
            'opportunities' => ':count opportunities',
        ],
        'growing_branch' => [
            'eyebrow' => 'Fastest growing',
            'title' => 'Branch publishing the most roles',
            'opportunities' => ':count opportunities',
            'upcoming' => ':count upcoming',
        ],
        'top_volunteer' => [
            'eyebrow' => 'Top-rated volunteer',
            'title' => 'Most accepted recently',
            'approvals' => ':count approvals this month',
            'badge' => 'Featured volunteer',
        ],
        'no_volunteers' => 'No standout volunteers yet.',
        'empty' => 'No branch data is available right now.',
    ],
    'branch_cards' => [
        'eyebrow' => 'Branch achievements',
        'title' => 'Branch performance in publishing and service',
        'main_branch' => 'Main branch',
        'opportunities' => ':count posted opportunities',
        'upcoming' => ':count upcoming events',
        'volunteers' => ':count approved volunteers',
        'empty' => 'No branch data available at the moment.',
    ],
];
