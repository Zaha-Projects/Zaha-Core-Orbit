<?php

/*
 * Movement module configuration (docs/movement-module-implementation-plan.md §9.3).
 * Keys are added by the phase that first reads them.
 */
return [
    // Business timezone for entering and displaying movement times. Storage stays UTC
    // (config('app.timezone')), which is intentionally not changed globally.
    'timezone' => env('MOVEMENT_TIMEZONE', 'Asia/Amman'),

    'requests' => [
        // Indicative availability window on the request form (Revision 5):
        // [required_arrival − hours, required_arrival). Indicative only; the manager assigns resources.
        'indicative_window_hours' => (int) env('MOVEMENT_REQUEST_INDICATIVE_WINDOW_HOURS', 3),
    ],

    'legacy' => [
        // Explicit "disaster-recovery backup verified" switch for legacy-table drop
        // migrations: they refuse to drop a table that still contains rows unless true.
        // Strict parsing: only true/1/yes/on enable it; anything else (including "no", "off") stays false.
        'allow_drop_with_rows' => filter_var(env('MOVEMENT_ALLOW_LEGACY_DROP', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
