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
        Schema::create('batch_items', function (Blueprint $table) {
            $table->id();
            $table->integer('batch_id');
            $table->integer('branch_id');
            $table->integer('company_id');
            $table->integer('category_id');
            $table->integer('product_id');
            $table->double('quantity')->default(0);
            $table->double('total_cost')->default(0);
            $table->double('cost_per_unit')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_items');
    }
};
