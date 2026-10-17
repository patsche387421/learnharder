<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Herausforderung (E-045): Fragen aus mehreren Themen, deshalb ohne topic_id, dafür mit der Liste ihrer Fragen.
     * Dazu der Bonus (zählt in total_xp, nicht ins Fach-Level) und die Trophäe bei 10 von 10. attempt_type kennt nur noch
     * die Arten, die es gibt (App\Enums\AttemptType).
     */
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->uuid('topic_id')->nullable()->change();
            $table->json('question_ids')->nullable(); // Reihenfolge der Fragen einer Herausforderung
            $table->integer('xp_bonus')->default(0);
            $table->integer('trophies_earned')->default(0); // nur die Trophäe der Herausforderung, die der Erfolge steht in user_achievements
        });

        DB::statement("ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_attempt_type_check CHECK (attempt_type IN ('standard', 'challenge'))");
        DB::statement("ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_topic_check CHECK (attempt_type <> 'standard' OR topic_id IS NOT NULL)");
        DB::statement("ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_question_ids_check CHECK (attempt_type <> 'challenge' OR question_ids IS NOT NULL)");
        DB::statement('ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_xp_bonus_check CHECK (xp_bonus >= 0)');
        DB::statement('ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_trophies_earned_check CHECK (trophies_earned >= 0)');
    }

    /**
     * Ohne topic_id gibt es keine Herausforderungen mehr: Sie werden samt Antworten gelöscht, sonst ließe sich die Spalte
     * nicht wieder auf NOT NULL setzen. Nur für die Entwicklung, auf einem Server läuft kein down().
     */
    public function down(): void
    {
        foreach (['attempt_type', 'topic', 'question_ids', 'xp_bonus', 'trophies_earned'] as $check) {
            DB::statement("ALTER TABLE quiz_attempts DROP CONSTRAINT quiz_attempts_{$check}_check");
        }

        DB::table('quiz_attempts')->whereNull('topic_id')->delete();

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['question_ids', 'xp_bonus', 'trophies_earned']);
            $table->uuid('topic_id')->nullable(false)->change();
        });
    }
};
