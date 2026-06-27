<?php

return [
    'unified_branch_edit' => [
        'enabled' => true,

        /*
         |-----------------------------------------------------------------
         | Locked fields for unified mandatory agenda activities (branch UI)
         |-----------------------------------------------------------------
         |
         | These fields remain unified from HQ/Khalda and branch users cannot
         | change them when editing a monthly activity generated from a
         | mandatory unified annual agenda event.
         |
         */
        'locked_fields' => [
            'title',
            'activity_date',
            'proposed_date',
            'agenda_event_id',
            'target_group_ids',
        ],
    ],


    'change_requests' => [
        /*
         |-----------------------------------------------------------------
         | Roles allowed to open planning edit forms or request deletion
         |-----------------------------------------------------------------
         |
         | Keep the default limited to the branch relations officer. Add
         | roles here later (for example supervisor or branch_coordinator)
         | if those users should regain the edit/delete-request actions.
         |
         */
        'allowed_roles' => [
            'relations_officer',
        ],
    ],


    'decision_focus_areas' => [
        'basic_info' => 'المعلومات العامة ووصف الفرصة',
        'schedule_location' => 'الموعد والمكان وبيانات التواصل',
        'target_audience' => 'الفئة المستهدفة والحضور المتوقع',
        'execution_needs' => 'احتياجات التنفيذ والتجهيزات',
        'teams_volunteers' => 'الفرق والمتطوعون والتنظيم',
        'external_coordination' => 'الشراكات والرعايات والمخاطبات الرسمية',
        'attachments_followup' => 'المرفقات والمتابعة وما بعد التنفيذ',
    ],

    'agenda_linked_edit' => [
        'enabled' => true,

        /*
         |-----------------------------------------------------------------
         | Locked fields for all agenda-sourced monthly activities
         |-----------------------------------------------------------------
         |
         | Any monthly activity created from an agenda event should keep
         | planning source fields in sync with agenda data.
         |
         */
        'locked_fields' => [
            'title',
            'description',
        ],
    ],
];
