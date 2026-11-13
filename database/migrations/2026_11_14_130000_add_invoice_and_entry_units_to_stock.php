<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('supplier_invoice')->nullable()->after('supplier_id');
        });

        // how the row was keyed in, e.g. "10 Carton at RM 48.00"; quantity /
        // cost_per_unit / balance stay in base units (240 tins at RM 2.00)
        Schema::table('batch_items', function (Blueprint $table) {
            $table->unsignedBigInteger('entry_unit_id')->nullable()->after('product_id');
            $table->string('entry_unit')->nullable()->after('entry_unit_id');
            $table->double('entry_qty')->nullable()->after('entry_unit');
            $table->double('entry_cost')->nullable()->after('entry_qty');
        });
    }

    public function down(): void
    {
        Schema::table('batch_items', fn (Blueprint $t) => $t->dropColumn(['entry_unit_id', 'entry_unit', 'entry_qty', 'entry_cost']));
        Schema::table('batches', fn (Blueprint $t) => $t->dropColumn('supplier_invoice'));
    }
};
