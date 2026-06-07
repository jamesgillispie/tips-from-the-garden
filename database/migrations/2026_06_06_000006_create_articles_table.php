<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            // Twill module defaults
            $table->id();
            $table->softDeletes();
            $table->timestamps();
            $table->boolean('published')->default(true);

            // Domain fields
            $table->string('title');
            $table->longText('body_md');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('article_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('writer')->nullable(); // driver + model used
            $table->string('download_token', 64)->unique();
            $table->timestamp('delivered_at')->nullable();
        });

        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('articles');
    }
};
