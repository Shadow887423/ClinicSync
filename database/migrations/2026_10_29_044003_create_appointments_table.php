<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 50)->unique();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('dentist_id')->nullable()->constrained('dentists')->onDelete('set null');
            $table->string('service_type');
            $table->date('requested_date');
            $table->time('requested_time');
            $table->date('confirmed_date')->nullable();
            $table->time('confirmed_time')->nullable();
            $table->date('proposed_date')->nullable();
            $table->time('proposed_time')->nullable();
            $table->string('status', 30)->default('pending'); // pending, confirmed, rejected, reschedule_requested, completed, cancelled
            $table->text('patient_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['dentist_id', 'confirmed_date', 'confirmed_time']);
            $table->index(['status', 'requested_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
