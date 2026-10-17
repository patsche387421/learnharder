<?php

namespace Database\Factories;

use App\Enums\AchievementRule;
use App\Models\Achievement;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Erfolg für Tests. Standard: sichtbar, aktiv, „1 Lerneinheit abgeschlossen“, 1 Trophäe.
 * Beispiel: Achievement::factory()->rule(AchievementRule::Streak, 7)->secret()->create().
 *
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(3),
            'title' => ['de' => 'Erste Lerneinheit'],
            'description' => ['de' => '1 Lerneinheit abschließen'],
            'rule' => AchievementRule::LessonsFinished,
            'threshold' => 1,
            'trophy_reward' => 1,
        ];
    }

    public function rule(AchievementRule $rule, int $threshold = 1): static
    {
        return $this->state(['rule' => $rule, 'threshold' => $threshold]);
    }

    /** Fach-Level in einem bestimmten Fach. Ohne Fach zählt irgendeines. */
    public function subjectLevel(int $threshold, ?Subject $subject = null): static
    {
        return $this->state(['rule' => AchievementRule::SubjectLevel, 'threshold' => $threshold, 'subject_id' => $subject?->id]);
    }

    public function secret(): static
    {
        return $this->state(['is_secret' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function trophies(int $trophies): static
    {
        return $this->state(['trophy_reward' => $trophies]);
    }
}
