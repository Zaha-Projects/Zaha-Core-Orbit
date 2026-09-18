<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_gift_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        // Move the existing public values to reference storage without changing
        // gift_type strings or requiring a production seeder for old forms.
        foreach (['gifts' => 'هدايا', 'shields' => 'دروع', 'both' => 'هدايا ودروع'] as $code => $name) {
            DB::table('ramadan_iftar_gift_types')->insert([
                'code' => $code, 'name_ar' => $name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_gift_types');
    }
};
