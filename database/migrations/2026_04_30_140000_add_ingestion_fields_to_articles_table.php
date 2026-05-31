<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'source_url')) {
                $table->string('source_url', 2048)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('articles', 'canonical_url')) {
                $table->string('canonical_url', 2048)->nullable()->after('source_url');
            }
            if (! Schema::hasColumn('articles', 'source_domain')) {
                $table->string('source_domain', 255)->nullable()->after('canonical_url');
            }
            if (! Schema::hasColumn('articles', 'fetched_at')) {
                $table->timestamp('fetched_at')->nullable()->after('source_domain');
            }
            if (! Schema::hasColumn('articles', 'processing_status')) {
                $table->enum('processing_status', ['queued', 'fetching', 'extracting', 'ready', 'failed'])
                    ->default('queued')
                    ->after('user_id');
            }
            if (! Schema::hasColumn('articles', 'processing_error')) {
                $table->text('processing_error')->nullable()->after('processing_status');
            }
            if (! Schema::hasColumn('articles', 'content_sanitized')) {
                $table->longText('content_sanitized')->nullable()->after('content');
            }
            if (! Schema::hasColumn('articles', 'text_extracted')) {
                $table->longText('text_extracted')->nullable()->after('content_sanitized');
            }
            if (! Schema::hasColumn('articles', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->after('text_extracted');
            }
            if (! Schema::hasColumn('articles', 'metadata')) {
                $table->json('metadata')->nullable()->after('content_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $columns = [
                'source_url',
                'canonical_url',
                'source_domain',
                'fetched_at',
                'processing_status',
                'processing_error',
                'content_sanitized',
                'text_extracted',
                'content_hash',
                'metadata',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
