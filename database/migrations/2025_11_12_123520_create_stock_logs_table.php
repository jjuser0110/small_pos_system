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
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('content_id');
            $table->string('content_type');
            $table->integer('branch_id');
            $table->integer('company_id');
            $table->integer('category_id');
            $table->integer('product_id');
            $table->string('type');
            $table->string('description');
            $table->double('before_stock')->default(0);
            $table->double('quantity')->default(0);
            $table->double('after_stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
