<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Phase 4 capture gap (BZ-14): the Bazaar close action recorded no closure time or actor.
 * Additive and nullable: existing completed bazaars keep NULL (closure time unknown).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bazaars', function (Blueprint $table) {
            if (! Schema::hasColumn('bazaars', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('verification_note');
            }
            if (! Schema::hasColumn('bazaars', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bazaars', function (Blueprint $table) {
            if (Schema::hasColumn('bazaars', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }
            if (Schema::hasColumn('bazaars', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
        });
    }
};
