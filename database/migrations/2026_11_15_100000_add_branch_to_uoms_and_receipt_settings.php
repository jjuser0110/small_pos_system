<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // each branch keeps its own units; existing ones stay shared (no branch)
        Schema::table('uoms', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            $table->index('branch_id');
        });

        // one receipt header/footer per branch; the existing one (no branch) is
        // the default for branches that have not set their own
        Schema::table('receipt_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            $table->unique('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_settings', function (Blueprint $table) {
            $table->dropUnique(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('uoms', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
