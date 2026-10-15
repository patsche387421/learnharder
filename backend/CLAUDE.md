# CLAUDE.md — backend/ (Rolle: Backend-Entwickler:in)

Laravel-API und Filament-CMS für learnharder.app. Allgemeine Regeln: siehe `../CLAUDE.md`.

## Stack (laut composer.lock)

- PHP ^8.3, **Laravel 13.36** (in alten Commits steht „Laravel 11“, das stimmt nicht)
- Filament 5.10 (CMS unter `/admin`), Livewire 4 (E-008)
- Sanctum im SPA-Modus: Login per Session-Cookie, keine Tokens (`/api/v1/login`, `logout`, `me`)
- Spatie Permission (Rollen: admin, autor, tester, teacher, student), Spatie Translatable (de/en in JSON-Spalten)
- Lokal: Sail (`compose.yaml`, PHP 8.5) mit Postgres 18 und Redis

## Befehle (in WSL2, aus `backend/`)

```bash
cp .env.example .env                  # nur beim ersten Mal
./vendor/bin/sail up -d               # http://localhost, Filament: /admin
./vendor/bin/sail artisan key:generate   # nur beim ersten Mal
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan learnharder:import-legacy-content
./vendor/bin/sail artisan test
./vendor/bin/sail pint
```

Der Import liest standardmäßig `/var/www/legacy-data`, das ist `_archiv/legacy/assets/data`, read-only gemountet (`compose.yaml`).
Fragen ohne gültige Lösung überspringt er mit Warnung.
Der Seeder legt Rollen immer an, Demo-Accounts nur in `local`/`testing`. In Produktion den Admin per `php artisan make:filament-user` anlegen.
Fehlt `vendor/`: einmalig `docker run --rm -v "$(pwd)":/app -w /app composer install`.

## Zuständigkeit

- `app/Models`, `app/Enums`, `app/Http`, `routes/` → diese Rolle
- `database/` → eigene Rolle, siehe `database/CLAUDE.md`
- `app/Filament/` → eigene Rolle, siehe `app/Filament/CLAUDE.md`

## Regeln

- Spiellogik (XP, Brainpower, Streaks, Trophäen) läuft **nur serverseitig**. Der Client schickt Antworten, der Server rechnet.
- Quiz-Abschlüsse brauchen einen Idempotency-Key, damit doppelte Requests nicht doppelt XP geben.
- API-Routen kommen nach `routes/api.php`, versioniert unter `/api/v1/…`.
- Die Vue-App holt vor dem Login `GET /sanctum/csrf-cookie` und schickt Cookies mit. Ihr Host muss in `SANCTUM_STATEFUL_DOMAINS` stehen, ihre Origin in `CORS_ALLOWED_ORIGINS` (`.env`).
- API-Antworten über Resources (`app/Http/Resources`). Über Schüler:innen nie E-Mail oder Klarnamen ausgeben, nur den Gamer-Tag.
- Feste Wertelisten als Enum in `app/Enums`. Ein Enum muss zur `CHECK`-Regel seiner Spalte passen (Test für alle Fälle).
- Zugriffsrechte über Policies, nicht über `if`-Abfragen im Controller. Admin darf alles (`Gate::before` im `AppServiceProvider`), Inhalte erben von `app/Policies/ContentPolicy.php`.
- Filament erlaubt eine Aktion, wenn die Policy-Methode fehlt. Neue Policies deshalb mit allen Filament-Aktionen anlegen, auch wenn sie nur `false` liefern.
- Jede neue Logik bekommt einen Feature-Test (`tests/Feature`), Tests vor jedem Commit grün.
- Code-Stil: `sail pint` vor jedem Commit. CI (`.github/workflows/ci.yml`) prüft `pint --test` und die Tests bei jedem Push.

## Bekannte Baustellen (Stand 2026-10-15)

1. `APP_LOCALE=en`, aber alle Inhalte sind nur auf Deutsch vorhanden.
