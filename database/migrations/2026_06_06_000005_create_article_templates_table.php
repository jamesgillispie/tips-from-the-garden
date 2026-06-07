<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_templates', function (Blueprint $table) {
            // Twill module defaults
            $table->id();
            $table->softDeletes();
            $table->timestamps();
            $table->boolean('published')->default(false);

            // Domain fields
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('structure_prompt');
            $table->longText('example_skeleton')->nullable();
        });

        Schema::create('article_template_revisions', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->foreignId('article_template_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_template_revisions');
        Schema::dropIfExists('article_templates');
    }
};
