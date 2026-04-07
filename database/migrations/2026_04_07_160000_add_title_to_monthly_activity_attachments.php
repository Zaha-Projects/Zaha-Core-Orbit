<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activity_attachments', function (Blueprint $table) {
            $table->string('title')->nullable()->after('file_type');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activity_attachments', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
