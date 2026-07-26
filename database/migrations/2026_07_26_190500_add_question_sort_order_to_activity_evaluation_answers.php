<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_evaluation_answers', 'question_sort_order')) {
            Schema::table('activity_evaluation_answers', function (Blueprint $table): void {
                $table->unsignedInteger('question_sort_order')->default(0)->after('weighted_score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_evaluation_answers', 'question_sort_order')) {
            Schema::table('activity_evaluation_answers', function (Blueprint $table): void {
                $table->dropColumn('question_sort_order');
            });
        }
    }
};
