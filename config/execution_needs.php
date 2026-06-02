<?php

return [
    'center_availability' => [
        'show_field' => true,
        'default' => 'not_available',
        'forced_not_available' => [
            'official_correspondence',
            'certificates',
            'thanks_letters',
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
