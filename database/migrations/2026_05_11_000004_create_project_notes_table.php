<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outline_id')->nullable()->constrained('project_outlines')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['project_id', 'note_id']);
            $table->index(['project_id', 'outline_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notes');
    }
};
