<?php

namespace Tests\Feature\Policies;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** Aktionen, die Filament prüft. Die mit Datensatz bekommen das Model, die anderen die Klasse. */
    private const RECORD_ABILITIES = ['view', 'update', 'replicate', 'delete', 'restore', 'forceDelete'];

    private const CLASS_ABILITIES = ['viewAny', 'create', 'reorder', 'deleteAny', 'restoreAny', 'forceDeleteAny'];

    /** Erlaubte Aktionen je Rolle, alles andere ist verboten */
    private const ALLOWED = [
        'admin' => [...self::RECORD_ABILITIES, ...self::CLASS_ABILITIES],
        'autor' => ['viewAny', 'view', 'create', 'update', 'replicate', 'reorder'],
        'tester' => ['viewAny', 'view'],
        'teacher' => [],
        'student' => [],
    ];

    public static function roles(): array
    {
        $roles = array_keys(self::ALLOWED);

        return array_combine($roles, array_map(fn (string $role) => [$role], $roles));
    }

    #[DataProvider('roles')]
    public function test_rechte_je_rolle_auf_fach_thema_und_frage(string $role): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => $role]));

        foreach ($this->contentRecords() as $record) {
            foreach (self::RECORD_ABILITIES as $ability) {
                $this->assertAbility($user, $role, $ability, $record);
            }
            foreach (self::CLASS_ABILITIES as $ability) {
                $this->assertAbility($user, $role, $ability, $record::class);
            }
        }
    }

    public function test_ohne_rolle_ist_alles_verboten(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', Subject::class));
        $this->assertFalse($user->can('view', $this->contentRecords()[0]));
    }

    private function assertAbility(User $user, string $role, string $ability, Model|string $target): void
    {
        $expected = in_array($ability, self::ALLOWED[$role], true);

        $this->assertSame($expected, $user->can($ability, $target), "$role → $ability auf ".class_basename($target));
    }

    /** @return array{0: Subject, 1: Topic, 2: Question} */
    private function contentRecords(): array
    {
        $subject = Subject::create(['slug' => 'mathe', 'name' => ['de' => 'Mathematik']]);
        $topic = Topic::create(['subject_id' => $subject->id, 'slug' => 'brueche', 'title' => ['de' => 'Brüche']]);
        $question = Question::create(['topic_id' => $topic->id, 'question_text' => ['de' => 'Was ist 1/2 + 1/4?']]);

        return [$subject, $topic, $question];
    }
}
