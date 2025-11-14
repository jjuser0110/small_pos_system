<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->integer('company_id');
            $table->string('batch_no');
            $table->date('batch_date');
            $table->double('total_product')->default(0);
            $table->double('total_item')->default(0);
            $table->double('total_cost')->default(0);
            $table->string('status')->default('Open');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
