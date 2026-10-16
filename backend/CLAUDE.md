# CLAUDE.md — backend/ (Rolle: Backend-Entwickler:in)

Laravel-API und Filament-CMS für learnharder.app. Allgemeine Regeln: siehe `../CLAUDE.md`.

## Stack (laut composer.lock)

- PHP ^8.3, **Laravel 13.36** (in alten Commits steht „Laravel 11“, das stimmt nicht)
- Filament 5.10 (CMS unter `/admin`), Livewire 4 (E-008)
- Sanctum im SPA-Modus: Login per Session-Cookie, keine Tokens
- Spatie Permission (Rollen: admin, autor, tester, teacher, student), Spatie Translatable (de/en in JSON-Spalten)
- Lokal: Sail (`compose.yaml`, PHP 8.5) mit Postgres 18, Redis und Mailpit (alle Mails unter http://localhost:8025, E-026)

## Befehle (in WSL2, aus `backend/`)

```bash
cp .env.example .env                  # nur beim ersten Mal
./vendor/bin/sail up -d               # http://localhost, Filament: /admin
./vendor/bin/sail artisan key:generate   # nur beim ersten Mal
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan learnharder:import-legacy-content
./vendor/bin/sail artisan db:seed --class=DemoSchoolSeeder   # Demo-Klasse mit Lehrplan, nach dem Import
./vendor/bin/sail artisan test
./vendor/bin/sail pint
```

Der Import liest standardmäßig `/var/www/legacy-data`, das ist `_archiv/legacy/assets/data`, read-only gemountet (`compose.yaml`).
Fragen ohne gültige Lösung überspringt er mit Warnung. Themen, die schon Theorie oder Fragen haben, lässt er aus: Er überschreibt nichts aus dem CMS.
Der Seeder legt Rollen immer an, Demo-Accounts nur in `local`/`testing`. In Produktion den Admin per `php artisan learnharder:make-admin <email> --name="…"` anlegen (auch Notfallweg, siehe `infra/CLAUDE.md`). `make:filament-user` vergibt keine Rolle und reicht deshalb nicht.
Fehlt `vendor/`: einmalig `docker run --rm -v "$(pwd)":/app -w /app composer install`.
Die Tests laufen gegen die Datenbank `testing` (`phpunit.xml`). Andere Befehle für diese Datenbank nur mit `./vendor/bin/sail exec -T -e DB_DATABASE=testing laravel.test …`, nie mit `--env=testing`: Es gibt keine `.env.testing`, der Befehl trifft dann die Entwicklungs-DB.

## API (`routes/api.php`, alles unter `/api/v1`)

| Route | Zweck |
|---|---|
| `POST login`, `POST logout`, `GET me` | Login per Session, `me` liefert Gamer-Tag, eigene Klasse und Fortschritt (Level, Rang, Prestige, EP) |
| `GET subjects`, `GET subjects/{slug}` | sichtbare Fächer mit Themen, Fortschritt und Fach-Level (E-018) |
| `GET subjects/{slug}/topics/{slug}` | Thema mit Theorie als sicherem HTML (`Str::markdown` mit `html_input: escape`) |
| `POST topics/{id}/attempts` | Lerneinheit starten, Fragen ohne Lösung |
| `POST attempts/{id}/answers` | eine Antwort prüfen, nur die erste pro Frage zählt |
| `POST attempts/{id}/finish` | abschließen und EP vergeben, idempotent |

Alles außer `login` braucht `auth:sanctum` und ein nicht gesperrtes Konto (`EnsureAccountActive`, sonst 403). Sichtbar ist nur `published` (Thema und Frage) in einem aktiven Fach, sonst 404. Dazu filtert `Subject::visibleTo()`: Schüler:innen sehen den Lehrplan ihrer Klasse, admin, autor, tester und teacher alles (E-024). API-Tests mit Schüler:innen brauchen deshalb eine Klasse (`Tests\Concerns\EnrollsStudents`).

## Zuständigkeit

- `app/Models`, `app/Enums`, `app/Http`, `routes/` → diese Rolle
- `database/` → eigene Rolle, siehe `database/CLAUDE.md`
- `app/Filament/` → eigene Rolle, siehe `app/Filament/CLAUDE.md`

## Regeln

- Spiellogik (XP, Brainpower, Streaks, Trophäen) läuft **nur serverseitig**. Der Client schickt Antworten, der Server rechnet.
- Quiz-Abschlüsse brauchen einen Idempotency-Key, damit doppelte Requests nicht doppelt XP geben. Bei Lerneinheiten ist das die Attempt-ID.
- EP vergibt nur `QuizAttempt::finish()`: erst `lockForUpdate` auf `user_stats`, dann die Tagesregel (E-015, Tag in `Europe/Vienna`, `App\Support\DailyXp`). Level, Rang und Prestige rechnet `App\Support\Progression` aus den Gesamt-EP, gespeichert wird nur `total_xp`.
- API-Routen kommen nach `routes/api.php`, versioniert unter `/api/v1/…`.
- Die Vue-App holt vor dem Login `GET /sanctum/csrf-cookie` und schickt Cookies mit. Ihr Host muss in `SANCTUM_STATEFUL_DOMAINS` stehen, ihre Origin in `CORS_ALLOWED_ORIGINS` (`.env`).
- API-Antworten über Resources (`app/Http/Resources`). Über Schüler:innen nie E-Mail oder Klarnamen ausgeben, nur den Gamer-Tag.
- Feste Wertelisten als Enum in `app/Enums`. Ein Enum muss zur `CHECK`-Regel seiner Spalte passen (Test für alle Fälle).
- Zugriffsrechte über Policies, nicht über `if`-Abfragen im Controller. Admin darf alles (`Gate::before` im `AppServiceProvider`), nur fremde Lerneinheiten nicht beantworten oder abschließen. Inhalte erben von `app/Policies/ContentPolicy.php`.
- Konten sperren, entsperren, löschen und Rollen ändern nur über `App\Support\Accounts`. Dort stehen die Schutzregeln (E-027), denn eine Policy würde `Gate::before` für Admins überspringen. `logoutEverywhere()` löscht Sessions und erneuert das Remember-Token. Sanctum-Tokens gibt es nicht.
- Filament erlaubt eine Aktion, wenn die Policy-Methode fehlt. Neue Policies deshalb mit allen Filament-Aktionen anlegen, auch wenn sie nur `false` liefern.
- Jede neue Logik bekommt einen Feature-Test (`tests/Feature`), Tests vor jedem Commit grün.
- API-Tests mit zwei Personen: vor dem Wechsel `$this->flushSession()`. Sanctums `AuthenticateSession` merkt sich den Passwort-Hash der ersten Person in der Session und meldet sonst ab (401).
- Oberfläche und Meldungen nur auf Deutsch (`APP_LOCALE=de`, E-012). Laravels Texte liegen in `lang/de/`, ein Test prüft, dass gegenüber Laravel kein Schlüssel fehlt.
- Übersetzbare Models nutzen `HasTranslationsWithoutEmpty` statt `HasTranslations`: Leere Sprachfelder werden `NULL`, nicht `{"de":null}`.
- Code-Stil: `sail pint` vor jedem Commit. CI (`.github/workflows/ci.yml`) prüft `pint --test` und die Tests bei jedem Push.
