<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_outlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('section_type', [
                'title_page', 'abstract', 'introduction', 'literature_review',
                'methodology', 'results', 'discussion', 'conclusion',
                'references', 'appendix', 'custom',
            ])->default('custom');
            $table->longText('content')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('project_outlines')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'position']);
            $table->index(['project_id', 'section_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_outlines');
    }
};
