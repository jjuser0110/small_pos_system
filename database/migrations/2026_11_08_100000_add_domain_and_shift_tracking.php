<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // each company is reached on its own domain; staff may only log in there
        Schema::table('companies', function (Blueprint $table) {
            $table->string('domain', 191)->nullable()->unique()->after('company_code');
        });

        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->string('shift', 2)->nullable()->after('work_date');               // AM / PM
            $table->string('clock_out_reason', 20)->nullable()->after('clock_out');   // closing / logout / auto
        });

        // a shift record now opens at login and knows which shift it is
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->string('shift', 2)->nullable()->after('company_id');
            $table->date('work_date')->nullable()->after('shift');
            $table->index(['user_id', 'closing_time']);
        });
    }

    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'closing_time']);
            $table->dropColumn(['shift', 'work_date']);
        });
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn(['shift', 'clock_out_reason']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['domain']);
            $table->dropColumn('domain');
        });
    }
};
