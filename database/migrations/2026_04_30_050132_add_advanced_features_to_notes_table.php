<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // Rich text content
            $table->json('content_json')->nullable()->after('content');
            $table->boolean('is_rich_text')->default(false)->after('content_json');
            
            // Note anchoring to paragraphs
            $table->integer('paragraph_index')->nullable()->after('article_id');
            $table->string('paragraph_id')->nullable()->after('paragraph_index');
            $table->integer('start_offset')->nullable()->after('paragraph_id');
            $table->integer('end_offset')->nullable()->after('start_offset');
            
            // Categories and tags
            $table->string('category')->nullable()->after('type');
            $table->json('tags')->nullable()->after('category');
            
            // Sync and device info
            $table->string('device_id')->nullable()->after('tags');
            $table->timestamp('last_synced_at')->nullable()->after('device_id');
            $table->string('sync_status')->default('synced')->after('last_synced_at');
            
            // Search optimization
            $table->text('search_vector')->nullable()->after('sync_status');
            
            // Additional indexes
            $table->index('category');
            $table->index('device_id');
            $table->index('sync_status');
            $table->index('paragraph_index');
            $table->index('paragraph_id');
            
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'content', 'search_vector']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropFullText(['title', 'content', 'search_vector']);
            }
            $table->dropColumn([
                'content_json', 'is_rich_text', 'paragraph_index', 'paragraph_id',
                'start_offset', 'end_offset', 'category', 'tags', 'device_id',
                'last_synced_at', 'sync_status', 'search_vector'
            ]);
        });
    }
};
