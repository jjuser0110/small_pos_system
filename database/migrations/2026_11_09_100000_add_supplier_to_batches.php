<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // the supplier is chosen when stock comes in, not on the product
        // (products.supplier_id is kept for old data but no longer used)
        Schema::table('batches', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('company_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
