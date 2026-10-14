# CLAUDE.md — backend/ (Rolle: Backend-Entwickler:in)

Laravel-API und Filament-CMS für learnharder.app. Allgemeine Regeln: siehe `../CLAUDE.md`.

## Stack (laut composer.lock)

- PHP ^8.3, **Laravel 13.35** (in alten Commits steht „Laravel 11“, das stimmt nicht)
- Filament 3.3 (CMS unter `/admin`), Livewire 3
- Sanctum (Token-Auth für das Vue-Frontend, **noch nicht eingerichtet**)
- Spatie Permission (Rollen: admin, autor, tester, teacher, student), Spatie Translatable (de/en in JSON-Spalten)
- Lokal: Sail (`compose.yaml`, PHP 8.5) mit Postgres 18 und Redis

## Befehle (in WSL2, aus `backend/`)

```bash
cp .env.example .env                  # nur beim ersten Mal
./vendor/bin/sail up -d               # http://localhost, Filament: /admin
./vendor/bin/sail artisan key:generate   # nur beim ersten Mal
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan learnharder:import-legacy-content --path=/var/www/legacy-data
./vendor/bin/sail artisan test
./vendor/bin/sail pint
```

`/var/www/legacy-data` ist `_archiv/legacy/assets/data`, read-only gemountet (`compose.yaml`).
Fehlt `vendor/`: einmalig `docker run --rm -v "$(pwd)":/app -w /app composer install`.

## Zuständigkeit

- `app/Models`, `app/Http`, `routes/` → diese Rolle
- `database/` → eigene Rolle, siehe `database/CLAUDE.md`
- `app/Filament/` → eigene Rolle, siehe `app/Filament/CLAUDE.md`

## Regeln

- Spiellogik (XP, Brainpower, Streaks, Trophäen) läuft **nur serverseitig**. Der Client schickt Antworten, der Server rechnet.
- Quiz-Abschlüsse brauchen einen Idempotency-Key, damit doppelte Requests nicht doppelt XP geben.
- API-Routen kommen nach `routes/api.php` (existiert noch nicht), versioniert unter `/api/v1/…`.
- Zugriffsrechte über Policies, nicht über `if`-Abfragen im Controller.
- Jede neue Logik bekommt einen Feature-Test (`tests/Feature`), Tests vor jedem Commit grün.
- Code-Stil: `sail pint` vor jedem Commit.

## Bekannte Baustellen (Stand 2026-10-14)

1. `DatabaseSeeder` legt `admin@learnharder.app` mit einem bekannten Passwort an → in Produktion verhindern.
2. Keine Policies: jede Rolle mit Panel-Zugang kann alles löschen.
3. `ImportLegacyContent`: Standardpfad zeigt noch auf `../src/assets/data` (liegt jetzt unter `../_archiv/legacy/assets/data`). Fehlende Lösung → Option 0 wird stillschweigend als richtig markiert.
4. `APP_LOCALE=en`, aber alle Inhalte sind nur auf Deutsch vorhanden.
