<?php

namespace App\Policies;

/**
 * Erfolge pflegt nur der Admin (Gate::before, E-042): Sie bringen Trophäen und gehören zur Spiel-Ökonomie, nicht zur Redaktion.
 * Ein vergebener Erfolg lässt sich nicht löschen, das verweigert das Model (BlocksDeletionWhileInUse).
 */
class AchievementPolicy extends SchoolStructurePolicy {}
