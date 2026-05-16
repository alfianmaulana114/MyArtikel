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
        Schema::dropIfExists('citation_messages');
        Schema::dropIfExists('citation_conversations');
    }

    public function down(): void
    {
        Schema::create('citation_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('citation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citation_conversation_id')->constrained()->onDelete('cascade');
            $table->string('role', 20);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
};
