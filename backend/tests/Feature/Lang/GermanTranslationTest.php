<?php

namespace Tests\Feature\Lang;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GermanTranslationTest extends TestCase
{
    #[TestWith(['auth'])]
    #[TestWith(['pagination'])]
    #[TestWith(['passwords'])]
    #[TestWith(['validation'])]
    public function test_deutsche_datei_hat_alle_schluessel_von_laravel(string $file): void
    {
        $english = require base_path("vendor/laravel/framework/src/Illuminate/Translation/lang/en/{$file}.php");
        $german = require lang_path("de/{$file}.php");

        // custom und attributes (validation.php) sind projektspezifisch und dürfen abweichen.
        $keys = fn (array $lines) => array_keys(Arr::dot(Arr::except($lines, ['custom', 'attributes'])));

        $this->assertSame([], array_values(array_diff($keys($english), $keys($german))), "Fehlende Schlüssel in lang/de/{$file}.php");
        $this->assertSame([], array_values(array_diff($keys($german), $keys($english))), "Überzählige Schlüssel in lang/de/{$file}.php");
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
