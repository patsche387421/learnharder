<?php

namespace Database\Seeders;

use App\Models\QuizAttempt;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Support\Accounts;
use App\Support\DailyXp;
use App\Support\Progression;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Feste Testpersonen in der Demo-Klasse, um Zustände in der App von Hand zu prüfen (Level, Prestige, Streak, Brainpower,
 * Booster, Herausforderung und Erfolge, ohne Klasse, gesperrt). Anmelden mit <gamer-tag>@test.learnharder.app und dem Passwort der Demo-Konten.
 * Jeder Lauf löscht die Testpersonen samt Lernverlauf und legt sie neu an, so kommt alles in den Ausgangszustand.
 * Alle haben heute schon aufgeladen, damit der gespeicherte Stand dem entspricht, was die App zeigt. Wer einen Streak hat,
 * hat am letzten Lerntag auch eine Lerneinheit abgeschlossen, sonst hielte der Hub den Besuch für den ersten.
 * Läuft nach db:seed, Import und DemoSchoolSeeder:
 * `./vendor/bin/sail artisan db:seed --class=TestPersonasSeeder`
 */
class TestPersonasSeeder extends Seeder
{
    public const DOMAIN = 'test.learnharder.app';

    public function run(): void
    {
        // Bekanntes Passwort: nie in Produktion
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Testpersonen übersprungen (nur in local/testing).');

            return;
        }

        $class = SchoolClass::where('name', '2AHIT')->whereHas('schoolYear.school', fn ($schools) => $schools->where('name', 'Demo-HTL'))->first();
        $admin = User::where('email', 'admin@learnharder.app')->first();

        if (! $class || ! $admin) {
            $this->command?->warn('Testpersonen übersprungen: zuerst `db:seed`, den Import und den DemoSchoolSeeder ausführen.');

            return;
        }

        User::where('email', 'like', '%@'.self::DOMAIN)->get()->each->delete();

        $this->persona('Neuling', fn (UserFactory $f) => $f->inClass($class)->brainpower(100));
        $this->persona('Mittendrin', fn (UserFactory $f) => $f->inClass($class)->level(12)->streak(6)->brainpower(15));
        $this->persona('Boosterfan', fn (UserFactory $f) => $f->inClass($class)->level(30)->streak(3, today: true)->brainpower(60)->booster(2.0, minutes: 25));
        $this->persona('FastPrestige', fn (UserFactory $f) => $f->inClass($class)->xp(Progression::CYCLE_XP - 10)->streak(12, today: true)->brainpower(150));
        $this->persona('PrestigeProfi', fn (UserFactory $f) => $f->inClass($class)->level(10, prestige: 2)->streak(40, longest: 55)->brainpower(200));
        // 24 Lerneinheiten: genug gelernte Fragen für die Herausforderung, die 25. bringt „Dranbleiben“ (E-044)
        $this->lessonHistory($this->persona('Herausforderer', fn (UserFactory $f) => $f->inClass($class)->level(20)->brainpower(100)), 24);
        $this->persona('OhneKlasse', fn (UserFactory $f) => $f->student()->brainpower(100));
        Accounts::deactivate($this->persona('Gesperrt', fn (UserFactory $f) => $f->inClass($class)->brainpower(100)), $admin, 'Testperson');

        $this->command?->info('Testpersonen angelegt: Neuling, Mittendrin, Boosterfan, FastPrestige, PrestigeProfi, Herausforderer, OhneKlasse, Gesperrt (@'.self::DOMAIN.').');
    }

    /** @param  callable(UserFactory): UserFactory  $build */
    private function persona(string $tag, callable $build): User
    {
        $user = $build(User::factory())->create([
            'gamer_tag' => $tag,
            'email' => strtolower($tag).'@'.self::DOMAIN,
            // Schüler:innen haben keinen Namen (E-025)
            'name' => null,
            'password' => Hash::make('learnharder123'),
        ]);
        $this->lastLesson($user);

        return $user;
    }

    /**
     * $count abgeschlossene Lerneinheiten ohne EP in den ersten drei Themen mit Fragen je sichtbarem Fach, reihum, an den
     * $count Tagen vor heute um 17 Uhr in Wien. Ohne Streak, der Hub sieht trotzdem einen letzten Lerntag (gestern).
     */
    private function lessonHistory(User $user, int $count): void
    {
        $topics = Subject::visibleTo($user)->orderBy('order_index')->get()
            ->flatMap(fn (Subject $subject) => $subject->topics()->published()
                ->whereHas('questions', fn ($questions) => $questions->published())
                ->orderBy('order_index')->take(3)->get())
            ->values();
        if ($topics->isEmpty()) {
            return;
        }

        $evening = CarbonImmutable::parse(DailyXp::today().' 17:00', DailyXp::TIMEZONE);
        foreach (range(1, $count) as $i) {
            QuizAttempt::create([
                'user_id' => $user->id,
                'topic_id' => $topics[($i - 1) % $topics->count()]->id,
                'finished_at' => $evening->subDays($count - $i + 1)->utc(),
            ]);
        }
    }

    /**
     * Eine abgeschlossene Lerneinheit am letzten Lerntag, im ersten Thema, das die Person sieht. Der Hub liest daraus
     * last_study_at und das nächste Thema. Heute: gerade eben, gestern: um 17 Uhr in Wien. Ohne Streak passiert nichts.
     */
    private function lastLesson(User $user): void
    {
        $stats = $user->stats;
        if (! $stats?->current_streak || ! $stats->last_study_date) {
            return;
        }

        $topic = Subject::visibleTo($user)->orderBy('order_index')->get()
            ->map(fn (Subject $subject) => $subject->topics()->published()->orderBy('order_index')->first())
            ->filter()
            ->first();
        if (! $topic) {
            return;
        }

        $day = $stats->last_study_date->toDateString();
        QuizAttempt::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'finished_at' => $day === DailyXp::today() ? now() : CarbonImmutable::parse($day.' 17:00', DailyXp::TIMEZONE)->utc(),
        ]);
    }
}
