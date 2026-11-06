<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dentist_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained('dentists')->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday
            $table->string('day_name', 20);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(30);
            $table->string('status')->default('active')->index(); // active, inactive
            $table->timestamps();

            $table->index(['dentist_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dentist_availabilities');
    }
};
