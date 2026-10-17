# CLAUDE.md — backend/database/ (Rolle: Datenbank & Migration)

Schema, Migrationen, Seeder und Factories. Allgemeine Regeln: `../../CLAUDE.md`, Backend-Regeln: `../CLAUDE.md`.

## Schema (Stand 2026-10-17)

- **Inhalte:** `subjects` → `topics` → `theory_sections` / `questions` → `question_options` (UUID-Schlüssel, Texte als JSON `{"de": …, "en": …}`)
- **Gamification:** `user_stats`, `quiz_attempts` (mit `finished_at`), `question_answers` (erste Antwort je Frage und Lerneinheit, mit `xp_awarded` und `xp_date`). `quiz_attempts.attempt_type` `standard` oder `challenge` (CHECK): Herausforderungen ohne `topic_id`, mit `question_ids`, `xp_bonus`, `trophies_earned` (E-045)
- **Erfolge (E-041):** `achievements` (Typ `rule` als CHECK = Enum `AchievementRule`, `threshold`, `trophy_reward`, `is_secret`, `is_active`) und `user_achievements` (Cascade am User, Restrict am Erfolg). `AchievementSeeder` legt den ersten Katalog in allen Umgebungen an (`firstOrCreate` nach `slug`, überschreibt nichts aus dem CMS)
- **Schule:** `schools` → `departments` → `programs` → `school_classes` (je Schuljahr) mit `class_enrollments` und `teaching_assignments`, dazu `school_email_domains`, `school_years`, `curriculum_entries`. Plan: `docs/plans/DATENMODELL_SCHULE.md`. Einschreibungen werden ausgetragen (`left_at`), nicht gelöscht. Demo-Daten: `DemoSchoolSeeder` (nur `local`/`testing`, nach dem Import), danach `TestPersonasSeeder`: acht Testpersonen (`<gamer-tag>@test.learnharder.app`) für Level, Prestige, Streak, Brainpower, Booster, Herausforderung, ohne Klasse und gesperrt, jeder Lauf setzt sie zurück. Mit Streak gibt es eine abgeschlossene Lerneinheit am letzten Lerntag (für `last_study_at` im Hub). Testnutzer:innen in Tests über die Bausteine der `UserFactory` (`inClass`, `level`, `xp`, `streak`, `brainpower`, `booster`) und `SchoolClassFactory`
- **Teams:** `teams`, `team_members`
- **Benutzer:** `users` (bigint-ID) mit `gamer_tag`, `brainpower_current`, `brainpower_refilled_on` (das Maximum rechnet der Server, E-036), `booster_multiplier` und `booster_ends_at` (nur der laufende Booster, kein Verlauf). `question_answers.xp_multiplier`: Faktor beim Antworten. Streak in `user_stats` (`current_streak`, `longest_streak`, `last_study_date` als Tag in Wien). `name` nur fürs Personal, Schüler:innen ohne Namen (E-025). Konten: `consented_at`, `terms_version`, `parental_consent_at`/`_by`, `deactivated_at`/`_by`, `deactivation_reason`, `last_login_at` (E-027, E-028)

## Regeln

- **Bestehende Migrationen nie ändern**, sobald sie auf einem Server gelaufen sind. Änderungen immer als neue Migration.
- Jede Migration braucht ein funktionierendes `down()`.
- Lernverlauf der Schüler darf nicht durch CMS-Löschungen verschwinden: für Inhalte Soft Deletes oder `restrictOnDelete`, kein `cascadeOnDelete` auf Verlaufstabellen.
- Seeder mit Test-Accounts nur in `local`/`testing` ausführen (`app()->environment(...)` prüfen).
- Zieldatenbank: Postgres (E-007 in `docs/ENTSCHEIDUNGEN.md`). Postgres-Features (`CHECK`, Teil-Indizes, `NULLS NOT DISTINCT`) per `DB::statement`, nur wo sie Datenregeln absichern. Tests dafür in `tests/Feature/Database`.

## Bekannte Schema-Lücken

1. Das Antwort-Log gibt es seit M3 (`question_answers`), ein XP-Ereignis-Log (`xp_events`) für EP außerhalb von Lerneinheiten noch nicht.

## Datenmigration aus V1

- Inhalte: Artisan-Command `learnharder:import-legacy-content` (Quelle `_archiv/legacy/assets/data`).
- Nutzer und Fortschritt: werden nicht übernommen, alle registrieren sich in V2 neu (E-011).
