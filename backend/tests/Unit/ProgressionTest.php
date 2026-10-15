<?php

namespace Tests\Unit;

use App\Enums\Rank;
use App\Support\Progression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProgressionTest extends TestCase
{
    /** Schwellen aus der Tabelle im Ökonomie-Modell §2.2 */
    public static function schwellen(): array
    {
        return [[1, 0], [2, 8], [5, 64], [10, 216], [25, 941], [50, 2744], [75, 5093], [100, 7880]];
    }

    #[DataProvider('schwellen')]
    public function test_level_beginnt_genau_an_der_schwelle(int $level, int $xp): void
    {
        $this->assertSame($xp, Progression::threshold($level));
        $this->assertSame($level, Progression::fromTotalXp($xp)->level);

        if ($xp > 0) {
            $this->assertSame($level - 1, Progression::fromTotalXp($xp - 1)->level);
        }
    }

    public function test_leiste_reicht_vom_level_bis_zum_naechsten(): void
    {
        $p = Progression::fromTotalXp(450);

        $this->assertSame(15, $p->level);
        $this->assertSame(419, $p->levelStart);
        $this->assertSame(465, $p->nextLevel);
    }

    public function test_level_100_reicht_bis_zum_prestige(): void
    {
        $p = Progression::fromTotalXp(7999);

        $this->assertSame(100, $p->level);
        $this->assertSame(0, $p->prestige);
        $this->assertSame(7880, $p->levelStart);
        $this->assertSame(8000, $p->nextLevel);
    }

    public function test_prestige_beginnt_wieder_bei_level_1_und_bronze(): void
    {
        $p = Progression::fromTotalXp(8000);

        $this->assertSame(1, $p->prestige);
        $this->assertSame(1, $p->level);
        $this->assertSame(Rank::Bronze, $p->rank);
        $this->assertSame(8000, $p->levelStart);
        $this->assertSame(8008, $p->nextLevel);
    }

    public function test_zweiter_zyklus_rechnet_wie_der_erste(): void
    {
        $p = Progression::fromTotalXp(2 * 8000 + 941);

        $this->assertSame(2, $p->prestige);
        $this->assertSame(25, $p->level);
        $this->assertSame(Rank::Silver, $p->rank);
    }

    /** Rang-Grenzen aus E-016 */
    public static function raenge(): array
    {
        return [
            [1, Rank::Bronze], [24, Rank::Bronze],
            [25, Rank::Silver], [49, Rank::Silver],
            [50, Rank::Gold], [74, Rank::Gold],
            [75, Rank::Platin], [100, Rank::Platin],
        ];
    }

    #[DataProvider('raenge')]
    public function test_rang_grenzen(int $level, Rank $rank): void
    {
        $this->assertSame($rank, Rank::forLevel($level));
        $this->assertSame($rank, Progression::fromTotalXp(Progression::threshold($level))->rank);
    }

    public function test_fach_level_ohne_prestige_mit_schluss_bei_100(): void
    {
        $this->assertSame(1, Progression::subjectLevel(0));
        $this->assertSame(2, Progression::subjectLevel(8));
        $this->assertSame(100, Progression::subjectLevel(7880));
        $this->assertSame(100, Progression::subjectLevel(8000));
        $this->assertSame(100, Progression::subjectLevel(20000));
    }

    public function test_negative_ep_zaehlen_als_null(): void
    {
        $this->assertSame(1, Progression::fromTotalXp(-5)->level);
    }
}
