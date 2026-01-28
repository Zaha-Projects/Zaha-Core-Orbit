<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePermissionTables extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        if (empty($tableNames)) {
            throw new RuntimeException('Error: permission.table_names not set in config/permission.php.');
        }

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('module')->nullable();
            $table->string('action')->nullable();
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $teamForeignKey) {
            $table->bigIncrements('id');
            if ($teams) {
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('guard_name');
            $table->timestamps();
            $table->unique($teams ? [$teamForeignKey, 'name', 'guard_name'] : ['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $teams, $teamForeignKey) {
            $table->unsignedBigInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'model_has_permissions_team_foreign_key_index');
                $table->primary([$teamForeignKey, 'permission_id', $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $teams, $teamForeignKey) {
            $table->unsignedBigInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            if ($teams) {
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'model_has_roles_team_foreign_key_index');
                $table->primary([$teamForeignKey, 'role_id', $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            } else {
                $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store'))
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new RuntimeException('Error: permission.table_names not set in config/permission.php.');
        }

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
}
