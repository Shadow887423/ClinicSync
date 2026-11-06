<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointment_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 50); // created, confirmed, rejected, modified, rescheduled, completed, cancelled
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'action']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointment_activity_logs');
    }
};
