<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'preferred_language')) {
                $table->string('preferred_language', 5)->default('es')->after('avatar');
            }
        });

        DB::statement("UPDATE users SET preferred_language = 'es' WHERE preferred_language IS NULL OR preferred_language = ''");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'preferred_language')) {
                $table->dropColumn('preferred_language');
            }
        });
    }
};
