<?php

return [
    'definitions' => [
        'volunteers' => [
            'label' => 'الحاجة للمتطوعين',
            'default_roles' => ['volunteer_coordinator'],
        ],
        'official_correspondence' => [
            'label' => 'الحاجة للمخاطبة الرسمية',
            'default_roles' => ['branch_coordinator'],
        ],
        'media_coverage' => [
            'label' => 'الحاجة لتغطية إعلامية',
            'default_roles' => ['communication_head'],
        ],
        'supplies' => [
            'label' => 'الحاجة للمستلزمات',
            'default_roles' => ['supervisor'],
        ],
        'official_sponsorship' => [
            'label' => 'الحاجة لرعاية رسمية',
            'default_roles' => ['branch_coordinator'],
        ],
        'external_partners' => [
            'label' => 'الحاجة لشركاء خارجيين',
            'default_roles' => ['branch_coordinator'],
        ],
        'ceremony_agenda' => [
            'label' => 'الحاجة لوجود أجندة حفل',
            'default_roles' => [],
        ],
        'transport' => [
            'label' => 'الحاجة لتأمين مواصلات',
            'default_roles' => ['transport_officer', 'movement_manager'],
        ],
        'maintenance_workers' => [
            'label' => 'الحاجة لعمال صيانة بالموقع',
            'default_roles' => ['administrative_unit_manager'],
        ],
        'gifts_shields' => [
            'label' => 'الحاجة لهدايا ودروع',
            'default_roles' => ['branch_coordinator'],
        ],
        'programs_participation' => [
            'label' => 'الحاجة لمشاركة البرامج',
            'default_roles' => ['supervisor'],
        ],
        'certificates_thanks' => [
            'label' => 'الحاجة لشهادات وكتب شكر',
            'default_roles' => ['branch_coordinator'],
        ],
        'invitations' => [
            'label' => 'الحاجة إلى بطاقات دعوة',
            'default_roles' => ['communication_head'],
        ],
    ],

    'decision_matrix' => [
        'volunteers' => ['roles' => ['volunteer_coordinator']],
        'official_correspondence' => ['roles' => ['branch_coordinator']],
        'media_coverage' => ['roles' => ['communication_head']],
        'supplies' => ['roles' => ['supervisor']],
        'official_sponsorship' => ['roles' => ['branch_coordinator']],
        'external_partners' => ['roles' => ['branch_coordinator']],
        'transport' => ['roles' => ['transport_officer', 'movement_manager']],
        'maintenance_workers' => ['roles' => ['administrative_unit_manager']],
        'gifts_shields' => ['roles' => ['branch_coordinator']],
        'programs_participation' => ['roles' => ['supervisor']],
        'certificates_thanks' => ['roles' => ['branch_coordinator']],
        'invitations' => ['roles' => ['communication_head']],
    ],
];
