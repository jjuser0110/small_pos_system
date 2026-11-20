<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // who did what, when, from where: every record change plus logins / logouts
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();          // kept even if the user is deleted later
            $table->string('action', 30);                     // created / updated / deleted / restored / login / logout / login_failed / ...
            $table->string('subject_type', 100)->nullable();  // e.g. Product
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();      // e.g. the item name at that time
            $table->json('changes')->nullable();              // {field: [old, new]}
            $table->string('description')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
