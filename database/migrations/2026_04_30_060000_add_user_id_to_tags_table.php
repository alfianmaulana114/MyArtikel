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
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (! Schema::hasColumn('tags', 'type')) {
                $table->string('type', 50)->default('custom'); // 'custom', 'system', 'auto'
            }
            if (! Schema::hasColumn('tags', 'is_auto_generated')) {
                $table->boolean('is_auto_generated')->default(false);
            }
            if (! Schema::hasColumn('tags', 'usage_count')) {
                $table->integer('usage_count')->default(0);
            }
            if (! Schema::hasColumn('tags', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            // Add indexes only if they don't exist
            if (! Schema::hasIndex('tags', 'tags_user_id_type_index')) {
                $table->index(['user_id', 'type']);
            }
            if (! Schema::hasIndex('tags', 'tags_user_id_name_index')) {
                $table->index(['user_id', 'name']);
            }
            if (! Schema::hasIndex('tags', 'tags_usage_count_index')) {
                $table->index('usage_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'type', 'is_auto_generated', 'usage_count', 'metadata']);
        });
    }
};
