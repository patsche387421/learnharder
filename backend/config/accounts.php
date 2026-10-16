<?php

// Registrierung und Passwort mit Code per Mail (E-025, Plan Schule §4)
return [

    // Gültigkeit und Versuche je Code
    'code_minutes' => 10,
    'code_attempts' => 5,

    // Obergrenze für alle Code-Mails zusammen pro Tag. Schützt Schulpostfächer, weil die Adressen leicht zu erraten sind.
    'codes_per_day' => (int) env('EMAIL_CODES_PER_DAY', 500),

    // Nach dem richtigen Code: so lange gilt der Einmal-Token für den letzten Schritt
    'registration_token_minutes' => 30,

];
