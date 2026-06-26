# Dokumentation — Wegweiser

Diese Datei erklärt, **wo welche Doku liegt**, damit Produkt-Wissen, Verlauf und
Agent-/Prozess-Artefakte sauber getrennt bleiben.

## `docs/` — Produkt- & Design-Doku (aktuell)

Die **gültige** Referenz zum laufenden Stand. Wird gepflegt, nicht archiviert.

- `DESIGN_GUIDELINES.md` — Design-System (Tokens, Dark/Light, Reset)
- `LEVEL_SYSTEM.md` — Level-, EP- und Energie-Logik
- `NAMENSKONVENTION.md` — Namens- & Begriffsregeln
- `KONZEPT_REITER.md`, `KONZEPT_TEAMS_SAISONS.md` — Feature-Konzepte
- `DATA_MIGRATION_V2.md` — aktueller Datenmigrations-Plan
- `IDEEN.md` — Backlog / Ideen
- `BUGS.md` — Bug-Tracker
- `design/` — Komponenten- & Seiten-Mockups (`components/`, `pages/`, `INBOX/`)

## `docs/archive/` — Überholtes (Verlauf)

Abgelöste Dokumente, die wir **zur Nachvollziehbarkeit** behalten, aber nicht
mehr pflegen.

- `DATENMIGRATION.md` — abgelöst durch `DATA_MIGRATION_V2.md`

## `agent/` — Agent-/Prozess-Artefakte (getrackt)

Arbeitsmaterial für die Entwicklung mit Claude Code. Versioniert, damit der
Projektverlauf nachvollziehbar bleibt.

- `CLAUDE.md` — verbindliche Projektregeln für den KI-Agenten
- `prompts/` — wiederverwendbare Prompt-Vorlagen (`KI_VORLAGE.md`, `MIGRATION_PROMPT.md`)
- `SESSION_REPORT.md` — fortlaufendes Session-Protokoll
