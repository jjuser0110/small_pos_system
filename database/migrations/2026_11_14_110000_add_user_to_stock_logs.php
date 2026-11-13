<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // who caused each stock movement (older rows stay empty)
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_logs', fn (Blueprint $t) => $t->dropColumn('user_id'));
    }
};
