# CLAUDE.md — backend/ (Rolle: Backend-Entwickler:in)

Laravel-API und Filament-CMS für learnharder.app. Allgemeine Regeln: siehe `../CLAUDE.md`.
Hinweise zu Laravel Boost (Agent-Setup): siehe `AGENTS.md`.

## Stack (laut composer.lock)

- PHP ^8.3, **Laravel 13.35** (in alten Commits steht „Laravel 11“, das stimmt nicht)
- Filament 3.3 (CMS unter `/admin`), Livewire 3
- Sanctum (Token-Auth für das Vue-Frontend, **noch nicht eingerichtet**)
- Spatie Permission (Rollen: admin, autor, tester, teacher, student), Spatie Translatable (de/en in JSON-Spalten)
- Lokal: Sail (`compose.yaml`) mit Postgres 18 und Redis. `.env.example` steht noch auf SQLite.

## Zuständigkeit

- `app/Models`, `app/Http`, `routes/` → diese Rolle
- `database/` → eigene Rolle, siehe `database/CLAUDE.md`
- `app/Filament/` → eigene Rolle, siehe `app/Filament/CLAUDE.md`

## Regeln

- Spiellogik (XP, Brainpower, Streaks, Trophäen) läuft **nur serverseitig**. Der Client schickt Antworten, der Server rechnet.
- Quiz-Abschlüsse brauchen einen Idempotency-Key, damit doppelte Requests nicht doppelt XP geben.
- API-Routen kommen nach `routes/api.php` (existiert noch nicht), versioniert unter `/api/v1/…`.
- Zugriffsrechte über Policies, nicht über `if`-Abfragen im Controller.
- Jede neue Logik bekommt einen Feature-Test (`tests/Feature`). Ausführen: `php artisan test`.
- Code-Stil: `vendor/bin/pint` vor jedem Commit.

## Bekannte Baustellen (Stand 2026-10-14)

1. `DatabaseSeeder` legt `admin@learnharder.app` mit einem bekannten Passwort an → in Produktion verhindern.
2. Keine Policies: jede Rolle mit Panel-Zugang kann alles löschen.
3. `ImportLegacyContent`: Standardpfad zeigt noch auf `../src/assets/data` (liegt jetzt unter `../_archiv/legacy/assets/data`). Fehlende Lösung → Option 0 wird stillschweigend als richtig markiert.
4. `APP_LOCALE=en`, aber alle Inhalte sind nur auf Deutsch vorhanden.
