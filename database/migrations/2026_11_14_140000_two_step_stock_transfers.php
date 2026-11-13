<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A transfer now needs two confirmations: the sending company's staff
        // (sent correctly), then the receiving company's staff (received).
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_confirmed_by')->nullable()->after('requested_by');
            $table->dateTime('sender_confirmed_at')->nullable()->after('sender_confirmed_by');
        });

        // old single-step "pending" transfers start at the first step
        DB::table('stock_transfers')->where('status', 'pending')->update(['status' => 'pending_sender']);
    }

    public function down(): void
    {
        DB::table('stock_transfers')->whereIn('status', ['pending_sender', 'pending_receiver'])->update(['status' => 'pending']);
        Schema::table('stock_transfers', fn (Blueprint $t) => $t->dropColumn(['sender_confirmed_by', 'sender_confirmed_at']));
    }
};
