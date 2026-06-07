<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pasted-transcript submissions have no audio file.
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('audio_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('audio_path')->nullable(false)->change();
        });
    }
};
