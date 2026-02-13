<?php

return [
    'title' => 'Operations dashboard',
    'subtitle' => 'Monitor opportunities, applications, and volunteer engagement in one place.',
    'stats' => [
        'opportunities_total' => 'Total opportunities',
        'opportunities_published' => 'Published opportunities',
        'opportunities_upcoming' => 'Upcoming engagements',
        'applications_pending' => 'Applications awaiting review',
        'applications_approved' => 'Approved applications',
        'volunteers_total' => 'Registered volunteers',
        'volunteers_active' => 'Active volunteers',
        'certificates_issued' => 'Certificates issued',
        'blacklist_active' => 'Suspended accounts',
    ],
    'breakdown' => [
        'heading' => 'Application status overview',
        'subtitle' => 'Distribution of all volunteer applications across workflow stages.',
        'statuses' => [
            'pending' => 'Pending',
            'under_review' => 'Under review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'waitlisted' => 'Waitlisted',
            'cancelled' => 'Cancelled',
        ],
    ],
    'table' => [
        'status' => 'Status',
        'count' => 'Count',
        'progress' => 'Progress',
        'opportunity' => 'Opportunity',
        'organization' => 'Organization',
        'starts_at' => 'Starts at',
        'volunteers_needed' => 'Volunteers needed',
        'volunteer' => 'Volunteer',
        'submitted_at' => 'Submitted at',
         'not_available' => 'Not available',
    ],
    'recent' => [
        'opportunities' => 'Latest opportunities',
        'applications' => 'Latest applications',
        'empty' => 'No records available yet.',
    ],
    'approvals' => [
        'heading' => 'Approvals & follow-up',
        'supervisor_pending' => 'Supervisor decisions pending',
        'liaison_pending' => 'Focal Point approvals pending',
        'drafts_owned' => 'Draft opportunities you created',
        'awaiting_publication' => 'Drafts awaiting publication',
        'empty' => 'No approval actions required at the moment.',
    ],
    'reminders' => [
        'heading' => 'Reminders to send',
        'empty' => 'No reminders due in the next :days days.',
        'days_before' => 'Reminder :days days before start',
    ],
    'evaluations' => [
        'heading' => 'Evaluation coverage',
        'supervisor' => 'Supervisor reviews',
        'volunteer' => 'Volunteer feedback',
        'average_score' => 'Average supervisor score',
    ],
    'levels' => [
        'heading' => 'Volunteer levels & benefits',
        'assigned' => 'Level assignments',
        'unassigned' => 'Volunteers without a level',
        'points' => 'Points range: :min – :max',
        'empty' => 'No levels have been configured yet.',
    ],
    'features' => [
        'heading' => 'Platform coverage',
        'items' => [
            'announcement' => [
                'title' => 'Opportunity announcements',
                'description' => 'Public portal with shareable cards detailing titles, hours, schedules, and conditions.',
            ],
            'roles' => [
                'title' => 'Defined operational roles',
                'description' => 'Branch Focal Points and supervisors manage opportunities with granular permissions.',
            ],
            'opportunity_data' => [
                'title' => 'Structured opportunity data',
                'description' => 'Captures location, skills, languages, capacity, and submission deadlines.',
            ],
            'registration_rules' => [
                'title' => 'Volunteer eligibility checks',
                'description' => 'Age, gender, guardian consent, and vehicle availability are enforced on application.',
            ],
            'supervisor_flow' => [
                'title' => 'Supervisor approval flow',
                'description' => 'Supervisors and unit heads review, accept, or reject volunteer submissions.',
            ],
            'volunteer_profile' => [
                'title' => 'Volunteer profile records',
                'description' => 'Each volunteer maintains a detailed profile and participation history.',
            ],
            'acceptance_commitment' => [
                'title' => 'Commitment & consent handling',
                'description' => 'Tracks pledge signatures, photography consent, and reminder notifications.',
            ],
            'evaluations' => [
                'title' => 'Two-way evaluations',
                'description' => 'Supervisors record performance notes while volunteers rate opportunities.',
            ],
            'levels' => [
                'title' => 'Levels and incentives',
                'description' => 'Multi-tier volunteer levels with configurable point thresholds and benefits.',
            ],
            'certificates' => [
                'title' => 'Certificate issuance',
                'description' => 'Certificates from Zaha and Nahnu acknowledge approved volunteer service.',
            ],
            'post_evaluation' => [
                'title' => 'Post-engagement communication',
                'description' => 'Point transactions and hours feed into thank-you messages and rewards.',
            ],
            'blacklist' => [
                'title' => 'Blacklist governance',
                'description' => 'Active blacklist entries suspend accounts and trigger notifications.',
            ],
        ],
    ],
];
