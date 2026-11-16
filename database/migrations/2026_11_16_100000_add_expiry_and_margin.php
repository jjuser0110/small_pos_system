<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Expiry date per stock row (with an index for the expiring-soon list) and margin % per item. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('balance');
            $table->index(['expiry_date', 'balance']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('margin', 8, 2)->nullable()->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('batch_items', function (Blueprint $table) {
            $table->dropIndex(['expiry_date', 'balance']);
            $table->dropColumn('expiry_date');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('margin');
        });
    }
};
