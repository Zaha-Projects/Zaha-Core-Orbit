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
