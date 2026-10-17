<?php

namespace Tests\Feature;

use App\Enums\AchievementRule;
use App\Models\Achievement;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Erster Katalog der Erfolge (E-044): 12 Erfolge, 2 geheim, in jeder Umgebung, ohne Änderungen aus dem CMS zu überschreiben.
 */
class AchievementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_legt_den_ersten_katalog_an(): void
    {
        $this->seed(AchievementSeeder::class);

        $this->assertSame(12, Achievement::count());
        $this->assertEqualsCanonicalizing(['from-mistakes', 'allrounder'], Achievement::where('is_secret', true)->pluck('slug')->all());
        $this->assertSame(0, Achievement::where('is_active', false)->count());

        $woche = Achievement::firstWhere('slug', 'streak-7');
        $this->assertSame('Eine Woche', $woche->title);
        $this->assertSame(AchievementRule::Streak, $woche->rule);
        $this->assertSame(7, $woche->threshold);
        $this->assertSame(2, $woche->trophy_reward);
        $this->assertSame('first-lesson', Achievement::orderBy('sort')->first()->slug);
    }

    public function test_zweiter_lauf_ueberschreibt_nichts_aus_dem_cms(): void
    {
        $this->seed(AchievementSeeder::class);
        Achievement::firstWhere('slug', 'streak-7')->update(['title' => ['de' => 'Sieben auf einen Streich'], 'trophy_reward' => 4, 'is_active' => false]);

        $this->seed(AchievementSeeder::class);

        $this->assertSame(12, Achievement::count());
        $woche = Achievement::firstWhere('slug', 'streak-7');
        $this->assertSame('Sieben auf einen Streich', $woche->title);
        $this->assertSame(4, $woche->trophy_reward);
        $this->assertFalse($woche->is_active);
    }
}
