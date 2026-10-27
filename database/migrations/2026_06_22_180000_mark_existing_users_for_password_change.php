<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mark all existing non-Super-Admin users as requiring password change
        DB::update(
            "UPDATE users
            SET must_change_password = true
            WHERE id NOT IN (
                SELECT mhr.model_id
                FROM model_has_roles mhr
                JOIN roles r ON mhr.role_id = r.id
                WHERE r.name = 'Super Admin'
                  AND mhr.model_type = 'App\\Models\\User'
            )
            AND deleted_at IS NULL
            AND must_change_password = false"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally reset the flag for all non-Super-Admin users
        DB::update(
            "UPDATE users
            SET must_change_password = false
            WHERE id NOT IN (
                SELECT mhr.model_id
                FROM model_has_roles mhr
                JOIN roles r ON mhr.role_id = r.id
                WHERE r.name = 'Super Admin'
                  AND mhr.model_type = 'App\\Models\\User'
            )
            AND deleted_at IS NULL"
        );
    }
};
