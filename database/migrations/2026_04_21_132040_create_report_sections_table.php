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
        Schema::create('report_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('theme');
            $table->json('issues')->nullable();      // ["проблем1", "проблем2"]
            $table->json('proposals')->nullable();   // ["предлог1", "предлог2"]
            $table->text('ai_summary')->nullable();
            $table->text('admin_summary')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_sections');
    }
};
