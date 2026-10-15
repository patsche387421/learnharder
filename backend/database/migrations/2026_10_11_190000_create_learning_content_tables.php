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
        // 1. Fächer (Subjects)
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->json('name'); // {"de": "Programmieren", "en": "Programming"}
            $table->json('description')->nullable();
            $table->string('icon_name')->default('code');
            $table->string('color_token')->default('--subject-pos'); // Token aus frontend/src/styles/tokens.css
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Themengebiete (Topics)
        Schema::create('topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('slug');
            $table->json('title'); // {"de": "Datenstrukturen", "en": "Data Structures"}
            $table->json('description')->nullable();
            $table->integer('level_required')->default(1);
            $table->integer('order_index')->default(0);
            $table->string('status')->default('draft'); // App\Enums\ContentStatus
            $table->timestamps();

            $table->unique(['subject_id', 'slug']);
        });
        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_status_check CHECK (status IN ('draft', 'review', 'published'))");

        // 3. Theorie-Abschnitte (Theory Sections)
        Schema::create('theory_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->json('title');
            $table->json('content'); // Markdown content {"de": "...", "en": "..."}
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // 4. Fragen / Aufgaben (Questions)
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->string('type')->default('single_choice'); // App\Enums\QuestionType
            $table->json('question_text');
            $table->json('explanation')->nullable();
            $table->integer('xp_reward')->default(2);
            $table->integer('difficulty')->default(1); // App\Enums\Difficulty
            $table->string('status')->default('draft'); // App\Enums\ContentStatus
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_type_check CHECK (type IN ('single_choice', 'multiple_choice'))");
        DB::statement('ALTER TABLE questions ADD CONSTRAINT questions_difficulty_check CHECK (difficulty BETWEEN 1 AND 5)');
        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_status_check CHECK (status IN ('draft', 'review', 'published'))");

        // 5. Antwortoptionen (Question Options)
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('questions')->cascadeOnDelete();
            $table->json('option_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('theory_sections');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('subjects');
    }
};
