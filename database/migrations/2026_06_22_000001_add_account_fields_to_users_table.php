<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Growing region as a USDA hardiness zone ("8b"); helps the writer
            // ground a gardener's notes in the right climate.
            $table->string('region', 8)->nullable()->after('name');
            // Birth year only — a coarse, optional detail, never a full birthdate.
            $table->unsignedSmallInteger('birth_year')->nullable()->after('region');
            // Holds a requested new email until the gardener confirms it from the
            // new address; the live `email` only changes once they click through.
            $table->string('pending_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['region', 'birth_year', 'pending_email']);
        });
    }
};
