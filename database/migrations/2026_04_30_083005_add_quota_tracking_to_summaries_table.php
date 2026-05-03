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
        Schema::table('summaries', function (Blueprint $table) {
            $table->string('source', 50)->default('manual')->after('type'); // manual, gemini, local
            $table->string('status', 50)->default('completed')->after('source'); // pending, processing, completed, failed
            $table->string('cache_key', 255)->nullable()->after('status');
            $table->timestamp('processing_started_at')->nullable()->after('cache_key');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            $table->integer('processing_time_ms')->nullable()->after('processing_completed_at');
            $table->text('error_message')->nullable()->after('processing_time_ms');
            
            $table->index('source');
            $table->index('status');
            $table->index('cache_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('summaries', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'status', 
                'cache_key',
                'processing_started_at',
                'processing_completed_at',
                'processing_time_ms',
                'error_message'
            ]);
        });
    }
};
