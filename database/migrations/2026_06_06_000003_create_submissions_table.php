<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            // Twill module defaults
            $table->id();
            $table->softDeletes();
            $table->timestamps();
            $table->boolean('published')->default(true);

            // Domain fields
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source'); // email | upload
            $table->string('audio_path');
            $table->string('original_filename')->nullable();
            $table->string('status')->default('received')->index();
            $table->text('error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
