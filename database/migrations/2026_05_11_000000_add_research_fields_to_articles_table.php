<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'source_type')) {
                $table->string('source_type', 10)->default('url')->after('source_domain');
            }
            if (! Schema::hasColumn('articles', 'file_path')) {
                $table->string('file_path')->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('articles', 'research_title')) {
                $table->string('research_title')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('articles', 'ai_quotation_suggestions')) {
                $table->json('ai_quotation_suggestions')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $columns = [
                'source_type',
                'file_path',
                'research_title',
                'ai_quotation_suggestions',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
