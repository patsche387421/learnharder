<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Den Streak führt nur noch user_stats (G2). Der letzte Lerntag ist ein Kalendertag in Wien, keine Uhrzeit.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_streak');
        });

        Schema::table('user_stats', function (Blueprint $table) {
            $table->date('last_study_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->timestamp('last_study_date')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('current_streak')->default(0);
        });
    }
};
