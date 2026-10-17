<?php

// Werte der Spiel-Ökonomie an einer Stelle (Ökonomie-Modell §3, Konzept Spielwelt §5).
// Das Brainpower-Konzept wird vor dem Start mit den Lehrkräften abgestimmt (M5), Änderungen nur hier.
return [

    'brainpower' => [
        // Kapazität: Start plus 5 je 5 Level, gedeckelt. Ab Prestige 1 immer der Deckel (E-036).
        'max_base' => 100,
        'max_step_levels' => 5,
        'max_step' => 5,
        'max_cap' => 200,

        // Tägliches Aufladen um Mitternacht in Wien (E-036): Basis plus 2 je Streak-Tag, höchstens 30 Bonus
        'daily_refill' => 50,
        'streak_bonus_per_day' => 2,
        'streak_bonus_max' => 30,
    ],

];
