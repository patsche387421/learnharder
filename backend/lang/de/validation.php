<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validierungsmeldungen
    |--------------------------------------------------------------------------
    |
    | Deutsche Fassung von Laravels Standardmeldungen (E-012: nur Deutsch).
    | Schlüssel wie in vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php,
    | ein Test prüft, dass keiner fehlt. Ton laut docs/design/Sprache.md: kurz, du-Form.
    |
    */

    'accepted' => 'Das Feld :attribute muss akzeptiert werden.',
    'accepted_if' => 'Das Feld :attribute muss akzeptiert werden, wenn :other :value ist.',
    'active_url' => 'Das Feld :attribute muss eine gültige URL sein.',
    'after' => 'Das Feld :attribute muss ein Datum nach :date sein.',
    'after_or_equal' => 'Das Feld :attribute muss ein Datum am oder nach :date sein.',
    'alpha' => 'Das Feld :attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das Feld :attribute darf nur Buchstaben, Ziffern, Binde- und Unterstriche enthalten.',
    'alpha_num' => 'Das Feld :attribute darf nur Buchstaben und Ziffern enthalten.',
    'any_of' => 'Das Feld :attribute ist ungültig.',
    'array' => 'Das Feld :attribute muss eine Liste sein.',
    'array_keys' => 'Das Feld :attribute darf nur diese Schlüssel enthalten: :values.',
    'ascii' => 'Das Feld :attribute darf nur einfache Buchstaben, Ziffern und Zeichen enthalten (ASCII).',
    'base64' => 'Das Feld :attribute muss ein gültiger Base64-Text sein.',
    'before' => 'Das Feld :attribute muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das Feld :attribute muss ein Datum am oder vor :date sein.',
    'between' => [
        'array' => 'Das Feld :attribute muss zwischen :min und :max Einträge haben.',
        'file' => 'Die Datei :attribute muss zwischen :min und :max Kilobyte groß sein.',
        'numeric' => 'Das Feld :attribute muss zwischen :min und :max liegen.',
        'string' => 'Das Feld :attribute muss zwischen :min und :max Zeichen lang sein.',
    ],
    'boolean' => 'Das Feld :attribute muss ja oder nein sein.',
    'can' => 'Das Feld :attribute enthält einen Wert, den du nicht setzen darfst.',
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'contains' => 'Im Feld :attribute fehlt ein Pflichtwert.',
    'current_password' => 'Das Passwort stimmt nicht.',
    'date' => 'Das Feld :attribute muss ein gültiges Datum sein.',
    'date_equals' => 'Das Feld :attribute muss das Datum :date sein.',
    'date_format' => 'Das Feld :attribute muss das Format :format haben.',
    'decimal' => 'Das Feld :attribute muss :decimal Nachkommastellen haben.',
    'declined' => 'Das Feld :attribute muss abgelehnt werden.',
    'declined_if' => 'Das Feld :attribute muss abgelehnt werden, wenn :other :value ist.',
    'different' => 'Die Felder :attribute und :other müssen sich unterscheiden.',
    'digits' => 'Das Feld :attribute muss :digits Ziffern haben.',
    'digits_between' => 'Das Feld :attribute muss zwischen :min und :max Ziffern haben.',
    'dimensions' => 'Das Bild :attribute hat ungültige Abmessungen.',
    'distinct' => 'Das Feld :attribute enthält einen doppelten Wert.',
    'doesnt_contain' => 'Das Feld :attribute darf nichts davon enthalten: :values.',
    'doesnt_end_with' => 'Das Feld :attribute darf nicht so enden: :values.',
    'doesnt_start_with' => 'Das Feld :attribute darf nicht so beginnen: :values.',
    'email' => 'Das Feld :attribute muss eine gültige E-Mail-Adresse sein.',
    'encoding' => 'Das Feld :attribute muss als :encoding kodiert sein.',
    'ends_with' => 'Das Feld :attribute muss so enden: :values.',
    'enum' => 'Der gewählte Wert für :attribute ist ungültig.',
    'exists' => 'Der gewählte Wert für :attribute ist ungültig.',
    'extensions' => 'Die Datei :attribute muss eine dieser Endungen haben: :values.',
    'file' => 'Das Feld :attribute muss eine Datei sein.',
    'filled' => 'Das Feld :attribute darf nicht leer sein.',
    'gt' => [
        'array' => 'Das Feld :attribute muss mehr als :value Einträge haben.',
        'file' => 'Die Datei :attribute muss größer als :value Kilobyte sein.',
        'numeric' => 'Das Feld :attribute muss größer als :value sein.',
        'string' => 'Das Feld :attribute muss länger als :value Zeichen sein.',
    ],
    'gte' => [
        'array' => 'Das Feld :attribute muss mindestens :value Einträge haben.',
        'file' => 'Die Datei :attribute muss mindestens :value Kilobyte groß sein.',
        'numeric' => 'Das Feld :attribute muss mindestens :value sein.',
        'string' => 'Das Feld :attribute muss mindestens :value Zeichen lang sein.',
    ],
    'hex_color' => 'Das Feld :attribute muss eine gültige Hex-Farbe sein.',
    'image' => 'Das Feld :attribute muss ein Bild sein.',
    'in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'in_array' => 'Das Feld :attribute muss in :other vorkommen.',
    'in_array_keys' => 'Das Feld :attribute muss mindestens einen dieser Schlüssel enthalten: :values.',
    'integer' => 'Das Feld :attribute muss eine ganze Zahl sein.',
    'ip' => 'Das Feld :attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => 'Das Feld :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => 'Das Feld :attribute muss eine gültige IPv6-Adresse sein.',
    'json' => 'Das Feld :attribute muss gültiges JSON sein.',
    'list' => 'Das Feld :attribute muss eine Liste sein.',
    'lowercase' => 'Das Feld :attribute darf nur Kleinbuchstaben enthalten.',
    'lt' => [
        'array' => 'Das Feld :attribute muss weniger als :value Einträge haben.',
        'file' => 'Die Datei :attribute muss kleiner als :value Kilobyte sein.',
        'numeric' => 'Das Feld :attribute muss kleiner als :value sein.',
        'string' => 'Das Feld :attribute muss kürzer als :value Zeichen sein.',
    ],
    'lte' => [
        'array' => 'Das Feld :attribute darf höchstens :value Einträge haben.',
        'file' => 'Die Datei :attribute darf höchstens :value Kilobyte groß sein.',
        'numeric' => 'Das Feld :attribute darf höchstens :value sein.',
        'string' => 'Das Feld :attribute darf höchstens :value Zeichen lang sein.',
    ],
    'mac_address' => 'Das Feld :attribute muss eine gültige MAC-Adresse sein.',
    'max' => [
        'array' => 'Das Feld :attribute darf höchstens :max Einträge haben.',
        'file' => 'Die Datei :attribute darf höchstens :max Kilobyte groß sein.',
        'numeric' => 'Das Feld :attribute darf höchstens :max sein.',
        'string' => 'Das Feld :attribute darf höchstens :max Zeichen lang sein.',
    ],
    'max_digits' => 'Das Feld :attribute darf höchstens :max Ziffern haben.',
    'mimes' => 'Die Datei :attribute muss vom Typ :values sein.',
    'mimetypes' => 'Die Datei :attribute muss vom Typ :values sein.',
    'min' => [
        'array' => 'Das Feld :attribute muss mindestens :min Einträge haben.',
        'file' => 'Die Datei :attribute muss mindestens :min Kilobyte groß sein.',
        'numeric' => 'Das Feld :attribute muss mindestens :min sein.',
        'string' => 'Das Feld :attribute muss mindestens :min Zeichen lang sein.',
    ],
    'min_digits' => 'Das Feld :attribute muss mindestens :min Ziffern haben.',
    'missing' => 'Das Feld :attribute darf nicht vorhanden sein.',
    'missing_if' => 'Das Feld :attribute darf nicht vorhanden sein, wenn :other :value ist.',
    'missing_unless' => 'Das Feld :attribute darf nur vorhanden sein, wenn :other :value ist.',
    'missing_with' => 'Das Feld :attribute darf nicht vorhanden sein, wenn :values angegeben ist.',
    'missing_with_all' => 'Das Feld :attribute darf nicht vorhanden sein, wenn :values angegeben sind.',
    'multiple_of' => 'Das Feld :attribute muss ein Vielfaches von :value sein.',
    'not_in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'not_regex' => 'Das Format von :attribute ist ungültig.',
    'numeric' => 'Das Feld :attribute muss eine Zahl sein.',
    'password' => [
        'letters' => 'Das Feld :attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => 'Das Feld :attribute muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers' => 'Das Feld :attribute muss mindestens eine Ziffer enthalten.',
        'symbols' => 'Das Feld :attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Dieses :attribute ist in einem Datenleck aufgetaucht. Wähl bitte ein anderes.',
    ],
    'present' => 'Das Feld :attribute muss vorhanden sein.',
    'present_if' => 'Das Feld :attribute muss vorhanden sein, wenn :other :value ist.',
    'present_unless' => 'Das Feld :attribute muss vorhanden sein, außer :other ist :value.',
    'present_with' => 'Das Feld :attribute muss vorhanden sein, wenn :values angegeben ist.',
    'present_with_all' => 'Das Feld :attribute muss vorhanden sein, wenn :values angegeben sind.',
    'prohibited' => 'Das Feld :attribute ist nicht erlaubt.',
    'prohibited_if' => 'Das Feld :attribute ist nicht erlaubt, wenn :other :value ist.',
    'prohibited_if_accepted' => 'Das Feld :attribute ist nicht erlaubt, wenn :other akzeptiert ist.',
    'prohibited_if_declined' => 'Das Feld :attribute ist nicht erlaubt, wenn :other abgelehnt ist.',
    'prohibited_unless' => 'Das Feld :attribute ist nur erlaubt, wenn :other in :values ist.',
    'prohibits' => 'Wenn :attribute ausgefüllt ist, darf :other nicht vorhanden sein.',
    'regex' => 'Das Format von :attribute ist ungültig.',
    'required' => 'Das Feld :attribute ist ein Pflichtfeld.',
    'required_array_keys' => 'Das Feld :attribute muss Einträge für :values enthalten.',
    'required_if' => 'Das Feld :attribute ist ein Pflichtfeld, wenn :other :value ist.',
    'required_if_accepted' => 'Das Feld :attribute ist ein Pflichtfeld, wenn :other akzeptiert ist.',
    'required_if_declined' => 'Das Feld :attribute ist ein Pflichtfeld, wenn :other abgelehnt ist.',
    'required_unless' => 'Das Feld :attribute ist ein Pflichtfeld, außer :other ist in :values.',
    'required_with' => 'Das Feld :attribute ist ein Pflichtfeld, wenn :values angegeben ist.',
    'required_with_all' => 'Das Feld :attribute ist ein Pflichtfeld, wenn :values angegeben sind.',
    'required_without' => 'Das Feld :attribute ist ein Pflichtfeld, wenn :values fehlt.',
    'required_without_all' => 'Das Feld :attribute ist ein Pflichtfeld, wenn keins von :values angegeben ist.',
    'same' => 'Die Felder :attribute und :other müssen übereinstimmen.',
    'size' => [
        'array' => 'Das Feld :attribute muss genau :size Einträge haben.',
        'file' => 'Die Datei :attribute muss genau :size Kilobyte groß sein.',
        'numeric' => 'Das Feld :attribute muss genau :size sein.',
        'string' => 'Das Feld :attribute muss genau :size Zeichen lang sein.',
    ],
    'starts_with' => 'Das Feld :attribute muss so beginnen: :values.',
    'string' => 'Das Feld :attribute muss ein Text sein.',
    'timezone' => 'Das Feld :attribute muss eine gültige Zeitzone sein.',
    'unique' => 'Dieser Wert für :attribute ist schon vergeben.',
    'uploaded' => 'Die Datei :attribute konnte nicht hochgeladen werden.',
    'uppercase' => 'Das Feld :attribute darf nur Großbuchstaben enthalten.',
    'url' => 'Das Feld :attribute muss eine gültige URL sein.',
    'ulid' => 'Das Feld :attribute muss eine gültige ULID sein.',
    'uuid' => 'Das Feld :attribute muss eine gültige UUID sein.',

    /*
    |--------------------------------------------------------------------------
    | Eigene Meldungen pro Feld
    |--------------------------------------------------------------------------
    |
    | Schreibweise "feld.regel", z. B. 'email' => ['unique' => '…'].
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Feldnamen
    |--------------------------------------------------------------------------
    |
    | Ersetzen :attribute außerhalb von Filament (z. B. in der API).
    | Filament setzt das Label des Formularfelds selbst ein.
    |
    */

    'attributes' => [],

];
