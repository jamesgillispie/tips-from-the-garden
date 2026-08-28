<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_ai_assisted')->default(false)->after('writer');
            $table->string('ai_model')->nullable()->after('is_ai_assisted');
        });

        // Every article predating this migration came through WriteArticle.
        // Preserve that provenance instead of making old public entries look
        // human-only merely because the columns did not exist yet.
        DB::table('articles')->update([
            'is_ai_assisted' => true,
            'ai_model' => DB::raw('writer'),
        ]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['is_ai_assisted', 'ai_model']);
        });
    }
};
