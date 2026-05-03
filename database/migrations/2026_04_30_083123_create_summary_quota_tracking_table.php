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
        Schema::create('summary_quota_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('service', 50); // gemini, local
            $table->integer('requests_count')->default(0);
            $table->integer('tokens_used')->default(0);
            $table->date('quota_date'); // Track per day
            $table->json('metadata')->nullable(); // Store additional info
            $table->timestamps();
            
            $table->unique(['user_id', 'service', 'quota_date']);
            $table->index('user_id');
            $table->index('service');
            $table->index('quota_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summary_quota_tracking');
    }
};
