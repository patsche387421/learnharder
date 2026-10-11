<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. User Stats (Aggregierte Statistiken)
        Schema::create('user_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->bigInteger('total_xp')->default(0);
            $table->integer('trophies')->default(0);
            $table->integer('prestige_level')->default(0);
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->timestamp('last_study_date')->nullable();
            $table->timestamps();
        });

        // 2. Quiz- und Übungs-Verlauf (Quiz Attempts)
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->integer('correct_count')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('score_percentage')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->integer('brainpower_spent')->default(0);
            $table->string('attempt_type')->default('standard'); // standard, challenge, daily, duel
            $table->timestamps();

            $table->index(['user_id', 'topic_id']);
        });

        // 3. Schulen (Schools)
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country', 2)->default('AT');
            $table->string('domain')->nullable();
            $table->timestamps();
        });

        // 4. Schulklassen (Classes)
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // z. B. "4AHIT"
            $table->string('join_code', 10)->unique();
            $table->string('academic_year', 9)->default('2026/2027');
            $table->timestamps();
        });

        // 5. Klassen-Einschreibungen (Class Enrollments)
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();

            $table->unique(['class_id', 'user_id']);
        });

        // 6. Private Teams & Lerngruppen (Teams)
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('tag', 5)->nullable();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('join_code', 10)->nullable()->unique();
            $table->boolean('is_private')->default(true);
            $table->timestamps();
        });

        // 7. Team-Mitglieder (Team Members)
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // leader, member
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['team_id', 'user_id']);
        });

        // 8. Trophäen & Errungenschaften (Trophies)
        Schema::create('trophies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description');
            $table->string('icon_name')->default('trophy');
            $table->integer('bp_reward')->default(0);
            $table->timestamps();
        });

        // 9. Freigeschaltete Trophäen (User Trophies)
        Schema::create('user_trophies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('trophy_id')->constrained('trophies')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->useCurrent();

            $table->unique(['user_id', 'trophy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_trophies');
        Schema::dropIfExists('trophies');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('user_stats');
    }
};
