<?php

namespace Tests\Feature\Lang;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GermanValidationTest extends TestCase
{
    public function test_deutsche_validierung_hat_alle_schluessel_von_laravel(): void
    {
        $english = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $german = require lang_path('de/validation.php');

        // custom und attributes sind projektspezifisch und dürfen abweichen.
        $keys = fn (array $lines) => array_keys(Arr::dot(Arr::except($lines, ['custom', 'attributes'])));

        $this->assertSame([], array_values(array_diff($keys($english), $keys($german))), 'Fehlende Schlüssel in lang/de/validation.php');
        $this->assertSame([], array_values(array_diff($keys($german), $keys($english))), 'Überzählige Schlüssel in lang/de/validation.php');
    }

    public function test_validator_meldet_fehler_auf_deutsch(): void
    {
        app()->setLocale('de');

        $errors = Validator::make(['title' => 'ab'], [
            'title' => ['required', 'min:3'],
            'email' => ['required'],
        ], [], ['title' => 'Titel', 'email' => 'E-Mail'])->errors();

        $this->assertSame('Das Feld Titel muss mindestens 3 Zeichen lang sein.', $errors->first('title'));
        $this->assertSame('Das Feld E-Mail ist ein Pflichtfeld.', $errors->first('email'));
    }
}
