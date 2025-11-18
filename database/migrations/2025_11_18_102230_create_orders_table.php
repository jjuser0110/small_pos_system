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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->integer('company_id');
            $table->integer('user_id');
            $table->string('order_no');
            $table->date('order_date');
            $table->double('total_product')->default(0);
            $table->double('total_item')->default(0);
            $table->double('total_price')->default(0);
            $table->double('tax_amount')->default(0);
            $table->double('final_total')->default(0);
            $table->string('payment_method')->nullable();
            $table->double('amount_received')->default(0);
            $table->double('change')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
