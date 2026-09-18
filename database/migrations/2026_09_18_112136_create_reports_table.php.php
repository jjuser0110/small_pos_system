<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Location context
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('order_no')->nullable();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('payment_method', 20)->index(); // 'cash' | 'qr'
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->decimal('change', 10, 2)->nullable();
            $table->date('business_date')->index();
            $table->timestamp('paid_at'); // = order's created_at
            $table->timestamps();
            $table->unique(['order_id', 'product_id'], 'reports_order_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};