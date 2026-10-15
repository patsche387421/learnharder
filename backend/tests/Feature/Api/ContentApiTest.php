<?php

namespace Tests\Feature\Api;

use App\Enums\ContentStatus;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserStat;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Subject $pos;

    private Topic $schleifen;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');

        $this->user = User::factory()->create();

        $this->pos = Subject::create(['slug' => 'pos', 'name' => ['de' => 'Programmieren'], 'order_index' => 2]);
        $this->schleifen = $this->pos->topics()->create([
            'slug' => 'schleifen', 'title' => ['de' => 'Schleifen'], 'status' => ContentStatus::Published, 'order_index' => 1,
        ]);
        $this->schleifen->theorySections()->create([
            'title' => ['de' => 'for-Schleife'], 'content' => ['de' => "Eine **for**-Schleife wiederholt Code.\n\n```java\nfor (int i = 0; i < 3; i++) {}\n```"],
        ]);

        $frage = $this->schleifen->questions()->create(['question_text' => ['de' => 'Wie oft?'], 'status' => ContentStatus::Published]);
        $frage->options()->create(['option_text' => ['de' => 'Dreimal'], 'is_correct' => true]);
        $frage->options()->create(['option_text' => ['de' => 'Nie'], 'is_correct' => false]);
        $this->schleifen->questions()->create(['question_text' => ['de' => 'Entwurf?'], 'status' => ContentStatus::Draft]);
    }

    private function topic(Subject $subject, string $slug, ContentStatus $status, array $extra = []): Topic
    {
        return $subject->topics()->create(['slug' => $slug, 'title' => ['de' => ucfirst($slug)], 'status' => $status, ...$extra]);
    }

    public function test_ohne_login_gibt_es_nichts(): void
    {
        $this->getJson('/api/v1/subjects')->assertUnauthorized();
        $this->getJson('/api/v1/subjects/pos')->assertUnauthorized();
        $this->getJson('/api/v1/subjects/pos/topics/schleifen')->assertUnauthorized();
    }

    public function test_faecherliste_zeigt_nur_aktive_faecher_mit_veroeffentlichtem_thema(): void
    {
        $dbi = Subject::create(['slug' => 'dbi', 'name' => ['de' => 'Datenbanken'], 'order_index' => 1]);
        $this->topic($dbi, 'sql', ContentStatus::Published);
        $this->topic($dbi, 'joins', ContentStatus::Review);

        $nurEntwurf = Subject::create(['slug' => 'syp', 'name' => ['de' => 'Systemplanung']]);
        $this->topic($nurEntwurf, 'uml', ContentStatus::Draft);

        $inaktiv = Subject::create(['slug' => 'wir', 'name' => ['de' => 'Wirtschaft'], 'is_active' => false]);
        $this->topic($inaktiv, 'markt', ContentStatus::Published);

        $this->actingAs($this->user)
            ->getJson('/api/v1/subjects')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'dbi')
            ->assertJsonPath('data.0.topics_count', 1)
            ->assertJsonPath('data.1.code', 'pos')
            ->assertJsonPath('data.1.name', 'Programmieren')
            ->assertJsonPath('data.1.color_token', '--subject-pos');
    }

    public function test_fach_zeigt_nur_veroeffentlichte_themen_und_fragen(): void
    {
        $this->topic($this->pos, 'methoden', ContentStatus::Review);
        $this->topic($this->pos, 'arrays', ContentStatus::Draft);

        $this->actingAs($this->user)
            ->getJson('/api/v1/subjects/pos')
            ->assertOk()
            ->assertJsonPath('data.code', 'pos')
            ->assertJsonCount(1, 'data.topics')
            ->assertJsonPath('data.topics.0.slug', 'schleifen')
            ->assertJsonPath('data.topics.0.title', 'Schleifen')
            ->assertJsonPath('data.topics.0.questions_count', 1)
            ->assertJsonPath('data.topics.0.locked', false);
    }

    public function test_unsichtbare_faecher_und_themen_geben_404(): void
    {
        $this->topic($this->pos, 'methoden', ContentStatus::Review);
        $inaktiv = Subject::create(['slug' => 'wir', 'name' => ['de' => 'Wirtschaft'], 'is_active' => false]);
        $this->topic($inaktiv, 'markt', ContentStatus::Published);

        $this->actingAs($this->user);
        $this->getJson('/api/v1/subjects/gibtsnicht')->assertNotFound();
        $this->getJson('/api/v1/subjects/wir')->assertNotFound();
        $this->getJson('/api/v1/subjects/wir/topics/markt')->assertNotFound();
        $this->getJson('/api/v1/subjects/pos/topics/methoden')->assertNotFound();
        $this->getJson('/api/v1/subjects/pos/topics/gibtsnicht')->assertNotFound();
    }

    public function test_thema_aus_anderem_fach_gibt_404(): void
    {
        $dbi = Subject::create(['slug' => 'dbi', 'name' => ['de' => 'Datenbanken']]);
        $this->topic($dbi, 'sql', ContentStatus::Published);

        $this->actingAs($this->user)->getJson('/api/v1/subjects/pos/topics/sql')->assertNotFound();
    }

    public function test_thema_liefert_theorie_als_html(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/subjects/pos/topics/schleifen')
            ->assertOk()
            ->assertJsonPath('data.title', 'Schleifen')
            ->assertJsonPath('data.questions_count', 1)
            ->assertJsonPath('data.xp_total', 2)
            ->assertJsonPath('data.theory.0.title', 'for-Schleife')
            ->assertJsonPath('data.theory.0.html', fn (string $html) => str_contains($html, '<strong>for</strong>')
                && str_contains($html, '<code class="language-java">'));
    }

    public function test_html_und_javascript_links_in_der_theorie_werden_entschaerft(): void
    {
        $this->schleifen->theorySections()->create([
            'title' => ['de' => 'Böse'],
            'content' => ['de' => "<script>alert(1)</script>\n\n[klick](javascript:alert(1))\n\n<img src=x onerror=alert(1)>"],
            'order_index' => 2,
        ]);

        $html = $this->actingAs($this->user)
            ->getJson('/api/v1/subjects/pos/topics/schleifen')
            ->assertOk()
            ->json('data.theory.1.html');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_thema_ueber_dem_eigenen_level_ist_gesperrt_aber_lesbar(): void
    {
        $this->topic($this->pos, 'rekursion', ContentStatus::Published, ['level_required' => 2]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/subjects/pos/topics/rekursion')
            ->assertOk()
            ->assertJsonPath('data.locked', true);

        // Level 2 beginnt bei 8 EP
        UserStat::create(['user_id' => $this->user->id, 'total_xp' => 8]);

        $this->actingAs($this->user->fresh())
            ->getJson('/api/v1/subjects/pos/topics/rekursion')
            ->assertJsonPath('data.locked', false);
    }

    public function test_keine_antwort_verraet_die_loesung(): void
    {
        $this->actingAs($this->user);

        foreach (['/api/v1/subjects', '/api/v1/subjects/pos', '/api/v1/subjects/pos/topics/schleifen'] as $url) {
            $body = $this->getJson($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('is_correct', $body);
            $this->assertStringNotContainsString('Dreimal', $body);
        }
    }

    public function test_jede_rolle_darf_lesen(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (User::whereNotNull('gamer_tag')->get() as $user) {
            // Jede Person mit eigener Session wie im echten Betrieb (ein Wechsel in derselben Session gibt 401)
            $this->flushSession();
            $this->actingAs($user)->getJson('/api/v1/subjects/pos')->assertOk();
        }
    }
}
