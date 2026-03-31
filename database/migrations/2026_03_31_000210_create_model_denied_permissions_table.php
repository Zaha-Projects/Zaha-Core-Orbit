<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelDeniedPermissionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('model_denied_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_denied_permissions_model_id_model_type_index');

            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_denied_permissions_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_denied_permissions');
    }
}
