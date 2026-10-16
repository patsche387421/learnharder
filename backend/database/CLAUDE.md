# CLAUDE.md — backend/database/ (Rolle: Datenbank & Migration)

Schema, Migrationen, Seeder und Factories. Allgemeine Regeln: `../../CLAUDE.md`, Backend-Regeln: `../CLAUDE.md`.

## Schema (Stand 2026-10-16)

- **Inhalte:** `subjects` → `topics` → `theory_sections` / `questions` → `question_options` (UUID-Schlüssel, Texte als JSON `{"de": …, "en": …}`)
- **Gamification:** `user_stats`, `quiz_attempts` (mit `finished_at`), `question_answers` (erste Antwort je Frage und Lerneinheit, mit `xp_awarded` und `xp_date`), `trophies`, `user_trophies`
- **Schule:** `schools` → `departments` → `programs` → `school_classes` (je Schuljahr) mit `class_enrollments` und `teaching_assignments`, dazu `school_email_domains`, `school_years`, `curriculum_entries`. Plan: `docs/plans/DATENMODELL_SCHULE.md`. Einschreibungen werden ausgetragen (`left_at`), nicht gelöscht. Demo-Daten: `DemoSchoolSeeder` (nur `local`/`testing`, nach dem Import)
- **Teams:** `teams`, `team_members`
- **Benutzer:** `users` (bigint-ID) mit `gamer_tag`, `brainpower_current`/`_max`, `current_streak`, `deactivated_at`

## Regeln

- **Bestehende Migrationen nie ändern**, sobald sie auf einem Server gelaufen sind. Änderungen immer als neue Migration.
- Jede Migration braucht ein funktionierendes `down()`.
- Lernverlauf der Schüler darf nicht durch CMS-Löschungen verschwinden: für Inhalte Soft Deletes oder `restrictOnDelete`, kein `cascadeOnDelete` auf Verlaufstabellen.
- Seeder mit Test-Accounts nur in `local`/`testing` ausführen (`app()->environment(...)` prüfen).
- Zieldatenbank: Postgres (E-007 in `docs/ENTSCHEIDUNGEN.md`). Postgres-Features (`CHECK`, Teil-Indizes, `NULLS NOT DISTINCT`) per `DB::statement`, nur wo sie Datenregeln absichern. Tests dafür in `tests/Feature/Database`.

## Bekannte Schema-Lücken

1. `current_streak` liegt doppelt in `users` und `user_stats`.
2. Für die Brainpower-Regeneration fehlt ein Zeitstempel (z. B. `brainpower_refilled_at`).
3. `user_stats.trophies` (Währung) und die Tabelle `trophies` (Errungenschaften) tragen denselben Namen.
4. Das Antwort-Log gibt es seit M3 (`question_answers`), ein XP-Ereignis-Log (`xp_events`) für EP außerhalb von Lerneinheiten noch nicht.

## Datenmigration aus V1

- Inhalte: Artisan-Command `learnharder:import-legacy-content` (Quelle `_archiv/legacy/assets/data`).
- Nutzer und Fortschritt: werden nicht übernommen, alle registrieren sich in V2 neu (E-011).
