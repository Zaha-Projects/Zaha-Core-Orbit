<?php

/*
 * Movement module configuration (docs/movement-module-implementation-plan.md §9.3).
 * Keys are added by the phase that first reads them; Phase 1 needs only these two.
 */
return [
    // Business timezone for entering and displaying movement times. Storage stays UTC
    // (config('app.timezone')), which is intentionally not changed globally.
    'timezone' => env('MOVEMENT_TIMEZONE', 'Asia/Amman'),

    'legacy' => [
        // Explicit "disaster-recovery backup verified" switch for legacy-table drop
        // migrations: they refuse to drop a table that still contains rows unless true.
        // Strict parsing: only true/1/yes/on enable it; anything else (including "no", "off") stays false.
        'allow_drop_with_rows' => filter_var(env('MOVEMENT_ALLOW_LEGACY_DROP', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
