<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests prüfen absichtlich Fehlerfälle. Ihre Log-Einträge gehören nicht ins App-Log (scripts/dev.py zeigt beide getrennt).
 */
class LoggingTest extends TestCase
{
    public function test_tests_loggen_in_eine_eigene_datei(): void
    {
        $marker = 'Probe '.Str::uuid();

        Log::warning($marker);

        $this->assertStringContainsString($marker, file_get_contents(storage_path('logs/testing.log')));
        $app = storage_path('logs/laravel.log');
        $this->assertFalse(is_file($app) && str_contains(file_get_contents($app), $marker), 'Eintrag aus dem Test im App-Log');
    }
}
