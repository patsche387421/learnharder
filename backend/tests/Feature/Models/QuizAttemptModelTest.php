<?php

namespace Tests\Feature\Models;

use App\Enums\AttemptType;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_jede_art_passt_zur_datenbankregel(): void
    {
        // Enum und CHECK-Regel in der Migration dürfen nicht auseinanderlaufen
        $user = User::factory()->create();
        $topic = Subject::create(['slug' => 'pos', 'name' => ['de' => 'POS']])->topics()->create(['slug' => 'schleifen', 'title' => ['de' => 'Schleifen']]);

        foreach (AttemptType::cases() as $type) {
            QuizAttempt::create(['user_id' => $user->id, 'attempt_type' => $type, 'topic_id' => $topic->id, 'question_ids' => []]);
        }

        $this->assertSame(count(AttemptType::cases()), QuizAttempt::count());
    }

    public function test_herausforderung_kommt_mit_enum_und_fragenliste_zurueck(): void
    {
        $attempt = QuizAttempt::create([
            'user_id' => User::factory()->create()->id, 'attempt_type' => AttemptType::Challenge,
            'question_ids' => ['a', 'b'], 'brainpower_spent' => 25, 'xp_bonus' => 20, 'trophies_earned' => 1,
        ])->fresh();

        $this->assertSame(AttemptType::Challenge, $attempt->attempt_type);
        $this->assertNull($attempt->topic_id);
        $this->assertSame(['a', 'b'], $attempt->question_ids);
        $this->assertSame(25, $attempt->brainpower_spent);
        $this->assertSame(20, $attempt->xp_bonus);
        $this->assertSame(1, $attempt->trophies_earned);
    }

    public function test_werte_der_herausforderung_nach_e_045(): void
    {
        // Ändert sich ein Wert nach der Abstimmung mit den Lehrkräften (M5), hier und im Ökonomie-Modell §3.3 nachziehen
        $this->assertSame([
            'cost' => 25,
            'questions' => 10,
            'xp_per_correct' => 5,
            'bonus' => [8 => 20, 10 => 50],
            'perfect_trophies' => 1,
            'daily_limit' => 2,
        ], config('gamification.challenge'));
    }
}
