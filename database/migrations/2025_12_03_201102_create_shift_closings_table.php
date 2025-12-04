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
        Schema::create('shift_closings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->double('total_order_count')->default(0);
            $table->double('total_order_amount')->default(0);
            $table->datetime('first_sale_time')->nullable();
            $table->datetime('closing_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_closings');
    }
};
