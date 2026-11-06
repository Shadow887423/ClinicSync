<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_phone', 30);
            $table->string('notification_type', 40); // confirmation, reminder, rescheduled, cancellation, rejection, general
            $table->text('message');
            $table->string('status', 30)->default('queued'); // queued, sent, delivered, failed, simulated
            $table->string('gateway', 30)->default('simulated');
            $table->string('gateway_reference')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->text('payload_response')->nullable();
            $table->timestamps();

            $table->index(['recipient_phone', 'notification_type']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_notifications');
    }
};
