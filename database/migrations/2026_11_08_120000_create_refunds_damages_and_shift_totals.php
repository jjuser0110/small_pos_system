<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // which shift an order was sold in (refunds are limited to the current shift)
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_closing_id')->nullable()->after('user_id');
            $table->index('shift_closing_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->double('refunded_quantity')->default(0)->after('quantity');
        });

        // per-shift money that is not a sale
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->double('total_item_count')->default(0)->after('total_order_count');
            $table->double('refund_amount')->default(0)->after('total_order_amount');
            $table->double('advance_amount')->default(0)->after('refund_amount');
        });

        // counter refund request; branch manager (or superadmin) approves
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shift_closing_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('company_id');
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');   // pending / approved / rejected
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('status_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['branch_id', 'status']);
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_id');
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('product_id');
            $table->double('quantity');
            $table->decimal('single_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->index('refund_id');
            $table->index('order_item_id');
        });

        // damaged goods; staff / branch manager report, superadmin approves (removes stock)
        Schema::create('damages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('shift_closing_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('status_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
        });

        // an advance is cut from the sales of the shift it was asked in
        Schema::table('staff_borrows', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_closing_id')->nullable()->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('staff_borrows', fn (Blueprint $t) => $t->dropColumn('shift_closing_id'));
        Schema::dropIfExists('damages');
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::table('shift_closings', fn (Blueprint $t) => $t->dropColumn(['total_item_count', 'refund_amount', 'advance_amount']));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('refunded_quantity'));
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['shift_closing_id']);
            $table->dropColumn('shift_closing_id');
        });
    }
};
