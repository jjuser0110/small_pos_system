<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // each user's own language (en / ms / zh_CN) and light / dark mode
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('shift_end');
            $table->string('theme', 10)->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['locale', 'theme']));
    }
};
