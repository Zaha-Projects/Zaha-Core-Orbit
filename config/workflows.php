<?php

return [
    // A module opts into branch-aware actor resolution by listing only the
    // workflow roles whose assignments are local to the entity branch.
    'branch_scoped_modules' => [
        'monthly_activities' => ['relations_officer', 'supervisor', 'branch_coordinator'],
        'ramadan_iftars' => ['relations_officer', 'supervisor', 'branch_coordinator'],
    ],
];
