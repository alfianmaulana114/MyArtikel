<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookmarks')) {
            return;
        }

        Schema::table('bookmarks', function (Blueprint $table) {
            if (!Schema::hasColumn('bookmarks', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('bookmark_categories')->nullOnDelete();
            }

            if (!Schema::hasColumn('bookmarks', 'tags')) {
                $table->json('tags')->nullable();
            }

            if (!Schema::hasColumn('bookmarks', 'priority')) {
                $table->integer('priority')->default(0);
            }

            if (!Schema::hasColumn('bookmarks', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false);
            }

            if (!Schema::hasColumn('bookmarks', 'is_archived')) {
                $table->boolean('is_archived')->default(false);
            }

            if (!Schema::hasColumn('bookmarks', 'reminder_at')) {
                $table->timestamp('reminder_at')->nullable();
            }

            if (!Schema::hasColumn('bookmarks', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            if (!Schema::hasColumn('bookmarks', 'read_count')) {
                $table->integer('read_count')->default(0);
            }

            if (!Schema::hasColumn('bookmarks', 'position')) {
                $table->integer('position')->default(0);
            }

            if (!Schema::hasColumn('bookmarks', 'source_device')) {
                $table->string('source_device', 50)->nullable();
            }

            if (!Schema::hasColumn('bookmarks', 'source_browser')) {
                $table->string('source_browser', 50)->nullable();
            }

            if (!Schema::hasColumn('bookmarks', 'created_ip')) {
                $table->ipAddress('created_ip')->nullable();
            }
        });

        if (
            Schema::hasColumn('bookmarks', 'category') &&
            Schema::hasColumn('bookmarks', 'category_id') &&
            Schema::hasTable('bookmark_categories') &&
            DB::connection()->getDriverName() !== 'sqlite'
        ) {
            DB::statement("
                UPDATE bookmarks b
                JOIN bookmark_categories c
                    ON c.user_id = b.user_id AND c.name = 'Favorites'
                SET b.category_id = c.id
                WHERE b.category = 'favorite' AND b.category_id IS NULL
            ");

            DB::statement("
                UPDATE bookmarks b
                JOIN bookmark_categories c
                    ON c.user_id = b.user_id AND c.name = 'Read Later'
                SET b.category_id = c.id
                WHERE b.category = 'read_later' AND b.category_id IS NULL
            ");

            DB::statement("
                UPDATE bookmarks b
                JOIN bookmark_categories c
                    ON c.user_id = b.user_id AND c.name = 'Research'
                SET b.category_id = c.id
                WHERE b.category = 'reference' AND b.category_id IS NULL
            ");
        }

        if (Schema::hasColumn('bookmarks', 'category') && Schema::hasColumn('bookmarks', 'is_favorite') && DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("UPDATE bookmarks SET is_favorite = 1 WHERE category = 'favorite'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('bookmarks')) {
            return;
        }

        Schema::table('bookmarks', function (Blueprint $table) {
            if (Schema::hasColumn('bookmarks', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
            if (Schema::hasColumn('bookmarks', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('bookmarks', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('bookmarks', 'is_favorite')) {
                $table->dropColumn('is_favorite');
            }
            if (Schema::hasColumn('bookmarks', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
            if (Schema::hasColumn('bookmarks', 'reminder_at')) {
                $table->dropColumn('reminder_at');
            }
            if (Schema::hasColumn('bookmarks', 'read_at')) {
                $table->dropColumn('read_at');
            }
            if (Schema::hasColumn('bookmarks', 'read_count')) {
                $table->dropColumn('read_count');
            }
            if (Schema::hasColumn('bookmarks', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('bookmarks', 'source_device')) {
                $table->dropColumn('source_device');
            }
            if (Schema::hasColumn('bookmarks', 'source_browser')) {
                $table->dropColumn('source_browser');
            }
            if (Schema::hasColumn('bookmarks', 'created_ip')) {
                $table->dropColumn('created_ip');
            }
        });
    }
};

