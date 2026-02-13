<?php

return [
    'dashboard' => [
        'title' => 'Supervisor overview',
        'subtitle' => 'Stay on top of volunteer applications that require your decision.',
        'metrics' => [
            'pending' => 'Awaiting your review',
            'awaiting_final' => 'Sent for final approval',
        ],
        'recent' => [
            'title' => 'Latest applications',
            'subtitle' => 'Recent activity across opportunities you supervise.',
            'view_all' => 'Open review queue',
        ],
        'table' => [
            'volunteer' => 'Volunteer',
            'opportunity' => 'Opportunity',
            'status' => 'Status',
            'updated_at' => 'Updated on',
        ],
        'empty' => 'No applications assigned to you yet.',
    ],
    'reviews' => [
        'title' => 'Review queue',
        'subtitle' => 'Approve or request changes on incoming volunteer applications.',
        'headers' => [
            'volunteer' => 'Volunteer',
            'opportunity' => 'Opportunity',
            'status' => 'Status',
            'applied_at' => 'Applied on',
            'actions' => 'Actions',
        ],
        'actions' => [
            'approve' => 'Approve application',
            'reject' => 'Reject application',
        ],
        'default_rejection' => 'Thank you for applying. We hope to collaborate in the future.',
        'empty' => 'Everything is up to date.',
    ],
    'evaluations' => [
        'title' => 'Focal Point performance reviews',
        'subtitle' => 'Complete the branch evaluation forms for your Focal Point team.',
        'actions' => [
            'back' => 'Back to evaluations',
            'start_form' => 'Start evaluation',
            'edit_form' => 'View or update',
            'cancel' => 'Cancel',
        ],
        'statuses' => [
            'submitted' => 'Submitted on :date',
            'pending' => 'Not started',
        ],
        'badges' => [
            'submitted' => 'Completed',
            'pending' => 'Pending',
        ],
        'messages' => [
            'saved' => 'Evaluation submitted successfully.',
        ],
        'empty' => [
            'forms' => 'No Focal Point evaluation forms are configured yet.',
            'liaisons' => 'No branch Focal Points are assigned to you.',
        ],
        'form' => [
            'title' => '“:form” evaluation',
            'subtitle' => 'Provide constructive feedback about Focal Point performance and coordination.',
            'badges' => [
                'liaison' => 'Focal Point review',
            ],
            'last_submitted' => 'Last submitted on :date',
            'submit' => 'Submit evaluation',
            'guidance' => [
                'title' => 'Helpful tips',
                'intro' => 'Focus on coaching-oriented feedback that highlights strengths and opportunities.',
                'focus_coaching' => 'Address collaboration, responsiveness, and follow-up with concrete examples.',
                'capture_examples' => 'Document specific wins or gaps to support future development plans.',
                'review_anytime' => 'You can revisit and update this evaluation after saving.',
            ],
            'options' => [
                'yes' => 'Yes',
                'no' => 'No',
                'choose' => 'Select an option',
            ],
        ],
    ],
    'evaluation_forms' => [
        'title' => 'Branch evaluation forms',
        'subtitle' => 'Design the Focal Point and branch review forms used in :branch.',
        'actions' => [
            'create' => 'New evaluation form',
        ],
        'back' => 'Back to evaluation forms',
        'create_title' => 'Create supervisor evaluation form',
        'manage_description' => 'Configure form details and maintain the questions available to branch managers.',
        'form_settings' => 'Form details',
        'questions_list' => 'Questions in this form',
        'empty' => 'No supervisor-managed evaluation forms are available yet.',
    ],
];
