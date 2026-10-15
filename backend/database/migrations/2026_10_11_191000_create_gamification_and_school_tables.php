<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->foreignUuid('topic_id')->constrained('topics')->restrictOnDelete(); // Verlauf bleibt, Themen werden nicht hart gelöscht
            $table->integer('correct_count')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('score_percentage')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->integer('brainpower_spent')->default(0);
            $table->string('attempt_type')->default('standard'); // standard, challenge, daily, duel
            $table->timestamp('finished_at')->nullable(); // gesetzt beim Abschluss, erst dann zählen die EP
            $table->timestamps();

            $table->index(['user_id', 'topic_id']);
        });

        // 2b. Erste Antwort pro Frage und Lerneinheit (Antwort-Log). Grundlage für „EP je Frage einmal pro Tag“ (E-015)
        Schema::create('question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('question_id')->index()->constrained('questions')->restrictOnDelete(); // Verlauf bleibt
            $table->json('selected_option_ids');
            $table->boolean('is_correct');
            $table->integer('xp_awarded')->default(0);
            $table->date('xp_date')->nullable(); // Tag in Europe/Vienna, gesetzt beim Abschluss
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'question_id']);
            $table->index(['user_id', 'question_id', 'xp_date']);
        });

        // 3. Schulen (Schools)
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country', 2)->default('AT');
            $table->timestamps();
        });

        // 4. Mail-Domains einer Schule (für die Registrierung, E-013)
        Schema::create('school_email_domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('domain');
            $table->boolean('allows_registration')->default(false);
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX school_email_domains_domain_lower_unique ON school_email_domains (lower(domain))');

        // 5. Abteilungen (Departments)
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->restrictOnDelete();
            $table->string('code', 10);
            $table->string('name');
            $table->string('color_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });

        // 6. Ausbildungsgänge (Programs): Höhere Lehranstalt, Fachschule, Aufbaulehrgang, Kolleg …
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('code', 10);
            $table->string('name');
            $table->string('form', 30);
            $table->string('unit', 10); // year oder semester
            $table->smallInteger('duration');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['department_id', 'code']);
        });
        DB::statement("ALTER TABLE programs ADD CONSTRAINT programs_form_check CHECK (form IN ('higher_technical', 'technical_school', 'add_on_course', 'college', 'preparatory_course'))");
        DB::statement("ALTER TABLE programs ADD CONSTRAINT programs_unit_check CHECK (unit IN ('year', 'semester'))");
        DB::statement('ALTER TABLE programs ADD CONSTRAINT programs_duration_check CHECK (duration >= 1)');

        // 7. Lehrplan: welches Fach in welchen Abschnitten eines Ausbildungsgangs
        Schema::create('curriculum_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->index()->constrained('subjects')->restrictOnDelete();
            $table->smallInteger('stage_from');
            $table->smallInteger('stage_to');
            $table->timestamps();

            $table->unique(['program_id', 'subject_id']);
        });
        DB::statement('ALTER TABLE curriculum_entries ADD CONSTRAINT curriculum_entries_stage_check CHECK (stage_from >= 1 AND stage_from <= stage_to)');

        // 8. Schuljahre (School Years)
        Schema::create('school_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->restrictOnDelete();
            $table->string('name', 9); // z. B. "2026/27"
            $table->date('starts_on');
            $table->date('second_term_starts_on');
            $table->date('ends_on');
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });
        DB::statement('ALTER TABLE school_years ADD CONSTRAINT school_years_dates_check CHECK (starts_on < second_term_starts_on AND second_term_starts_on < ends_on)');

        // 9. Klassen, je Schuljahr ein Datensatz (School Classes)
        Schema::create('school_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->index()->constrained('programs')->restrictOnDelete();
            $table->foreignUuid('school_year_id')->constrained('school_years')->restrictOnDelete();
            $table->string('name', 20); // z. B. "2AHIT"
            $table->smallInteger('stage'); // Abschnitt zu Beginn des Schuljahres
            $table->foreignId('head_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('previous_class_id')->nullable();
            $table->string('join_code', 10)->nullable()->unique();
            $table->timestamps();

            $table->unique(['school_year_id', 'name']);
            $table->unique(['id', 'school_year_id']); // Ziel des zusammengesetzten Fremdschlüssels in class_enrollments
        });
        DB::statement('ALTER TABLE school_classes ADD CONSTRAINT school_classes_stage_check CHECK (stage >= 1)');
        // Selbstverweis erst nach dem Anlegen, vorher gibt es den Primärschlüssel noch nicht
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreign('previous_class_id')->references('id')->on('school_classes')->nullOnDelete();
        });

        // 10. Klassen-Einschreibungen (Class Enrollments)
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('school_class_id');
            $table->foreignUuid('school_year_id');
            $table->foreignId('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('left_at')->nullable();

            // Klasse und Schuljahr müssen zusammenpassen
            $table->foreign(['school_class_id', 'school_year_id'])
                ->references(['id', 'school_year_id'])->on('school_classes')
                ->restrictOnDelete();
            $table->index(['school_class_id', 'school_year_id']);
        });
        // Höchstens eine aktive Klasse pro Person und Schuljahr, beendete bleiben als Verlauf
        DB::statement('CREATE UNIQUE INDEX class_enrollments_active_unique ON class_enrollments (user_id, school_year_id) WHERE left_at IS NULL');
        DB::statement('ALTER TABLE class_enrollments ADD CONSTRAINT class_enrollments_left_check CHECK (left_at IS NULL OR left_at >= enrolled_at)');

        // 11. Wer unterrichtet welches Fach in welcher Klasse (Teaching Assignments)
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignUuid('subject_id')->index()->constrained('subjects')->restrictOnDelete();
            $table->foreignId('teacher_id')->index()->constrained('users')->restrictOnDelete();
            $table->smallInteger('semester')->nullable(); // leer = ganzes Schuljahr
            $table->timestamps();
        });
        DB::statement('ALTER TABLE teaching_assignments ADD CONSTRAINT teaching_assignments_unique UNIQUE NULLS NOT DISTINCT (school_class_id, subject_id, teacher_id, semester)');
        DB::statement('ALTER TABLE teaching_assignments ADD CONSTRAINT teaching_assignments_semester_check CHECK (semester IN (1, 2))');

        // 12. Private Teams & Lerngruppen (Teams)
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('tag', 5)->nullable();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('join_code', 10)->nullable()->unique();
            $table->boolean('is_private')->default(true);
            $table->timestamps();
        });

        // 13. Team-Mitglieder (Team Members)
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // leader, member
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['team_id', 'user_id']);
        });

        // 14. Trophäen & Errungenschaften (Trophies)
        Schema::create('trophies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description');
            $table->string('icon_name')->default('trophy');
            $table->integer('bp_reward')->default(0);
            $table->timestamps();
        });

        // 15. Freigeschaltete Trophäen (User Trophies)
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
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('school_years');
        Schema::dropIfExists('curriculum_entries');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('school_email_domains');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('question_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('user_stats');
    }
};
