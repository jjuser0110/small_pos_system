<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Only add if the column doesn't already exist
            if (!Schema::hasColumn('carts', 'addons')) {
                $table->json('addons')->nullable()->after('total_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'addons')) {
                $table->dropColumn('addons');
            }
        });
    }
};