<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('Active')->after('change');
            $table->integer('voided_by')->nullable()->after('status');
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('voided_reason')->nullable()->after('voided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('voided_by');
            $table->dropColumn('voided_at');
            $table->dropColumn('voided_reason');
        });
    }
};
