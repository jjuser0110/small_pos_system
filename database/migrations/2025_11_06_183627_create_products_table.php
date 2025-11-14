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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->integer('company_id');
            $table->integer('category_id');
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->string('barcode')->nullable();
            $table->double('selling_price');
            $table->string('uom');
            $table->double('stock_quantity')->default(0);
            $table->integer('is_active')->default(1);
            $table->integer('arrangement')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
