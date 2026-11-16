<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // one transfer (with its own transfer no) now carries many item lines:
        // company A item -> company B item x quantity
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('from_product_id');
            $table->unsignedBigInteger('to_product_id');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
            $table->index('stock_transfer_id');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('transfer_no', 50)->nullable()->unique()->after('id');
            // the item lines now live in stock_transfer_items; kept for old records
            $table->unsignedBigInteger('from_product_id')->nullable()->change();
            $table->unsignedBigInteger('to_product_id')->nullable()->change();
            $table->decimal('quantity', 15, 4)->nullable()->change();
        });

        // every old single-item transfer becomes a transfer with one line
        foreach (DB::table('stock_transfers')->orderBy('id')->get() as $t) {
            DB::table('stock_transfers')->where('id', $t->id)
                ->update(['transfer_no' => 'TRF' . str_pad($t->id, 5, '0', STR_PAD_LEFT)]);
            if ($t->from_product_id && $t->to_product_id) {
                DB::table('stock_transfer_items')->insert([
                    'stock_transfer_id' => $t->id,
                    'from_product_id'   => $t->from_product_id,
                    'to_product_id'     => $t->to_product_id,
                    'quantity'          => $t->quantity ?? 0,
                    'created_at'        => $t->created_at,
                    'updated_at'        => $t->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropUnique(['transfer_no']);
            $table->dropColumn('transfer_no');
        });
        Schema::dropIfExists('stock_transfer_items');
    }
};
