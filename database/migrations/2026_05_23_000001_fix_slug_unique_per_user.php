<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop global unique constraint on slug
            $table->dropUnique('articles_slug_unique');
            // Add composite unique per user
            $table->unique(['user_id', 'slug'], 'articles_user_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique('articles_user_slug_unique');
            $table->unique('slug');
        });
    }
};
