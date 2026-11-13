<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // what the damaged stock cost (FIFO), saved when the superadmin approves
        Schema::table('damages', function (Blueprint $table) {
            $table->decimal('cost_amount', 15, 2)->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('damages', fn (Blueprint $t) => $t->dropColumn('cost_amount'));
    }
};
