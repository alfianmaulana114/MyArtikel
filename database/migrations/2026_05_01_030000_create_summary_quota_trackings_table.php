<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('summary_quota_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service', 20);
            $table->unsignedInteger('requests_count')->default(0);
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->date('quota_date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'service', 'quota_date'], 'summary_quota_unique');
            $table->index(['service', 'quota_date'], 'summary_quota_service_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summary_quota_trackings');
    }
};

