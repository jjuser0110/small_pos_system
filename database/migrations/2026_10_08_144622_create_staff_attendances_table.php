<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('work_date');
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();   // set by the Closing button
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);    // one record per staff per day
            $table->index(['branch_id', 'work_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_attendances');
    }
};
