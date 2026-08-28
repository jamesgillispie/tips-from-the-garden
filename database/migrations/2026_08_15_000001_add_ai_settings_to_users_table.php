<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ai_opt_in')->default(false)->after('pending_email');
            $table->timestamp('ai_opted_in_at')->nullable()->after('ai_opt_in');
            $table->timestamp('last_ai_check_in_at')->nullable()->after('ai_opted_in_at');
            $table->json('ai_preferences')->nullable()->after('last_ai_check_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ai_opt_in',
                'ai_opted_in_at',
                'last_ai_check_in_at',
                'ai_preferences',
            ]);
        });
    }
};
