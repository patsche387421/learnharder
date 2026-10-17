<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Achievement;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\Concerns\EnrollsStudents;
use Tests\TestCase;

/**
 * Eigenes Konto löschen (E-027): sofort, vollständig, nur Schüler:innen, nur mit Passwort.
 */
class DeleteAccountTest extends TestCase
{
    use EnrollsStudents, RefreshDatabase;

    private const TABLES = ['user_stats', 'quiz_attempts', 'question_answers', 'user_achievements', 'class_enrollments', 'sessions'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
    }

    public function test_loescht_konto_und_gesamten_lernverlauf(): void
    {
        $user = User::factory()->create(['remember_token' => 'alt']);
        $this->learnSomething($user);
        DB::table('sessions')->insert(['id' => 'handy', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);
        foreach (self::TABLES as $table) {
            $this->assertGreaterThan(0, DB::table($table)->where('user_id', $user->id)->count(), $table);
        }

        $this->actingAs($user)->deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($user);
        foreach ([...self::TABLES, 'model_has_roles'] as $table) {
            $column = $table === 'model_has_roles' ? 'model_id' : 'user_id';
            $this->assertSame(0, DB::table($table)->where($column, $user->id)->count(), $table);
        }
        $this->assertGuest('web');
    }

    public function test_falsches_passwort_loescht_nichts(): void
    {
        $user = $this->student();

        $this->actingAs($user)->deleteJson('/api/v1/me', ['password' => 'falsch'])
            ->assertJsonValidationErrors(['password' => 'Das Passwort stimmt nicht.']);

        $this->assertModelExists($user);
    }

    public function test_personal_loescht_sich_nicht_selbst(): void
    {
        foreach (['admin', 'autor', 'tester', 'teacher'] as $role) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate($role, 'web'));

            $this->actingAs($user)->deleteJson('/api/v1/me', ['password' => 'password'])
                ->assertForbidden()
                ->assertJsonPath('message', 'Dein Konto kann nur ein Admin sperren oder löschen.');

            $this->assertModelExists($user);
        }
    }

    public function test_ohne_login_nicht_moeglich(): void
    {
        $this->deleteJson('/api/v1/me', ['password' => 'password'])->assertUnauthorized();
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('student', 'web'));

        return $user;
    }

    /** Klasse, eine abgeschlossene Lerneinheit mit Antwort und EP: alles, was am Konto hängt. */
    private function learnSomething(User $user): void
    {
        $subject = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']]);
        $this->addToCurriculum($this->enrollStudent($user), $subject);
        $topic = $subject->topics()->create(['slug' => 'schleifen', 'title' => ['de' => 'Schleifen'], 'status' => ContentStatus::Published]);
        $question = $topic->questions()->create([
            'question_text' => ['de' => 'Frage'], 'type' => QuestionType::SingleChoice, 'status' => ContentStatus::Published, 'xp_reward' => 2,
        ]);
        $richtig = $question->options()->create(['option_text' => ['de' => 'richtig'], 'is_correct' => true, 'order_index' => 0]);
        $question->options()->create(['option_text' => ['de' => 'falsch'], 'is_correct' => false, 'order_index' => 1]);

        $attemptId = $this->actingAs($user)->postJson("/api/v1/topics/{$topic->id}/attempts")->assertCreated()->json('data.id');
        $this->postJson("/api/v1/attempts/{$attemptId}/answers", ['question_id' => $question->id, 'option_ids' => [$richtig->id]])->assertOk();
        $this->postJson("/api/v1/attempts/{$attemptId}/finish")->assertOk();
        $user->achievements()->attach(Achievement::factory()->create());
    }
}
