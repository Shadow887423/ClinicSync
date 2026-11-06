<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dentists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('specialization')->default('General Dentistry');
            $table->string('license_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->integer('slot_duration_minutes')->default(30);
            $table->string('status')->default('active')->index(); // active, inactive
            $table->text('bio')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dentists');
    }
};
