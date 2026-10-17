<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nur der laufende Booster steht am Konto, ohne Kaufverlauf. Jede Antwort merkt sich den Faktor, der beim Antworten lief.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('booster_multiplier', 3, 2)->nullable();
            $table->timestamp('booster_ends_at')->nullable();
        });

        Schema::table('question_answers', function (Blueprint $table) {
            $table->decimal('xp_multiplier', 3, 2)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('question_answers', function (Blueprint $table) {
            $table->dropColumn('xp_multiplier');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['booster_multiplier', 'booster_ends_at']);
        });
    }
};
