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
        // Add full-text search indexes to articles table
        if (Schema::hasTable('articles')) {
            if (! Schema::hasColumn('articles', 'search_vector')) {
                Schema::table('articles', function (Blueprint $table) {
                    $table->text('search_vector')->nullable()->after('content');
                });
            }
        }

        // Add full-text search indexes to notes table
        if (Schema::hasTable('notes')) {
            if (! Schema::hasColumn('notes', 'search_vector')) {
                Schema::table('notes', function (Blueprint $table) {
                    $table->text('search_vector')->nullable()->after('content');
                });
            }
        }

        // Create search history table
        if (! Schema::hasTable('search_history')) {
            Schema::create('search_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('query', 500);
                $table->json('filters')->nullable();
                $table->integer('results_count')->default(0);
                $table->boolean('clicked_result')->default(false);
                $table->string('session_id', 100)->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index('query');
                $table->index('session_id');
            });
        }

        // Create search analytics table
        if (! Schema::hasTable('search_analytics')) {
            Schema::create('search_analytics', function (Blueprint $table) {
                $table->id();
                $table->string('query_hash', 64)->unique();
                $table->string('query', 500);
                $table->json('keywords')->nullable();
                $table->integer('search_count')->default(0);
                $table->integer('click_count')->default(0);
                $table->float('avg_click_position')->default(0);
                $table->json('related_queries')->nullable();
                $table->timestamps();

                $table->index('query_hash');
                $table->index('search_count');
            });
        }

        // Create search suggestions table
        if (! Schema::hasTable('search_suggestions')) {
            Schema::create('search_suggestions', function (Blueprint $table) {
                $table->id();
                $table->string('suggestion', 255);
                $table->string('type', 50)->default('query'); // 'query', 'tag', 'content'
                $table->integer('popularity')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('suggestion');
                $table->index(['type', 'popularity']);
                $table->index('popularity');
            });
        }

        // Create article search index (PostgreSQL specific)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            try {
                DB::statement('CREATE INDEX articles_search_vector_gin ON articles USING GIN (to_tsvector(\'english\', search_vector))');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('CREATE INDEX notes_search_vector_gin ON notes USING GIN (to_tsvector(\'english\', search_vector))');
            } catch (Throwable $e) {
            }
        } elseif ($driver !== 'sqlite') {
            // MySQL full-text indexes
            try {
                DB::statement('ALTER TABLE articles ADD FULLTEXT INDEX articles_fulltext (title, content, excerpt)');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE notes ADD FULLTEXT INDEX notes_fulltext (content)');
            } catch (Throwable $e) {
            }
        }

        // Populate initial search vectors
        $this->populateSearchVectors();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_suggestions');
        Schema::dropIfExists('search_analytics');
        Schema::dropIfExists('search_history');

        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'search_vector')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('search_vector');
            });
        }

        if (Schema::hasTable('notes') && Schema::hasColumn('notes', 'search_vector')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->dropColumn('search_vector');
            });
        }
    }

    /**
     * Populate search vectors for existing data
     */
    private function populateSearchVectors(): void
    {
        // Populate articles search vectors
        DB::table('articles')->orderBy('id')->chunk(100, function ($articles) {
            foreach ($articles as $article) {
                $searchVector = $this->generateSearchVector($article->title, $article->content, $article->excerpt);
                DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['search_vector' => $searchVector]);
            }
        });

        // Populate notes search vectors
        DB::table('notes')->orderBy('id')->chunk(100, function ($notes) {
            foreach ($notes as $note) {
                $searchVector = $this->generateSearchVector('', $note->content, '');
                DB::table('notes')
                    ->where('id', $note->id)
                    ->update(['search_vector' => $searchVector]);
            }
        });
    }

    /**
     * Generate search vector from content
     */
    private function generateSearchVector(string $title, string $content, string $excerpt): string
    {
        $parts = [];

        if ($title) {
            $parts[] = $title;
        }

        if ($content) {
            // Limit content to prevent overly long search vectors
            $parts[] = substr(strip_tags($content), 0, 1000);
        }

        if ($excerpt) {
            $parts[] = $excerpt;
        }

        return implode(' ', $parts);
    }
};
