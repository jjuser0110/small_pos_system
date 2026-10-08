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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id')->nullable();   // source branch
            $table->unsignedBigInteger('company_id')->nullable();  // source company
            $table->string('type', 20);                            // adjust_in / adjust_out / transfer
            $table->decimal('quantity', 15, 2);
            $table->unsignedBigInteger('target_company_id')->nullable();
            $table->unsignedBigInteger('target_branch_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');      // pending / approved / rejected
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('status_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
