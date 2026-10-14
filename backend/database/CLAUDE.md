# CLAUDE.md — backend/database/ (Rolle: Datenbank & Migration)

Schema, Migrationen, Seeder und Factories. Allgemeine Regeln: `../../CLAUDE.md`, Backend-Regeln: `../CLAUDE.md`.

## Schema (Stand 2026-10-14)

- **Inhalte:** `subjects` → `topics` → `theory_sections` / `questions` → `question_options` (UUID-Schlüssel, Texte als JSON `{"de": …, "en": …}`)
- **Gamification:** `user_stats`, `quiz_attempts`, `trophies`, `user_trophies`
- **Schule/Teams:** `schools`, `classes`, `class_enrollments`, `teams`, `team_members`
- **Benutzer:** `users` (bigint-ID) mit `gamer_tag`, `brainpower_current`/`_max`, `current_streak`

## Regeln

- **Bestehende Migrationen nie ändern**, sobald sie auf einem Server gelaufen sind. Änderungen immer als neue Migration.
- Jede Migration braucht ein funktionierendes `down()`.
- Lernverlauf der Schüler darf nicht durch CMS-Löschungen verschwinden: für Inhalte Soft Deletes oder `restrictOnDelete`, kein `cascadeOnDelete` auf Verlaufstabellen.
- Seeder mit Test-Accounts nur in `local`/`testing` ausführen (`app()->environment(...)` prüfen).
- Zieldatenbank: Postgres (Entscheidung offen, siehe `docs/ENTSCHEIDUNGEN.md`). Keine DB-spezifischen Features ohne Not.

## Bekannte Schema-Lücken

1. `quiz_attempts.topic_id` hat `cascadeOnDelete` → Topic löschen löscht den Verlauf.
2. `current_streak` liegt doppelt in `users` und `user_stats`.
3. Für die Brainpower-Regeneration fehlt ein Zeitstempel (z. B. `brainpower_refilled_at`).
4. `user_stats.trophies` (Währung) und die Tabelle `trophies` (Errungenschaften) tragen denselben Namen.
5. Es gibt kein Antwort-Log pro Frage und kein XP-Ereignis-Log (`xp_events`). Beides braucht man für Statistiken und Wiederholungen.

## Datenmigration aus V1

- Inhalte: Artisan-Command `learnharder:import-legacy-content` (Quelle `_archiv/legacy/assets/data`).
- Nutzer und Fortschritt: kommen aus Supabase (Schema in `_archiv/sql/migrations/`). Ein Import-Command fehlt noch. Supabase speichert bcrypt-Hashes, die Laravel prüfen kann.
