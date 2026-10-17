<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erfolge (E-040, E-041): Katalog und Vergaben ersetzen die leeren Tabellen trophies und user_trophies.
     * Neu anlegen statt umbenennen, weil Postgres Index- und Fremdschlüssel-Namen beim Umbenennen nicht mitnimmt.
     * user_stats.trophies bleibt die Währung.
     */
    public function up(): void
    {
        Schema::dropIfExists('user_trophies');
        Schema::dropIfExists('trophies');

        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description'); // Bedingung in Worten, steht auch auf der Kachel, solange der Erfolg fehlt
            $table->string('icon_name')->default('achievement');
            $table->string('rule'); // App\Enums\AchievementRule (E-042)
            $table->integer('threshold')->default(1);
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->restrictOnDelete(); // nur bei subject_level, NULL = irgendein Fach
            $table->boolean('is_secret')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->integer('trophy_reward')->default(0); // Trophäen bei der Vergabe (E-043)
            $table->timestamps();
        });

        DB::statement("ALTER TABLE achievements ADD CONSTRAINT achievements_rule_check CHECK (rule IN ('lessons_finished', 'perfect_lessons', 'streak', 'level', 'subject_level', 'challenges_finished', 'corrected_mistakes', 'subjects_learned'))");
        DB::statement('ALTER TABLE achievements ADD CONSTRAINT achievements_threshold_check CHECK (threshold >= 1)');
        DB::statement('ALTER TABLE achievements ADD CONSTRAINT achievements_trophy_reward_check CHECK (trophy_reward >= 0)');
        DB::statement("ALTER TABLE achievements ADD CONSTRAINT achievements_subject_check CHECK (subject_id IS NULL OR rule = 'subject_level')");

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Konto löschen löscht alles (E-027)
            $table->foreignUuid('achievement_id')->index()->constrained('achievements')->restrictOnDelete(); // Verlauf bleibt
            $table->timestamp('unlocked_at')->useCurrent();
            $table->integer('trophies_awarded')->default(0); // Wert bei der Vergabe, eine spätere Änderung im CMS verschiebt nichts

            $table->unique(['user_id', 'achievement_id']);
        });

        DB::statement('ALTER TABLE user_achievements ADD CONSTRAINT user_achievements_trophies_awarded_check CHECK (trophies_awarded >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');

        Schema::create('trophies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description');
            $table->string('icon_name')->default('trophy');
            $table->integer('bp_reward')->default(0);
            $table->timestamps();
        });

        Schema::create('user_trophies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('trophy_id')->constrained('trophies')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->useCurrent();

            $table->unique(['user_id', 'trophy_id']);
        });
    }
};
