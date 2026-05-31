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
        // Create bookmark categories table
        if (! Schema::hasTable('bookmark_categories')) {
            Schema::create('bookmark_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('name', 100);
                $table->string('color', 7)->default('#3b82f6'); // Hex color
                $table->text('description')->nullable();
                $table->integer('position')->default(0);
                $table->boolean('is_public')->default(false);
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                // Indexes
                $table->index(['user_id', 'position']);
                $table->index('name');

                // Unique constraint per user
                $table->unique(['user_id', 'name']);
            });
        }

        // Create bookmarks table
        if (! Schema::hasTable('bookmarks')) {
            Schema::create('bookmarks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('article_id')->constrained()->onDelete('cascade');
                $table->foreignId('category_id')->nullable()->constrained('bookmark_categories')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->json('tags')->nullable(); // Custom tags for this bookmark
                $table->integer('priority')->default(0); // 0-5 for sorting
                $table->boolean('is_favorite')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->timestamp('reminder_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->integer('read_count')->default(0);
                $table->integer('position')->default(0);
                $table->string('source_device', 50)->nullable(); // Device that created the bookmark
                $table->string('source_browser', 50)->nullable();
                $table->ipAddress('created_ip')->nullable();
                $table->timestamps();

                // Indexes for performance
                $table->index(['user_id', 'article_id']);
                $table->index(['user_id', 'category_id']);
                $table->index(['user_id', 'is_favorite']);
                $table->index(['user_id', 'is_archived']);
                $table->index(['user_id', 'created_at']);
                $table->index(['user_id', 'priority']);
                $table->index('reminder_at');
                $table->index('read_at');

                // Unique constraint to prevent duplicate bookmarks
                $table->unique(['user_id', 'article_id']);
            });
        }

        // Create bookmark sync table for cross-device synchronization
        if (! Schema::hasTable('bookmark_sync')) {
            Schema::create('bookmark_sync', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('device_id', 100); // Unique device identifier
                $table->string('device_name', 100)->nullable();
                $table->string('device_type', 50)->nullable(); // mobile, desktop, tablet
                $table->timestamp('last_sync_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('sync_token', 64)->nullable(); // For secure sync
                $table->timestamps();

                // Indexes
                $table->index(['user_id', 'device_id']);
                $table->index(['user_id', 'is_active']);
                $table->index('last_sync_at');

                // Unique constraint per user-device combination
                $table->unique(['user_id', 'device_id']);
            });
        }

        // Create bookmark analytics table
        if (! Schema::hasTable('bookmark_analytics')) {
            Schema::create('bookmark_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('bookmark_id')->constrained()->onDelete('cascade');
                $table->string('action', 50); // viewed, read, shared, exported, etc.
                $table->json('metadata')->nullable(); // Additional context
                $table->string('device_id', 100)->nullable();
                $table->string('session_id', 100)->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('occurred_at')->useCurrent();

                // Indexes
                $table->index(['user_id', 'bookmark_id']);
                $table->index(['user_id', 'action']);
                $table->index(['user_id', 'occurred_at']);
                $table->index('occurred_at');
            });
        }

        // Create bookmark sharing table (for future sharing feature)
        if (! Schema::hasTable('bookmark_shares')) {
            Schema::create('bookmark_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bookmark_id')->constrained()->onDelete('cascade');
                $table->string('share_token', 64)->unique();
                $table->enum('share_type', ['public', 'private', 'collaborative'])->default('private');
                $table->timestamp('expires_at')->nullable();
                $table->integer('max_views')->nullable();
                $table->integer('view_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Indexes
                $table->index('share_token');
                $table->index(['bookmark_id', 'is_active']);
                $table->index('expires_at');
            });
        }

        // Add bookmark count to articles table
        if (Schema::hasTable('articles') && ! Schema::hasColumn('articles', 'bookmarks_count')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->integer('bookmarks_count')->default(0)->after('view_count');
                $table->index('bookmarks_count');
            });
        }

        // Add bookmark counts to users table
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'bookmarks_count')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('bookmarks_count')->default(0)->after('remember_token');
                $table->integer('bookmark_categories_count')->default(0)->after('bookmarks_count');
                $table->index('bookmarks_count');
            });
        }

        // Create default categories for existing users
        $this->createDefaultCategories();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmark_shares');
        Schema::dropIfExists('bookmark_analytics');
        Schema::dropIfExists('bookmark_sync');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('bookmark_categories');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('bookmarks_count');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bookmarks_count', 'bookmark_categories_count']);
        });
    }

    /**
     * Create default bookmark categories for existing users
     */
    private function createDefaultCategories(): void
    {
        if (! Schema::hasTable('bookmark_categories')) {
            return;
        }

        $users = DB::table('users')->pluck('id');

        foreach ($users as $userId) {
            DB::table('bookmark_categories')->insertOrIgnore([
                [
                    'user_id' => $userId,
                    'name' => 'Favorites',
                    'color' => '#ef4444',
                    'description' => 'Your favorite articles',
                    'position' => 0,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $userId,
                    'name' => 'Read Later',
                    'color' => '#f59e0b',
                    'description' => 'Articles to read later',
                    'position' => 1,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $userId,
                    'name' => 'Research',
                    'color' => '#10b981',
                    'description' => 'Articles for research purposes',
                    'position' => 2,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $userId,
                    'name' => 'Work',
                    'color' => '#3b82f6',
                    'description' => 'Work-related articles',
                    'position' => 3,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Update user's bookmark categories count
            DB::table('users')->where('id', $userId)->update([
                'bookmark_categories_count' => 4,
            ]);
        }
    }
};
