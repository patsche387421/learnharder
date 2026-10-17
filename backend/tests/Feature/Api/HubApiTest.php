<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\EnrollsStudents;
use Tests\TestCase;

class HubApiTest extends TestCase
{
    use EnrollsStudents, RefreshDatabase;

    private User $user;

    private SchoolClass $class;

    private Subject $dbi;

    private Subject $pos;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');

        $this->user = User::factory()->create();
        $this->class = $this->enrollStudent($this->user);

        $this->dbi = Subject::create(['slug' => 'dbi', 'name' => ['de' => 'Datenbanken'], 'order_index' => 1]);
        $this->pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'Programmieren'], 'order_index' => 2]);
        $this->addToCurriculum($this->class, $this->dbi);
        $this->addToCurriculum($this->class, $this->pos);
    }

    /** Veröffentlichtes Thema mit einer veröffentlichten Frage, also als Lerneinheit spielbar */
    private function topic(Subject $subject, string $slug, int $order, array $extra = []): Topic
    {
        $topic = $subject->topics()->create([
            'slug' => $slug, 'title' => ['de' => ucfirst($slug)], 'status' => ContentStatus::Published, 'order_index' => $order, ...$extra,
        ]);
        $topic->questions()->create(['question_text' => ['de' => 'Frage?'], 'status' => ContentStatus::Published]);

        return $topic;
    }

    private function finish(Topic $topic, ?User $user = null, ?Carbon $at = null): void
    {
        QuizAttempt::create(['user_id' => ($user ?? $this->user)->id, 'topic_id' => $topic->id, 'finished_at' => $at ?? now()]);
    }

    public function test_ohne_login_gibt_es_nichts(): void
    {
        $this->getJson('/api/v1/hub')->assertUnauthorized();
    }

    public function test_ohne_fortschritt_kommt_das_erste_thema_des_ersten_fachs(): void
    {
        $this->topic($this->pos, 'schleifen', 1);
        $this->topic($this->dbi, 'joins', 2);
        $this->topic($this->dbi, 'sql', 1);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertOk()
            ->assertJsonPath('data.next_topic.slug', 'sql')
            ->assertJsonPath('data.next_topic.title', 'Sql')
            ->assertJsonPath('data.next_topic.subject.code', 'dbi')
            ->assertJsonPath('data.next_topic.subject.name', 'Datenbanken');
    }

    public function test_das_fach_der_letzten_lerneinheit_kommt_zuerst(): void
    {
        $sql = $this->topic($this->dbi, 'sql', 1);
        $this->topic($this->dbi, 'joins', 2);
        $schleifen = $this->topic($this->pos, 'schleifen', 1);
        $this->topic($this->pos, 'methoden', 2);

        $this->finish($sql, at: now()->subDay());
        $this->finish($schleifen);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic.slug', 'methoden')
            ->assertJsonPath('data.next_topic.subject.code', 'pos');
    }

    public function test_ist_das_letzte_fach_erledigt_geht_es_im_naechsten_weiter(): void
    {
        $this->topic($this->dbi, 'sql', 1);
        $schleifen = $this->topic($this->pos, 'schleifen', 1);
        $this->finish($schleifen);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic.slug', 'sql');
    }

    public function test_gesperrte_leere_und_unveroeffentlichte_themen_zaehlen_nicht(): void
    {
        $this->topic($this->dbi, 'gesperrt', 1, ['level_required' => 2]);
        $this->topic($this->dbi, 'entwurf', 2, ['status' => ContentStatus::Draft]);
        $this->dbi->topics()->create(['slug' => 'ohne-fragen', 'title' => ['de' => 'Ohne Fragen'], 'status' => ContentStatus::Published, 'order_index' => 3]);
        $this->topic($this->dbi, 'sql', 4);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic.slug', 'sql');
    }

    public function test_lerneinheiten_anderer_und_offene_lerneinheiten_zaehlen_nicht(): void
    {
        $sql = $this->topic($this->dbi, 'sql', 1);
        $this->finish($sql, User::factory()->create());
        QuizAttempt::create(['user_id' => $this->user->id, 'topic_id' => $sql->id]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic.slug', 'sql');
    }

    public function test_ist_alles_erledigt_kommt_null(): void
    {
        $this->finish($this->topic($this->dbi, 'sql', 1));
        $this->finish($this->topic($this->pos, 'schleifen', 1));

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertOk()
            ->assertJsonPath('data.next_topic', null);
    }

    public function test_nur_faecher_der_eigenen_klasse(): void
    {
        $fremd = Subject::create(['slug' => 'wir', 'name' => ['de' => 'Wirtschaft'], 'order_index' => 0]);
        $this->topic($fremd, 'markt', 1);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic', null);

        $this->topic($this->pos, 'schleifen', 1);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.next_topic.slug', 'schleifen');
    }

    public function test_vor_der_ersten_lerneinheit_ist_last_study_at_null(): void
    {
        QuizAttempt::create(['user_id' => $this->user->id, 'topic_id' => $this->topic($this->dbi, 'sql', 1)->id]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.last_study_at', null);
    }

    public function test_last_study_at_ist_das_ende_der_letzten_eigenen_lerneinheit(): void
    {
        Carbon::setTestNow('2026-10-17 12:00:00');
        $sql = $this->topic($this->dbi, 'sql', 1);
        $this->finish($sql, at: now()->subDays(5));
        $this->finish($this->topic($this->pos, 'schleifen', 1), at: now()->subDays(2));
        $this->finish($sql, User::factory()->create(), now());

        $this->actingAs($this->user)
            ->getJson('/api/v1/hub')
            ->assertJsonPath('data.last_study_at', '2026-10-15T12:00:00+00:00');
    }
}
