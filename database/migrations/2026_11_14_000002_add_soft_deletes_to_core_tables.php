<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'appointments',
            'dentists',
            'treatment_records',
            'attachments',
            'dependents',
            'users',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->timestamp('deleted_at')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'deletion_reason')) {
                        $table->text('deletion_reason')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = [
            'appointments',
            'dentists',
            'treatment_records',
            'attachments',
            'dependents',
            'users',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropColumn('deleted_at');
                    }
                    if (Schema::hasColumn($tableName, 'deletion_reason')) {
                        $table->dropColumn('deletion_reason');
                    }
                });
            }
        }
    }
};
