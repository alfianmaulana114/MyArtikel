<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->index(['user_id', 'processing_status'], 'articles_user_status_index');
            $table->index(['user_id', 'created_at'], 'articles_user_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_user_status_index');
            $table->dropIndex('articles_user_created_index');
        });
    }
};
