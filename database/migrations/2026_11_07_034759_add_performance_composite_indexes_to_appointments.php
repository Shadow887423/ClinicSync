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
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['dentist_id', 'confirmed_date', 'confirmed_time'], 'idx_dentist_confirmed_slot');
            $table->index(['status', 'requested_date'], 'idx_appointment_status_requested');
            $table->index(['patient_id', 'status'], 'idx_patient_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_dentist_confirmed_slot');
            $table->dropIndex('idx_appointment_status_requested');
            $table->dropIndex('idx_patient_status');
        });
    }
};
