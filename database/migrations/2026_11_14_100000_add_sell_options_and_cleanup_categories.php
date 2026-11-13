<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ways to sell an item other than its base unit, e.g. for "Cola" (base = tin):
        //   Carton   = 24 tins for RM50
        //   3 for 10 =  3 tins for RM10
        // Stock is always counted in the base unit.
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->decimal('base_qty', 15, 4);   // how many base units this option is
            $table->decimal('price', 15, 2);
            $table->string('barcode')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('product_id');
            $table->index('barcode');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_id')->nullable()->after('product_id');
        });

        // what was sold: quantity is in sale units (e.g. 2 cartons); base_qty says
        // how many base units one sale unit is (24), so stock moved = 2 x 24
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_id')->nullable()->after('product_id');
            $table->string('unit_name')->nullable()->after('product_unit_id');
            $table->double('base_qty')->default(1)->after('unit_name');
        });

        // every item now tracks stock and sells at a fixed price
        DB::table('categories')->update(['has_stock' => 1, 'special' => 0]);
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn(['product_unit_id', 'unit_name', 'base_qty']));
        Schema::table('carts', fn (Blueprint $t) => $t->dropColumn('product_unit_id'));
        Schema::dropIfExists('product_units');
    }
};
