<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only report of users still holding the legacy transport/movement roles that the
 * Movement plan retires in Phase 12 (§14.2 retirement procedure, D3). Performs SELECTs only.
 */
class MovementLegacyRoleHolders extends Command
{
    public const LEGACY_ROLES = ['transport_officer', 'movement_editor', 'movement_viewer'];

    protected $signature = 'movement:legacy-role-holders';

    protected $description = 'List users holding the legacy transport_officer / movement_editor / movement_viewer roles (read-only).';

    public function handle(): int
    {
        $tables = config('permission.table_names');
        $morphKey = config('permission.column_names.model_morph_key', 'model_id');

        $rows = DB::table($tables['model_has_roles'].' as mhr')
            ->join($tables['roles'].' as r', 'r.id', '=', 'mhr.role_id')
            ->leftJoin('users as u', function ($join) use ($morphKey) {
                $join->on('u.id', '=', 'mhr.'.$morphKey)->where('mhr.model_type', '=', \App\Models\User::class);
            })
            ->whereIn('r.name', self::LEGACY_ROLES)
            ->orderBy('r.name')
            ->orderBy('mhr.'.$morphKey)
            ->get(['r.name as role', 'mhr.model_type', 'mhr.'.$morphKey.' as model_id', 'u.name as user_name', 'u.email']);

        $this->table(
            ['Role', 'Count'],
            collect(self::LEGACY_ROLES)->map(fn (string $role) => [$role, $rows->where('role', $role)->count()])->all()
        );

        if ($rows->isEmpty()) {
            $this->info('No users hold the legacy roles.');

            return self::SUCCESS;
        }

        $this->table(
            ['Role', 'Model', 'ID', 'Name', 'Email'],
            $rows->map(fn ($row) => [$row->role, class_basename($row->model_type), $row->model_id, $row->user_name ?? '-', $row->email ?? '-'])->all()
        );

        return self::SUCCESS;
    }
}
