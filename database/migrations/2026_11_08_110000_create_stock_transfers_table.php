<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // stock sent from one company's item to another company's item;
        // the receiving company approves before any stock moves
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_branch_id');
            $table->unsignedBigInteger('from_company_id');
            $table->unsignedBigInteger('from_product_id');
            $table->unsignedBigInteger('to_branch_id');
            $table->unsignedBigInteger('to_company_id');
            $table->unsignedBigInteger('to_product_id');
            $table->decimal('quantity', 15, 4);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');   // pending / approved / rejected
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('status_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->unsignedBigInteger('received_batch_id')->nullable(); // stock entry created at the receiver
            $table->timestamps();
            $table->softDeletes();
            $table->index(['to_company_id', 'status']);
            $table->index(['from_company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
