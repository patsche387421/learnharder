<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Wie HasTranslations, aber leere Einträge werden entfernt statt als {"de":null} gespeichert.
 * Bleibt keine Sprache übrig, steht in der Spalte NULL. Andere Sprachen bleiben erhalten.
 */
trait HasTranslationsWithoutEmpty
{
    use HasTranslations {
        setAttribute as setTranslatableAttribute;
    }

    public function setAttribute($key, $value)
    {
        if (! $this->isTranslatableAttribute($key)) {
            return $this->setTranslatableAttribute($key, $value);
        }

        // Ein einzelner Wert gilt für die aktuelle Sprache, ein Array mit Sprachschlüsseln für mehrere
        $translations = is_array($value) && (! array_is_list($value) || $value === []) ? $value : [$this->getLocale() => $value];

        foreach ($translations as $locale => $translation) {
            if ($translation === null || $translation === '') {
                $this->forgetTranslation($key, $locale);
            } else {
                $this->setTranslation($key, $locale, $translation);
            }
        }

        if ($this->getTranslations($key) === []) {
            $this->attributes[$key] = null;
        }

        return $this;
    }
}
