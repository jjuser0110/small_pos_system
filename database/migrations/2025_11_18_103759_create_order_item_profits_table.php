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
        Schema::create('order_item_profits', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('order_item_id');
            $table->integer('branch_id');
            $table->integer('company_id');
            $table->integer('category_id');
            $table->integer('product_id');
            $table->integer('batch_id');
            $table->integer('batch_item_id');
            $table->double('cost_price');
            $table->double('selling_price');
            $table->double('earning');
            $table->double('quantity');
            $table->double('total_cost_price');
            $table->double('total_selling_price');
            $table->double('total_earning');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_profits');
    }
};
