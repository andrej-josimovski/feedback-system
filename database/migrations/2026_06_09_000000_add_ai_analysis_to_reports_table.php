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
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('ai_status', ['pending', 'running', 'completed', 'failed'])->default('pending')->after('status');
            $table->json('ai_analysis')->nullable()->after('ai_status');
            $table->timestamp('ai_started_at')->nullable()->after('ai_analysis');
            $table->timestamp('ai_completed_at')->nullable()->after('ai_started_at');
            $table->text('ai_error')->nullable()->after('ai_completed_at');
            $table->string('ai_model')->nullable()->after('ai_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['ai_status', 'ai_analysis', 'ai_started_at', 'ai_completed_at', 'ai_error', 'ai_model']);
        });
    }
};

