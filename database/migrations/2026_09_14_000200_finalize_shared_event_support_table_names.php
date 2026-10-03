<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the generalized historical tables in place. Schema rename keeps every
     * row, primary key, index, and foreign-key relationship attached to its table.
     */
    public function up(): void
    {
        Schema::rename('monthly_activity_team', 'execution_team_members');
        Schema::rename('monthly_activity_supplies', 'event_supplies');
    }

    /**
     * Reverse names only; no historical data is copied or recreated.
     */
    public function down(): void
    {
        Schema::rename('event_supplies', 'monthly_activity_supplies');
        Schema::rename('execution_team_members', 'monthly_activity_team');
    }
};
