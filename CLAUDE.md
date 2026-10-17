# CLAUDE.md — learnharder.app (V2)

Lern- und Gamification-Plattform für HTL-Schüler:innen. Seit Oktober 2026 wird die App neu gebaut:
**Laravel + Filament (Backend/CMS) und Vue 3 PWA (Frontend) auf einem Hetzner-VPS (EU).**
Der alte Vanilla-JS/Supabase-Prototyp liegt nur noch im Archiv.

## Wo ist die Wahrheit?

| Thema | Quelle |
|---|---|
| Was wir bauen und in welcher Reihenfolge | `docs/plans/LEARNHARDER_V2_PROJEKTPLAN_PSP_RISIKEN.md` |
| Was offen ist und wer dran ist | `docs/AUFGABEN.md` (auch im Tab „Projekt“ der Testzentrale) |
| Hosting, Domains, Datenmigration, Versionen | `docs/plans/SETUP_DOMAIN_UND_DATENBANK_MIGRATION.md` |
| XP, Level, Brainpower, Streaks | `docs/plans/GAMIFICATION_OEKONOMIE_MODELL.md` |
| Getroffene und offene Entscheidungen | `docs/ENTSCHEIDUNGEN.md` |
| Design (Tokens, Komponenten) | `frontend/src/styles/`, Regeln und Sprache in `docs/design/` |
| Code | Git. Doku kann veralten, Code nicht. |

## Ordner

```
backend/     Laravel-API + Filament-CMS (PHP)         → backend/CLAUDE.md
frontend/    Vue 3 + Vite PWA                          → frontend/CLAUDE.md
infra/       Hetzner-Server, Docker, Caddy, Backups    → infra/CLAUDE.md
docs/        Pläne, Entscheidungen                     → docs/CLAUDE.md
agent/       Arbeitsbereich des KI-Agenten             → agent/CLAUDE.md
scripts/     lokale Testwerkzeuge (dev.py)             → README.md
.claude/     agents/ + skills/ (versioniert), launch.json; Rest lokal
.github/     CI bei jedem Push: Backend (Pint, Tests gegen Postgres), Frontend (Tokens, Tests, Build)
public/      Wartungsseite (live über Netlify, bis Hetzner steht). Hier KEINE CLAUDE.md, sonst wird sie öffentlich ausgeliefert.
_archiv/     V1-Prototyp und V1-Material für den Umzug. Nur lesen. → _archiv/CLAUDE.md
```

Jede `CLAUDE.md` in einem Unterordner beschreibt eine **Rolle**. Daraus werden später Subagents
(siehe `agent/CLAUDE.md` → „Subagent-Roster“).

## Lokal entwickeln (WSL2)

Das Repo liegt im Ubuntu-WSL2-Dateisystem (E-005, Umzug abgeschlossen Stand 2026-10-15). Einrichtung: `README.md`.
`C:\v\learnharder.app` ist nur noch Archiv, bis V1 abgeschaltet ist (E-011): dort nichts lesen, nichts ändern, keinen Sail-Stack starten.

| Checkout | Branch | Zweck |
|---|---|---|
| `~/learnharder.app` | `dev`, `feat/…` | Entwicklung |
| `~/learnharder.app-main` | `main` | Git-Worktree: Prod-Stand bauen und prüfen. Dort nur mergen, nicht entwickeln |

Befehle: `backend/CLAUDE.md` und `frontend/CLAUDE.md`. Auf dem Host gibt es kein PHP, alles läuft über Sail.

## Arbeitsregeln (verbindlich)

1. **Branches:** Immer auf `dev` arbeiten. Große Features und Umbauten auf `feat/<name>` (Umbauten auch `chore/<name>`), danach zurück nach `dev`. `main` nur, wenn Patsche „deployen“ sagt.
2. **Commits:** Conventional Commits **auf Deutsch** (`feat`, `fix`, `docs`, `style`, `refactor`, `chore`, `test`), **ohne** `Co-Authored-By`-Zeile. Ein Commit pro logischem Schritt.
3. **Diff-Gate:** Erst ändern → rohen Diff zeigen → explizites OK → erst dann `git add`/Commit. Nie vorher stagen (auch kein `git mv`; Verschieben per `mv`).
4. **Tempo:** Kleine Schritte, eine Entscheidung pro Rückfrage. Vor jeder Änderung ein Satz: was und warum.
5. **Scope:** Nur Dateien anfassen, die zur Aufgabe gehören. Keine „hilfreichen“ Extras.
6. **Session-Ende:** `docs/AUFGABEN.md` abgleichen (Erledigtes raus, Neues rein), dann Eintrag in `agent/SESSION_REPORT.md` anhängen (nie überschreiben).
7. **Sicherheit:** Keine Secrets im Repo. `.env`-Dateien bleiben ungetrackt. Keine bekannten Passwörter in Seedern, die in Produktion laufen könnten.
8. **Datenschutz:** Schülerdaten sind von Minderjährigen. Öffentlich nur Gamer-Tags, keine Klarnamen. Keine externen CDNs oder Fonts ohne Freigabe.

## Sprache

Doku, Kommentare und Commits auf Deutsch. Code-Bezeichner (Klassen, Spalten, Routen) auf Englisch, wie es in Laravel und Vue üblich ist.
