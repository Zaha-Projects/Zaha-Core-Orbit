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
            'owner_department_id',
            'title',
            'activity_date',
            'proposed_date',
            'branch_id',
            'agenda_event_id',
            'planning_attachment',
            'responsible_entities',
            'target_group_ids',
            'partner_department_ids',
        ],
    ],
];
