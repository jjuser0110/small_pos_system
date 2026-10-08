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
        Schema::create('staff_borrows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');            // staff (role 5)
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending / approved / rejected
            $table->unsignedBigInteger('approved_by')->nullable(); // manager who acted
            $table->dateTime('status_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_borrows');
    }
};
