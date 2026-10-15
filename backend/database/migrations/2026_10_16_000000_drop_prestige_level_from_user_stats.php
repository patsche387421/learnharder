<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prestige rechnet der Server aus total_xp (App\Support\Progression), die Spalte wird nicht mehr gelesen.
     */
    public function up(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->dropColumn('prestige_level');
        });
    }

    public function down(): void
    {
        Schema::table('user_stats', function (Blueprint $table) {
            $table->integer('prestige_level')->default(0);
        });
    }
};
