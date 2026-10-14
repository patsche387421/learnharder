# CLAUDE.md — agent/ (Arbeitsbereich des KI-Agenten)

Hier liegt, was der Agent für die Zusammenarbeit braucht: Session-Protokoll, Prompt-Vorlagen
und der Plan für spezialisierte Subagents. Allgemeine Regeln: `../CLAUDE.md`.

## Inhalt

```
SESSION_REPORT.md   fortlaufendes Protokoll, nur anhängen
prompts/            wiederverwendbare Prompt-Vorlagen (entstehen bei Bedarf)
```

Das V1-Protokoll und die V1-Prompts liegen unter `_archiv/agent/`.

## Ablauf einer Session

1. `../CLAUDE.md`, die CLAUDE.md des betroffenen Ordners und `docs/plans/README.md` lesen.
2. Branch prüfen (`dev` oder `feat/…`).
3. In kleinen Schritten arbeiten. Jeder Schritt: Ankündigung → Änderung → roher Diff → OK → Commit.
4. Am Ende: Eintrag in `SESSION_REPORT.md` (Datum, Branch, Commits, Ergebnis, offene Punkte).

## Subagent-Roster

Subagents liegen in `.claude/agents/`, ihre Skills in `.claude/skills/` (beide versioniert).
Jede Rolle entspricht einer CLAUDE.md. Ein Subagent bekommt diese Datei plus die Root-CLAUDE.md als Kontext,
darf nur in seinem Bereich schreiben und committet nie selbst (Diff-Gate bleibt beim Hauptagenten).

| Subagent | Modell | Kontext | Darf schreiben in | Typische Aufgabe |
|---|---|---|---|---|
| `doku` ✅ | haiku | Skill `doku-pflegen` | CLAUDE.md-Dateien, `docs/`, `agent/SESSION_REPORT.md`, `README.md` | Doku mit Code abgleichen, Session-Report |

Geplant (Datei entsteht, wenn die Rolle gebraucht wird):

| Subagent | Kontext | Darf schreiben in | Typische Aufgabe |
|---|---|---|---|
| `backend-dev` | `backend/CLAUDE.md` | `backend/app`, `backend/routes`, `backend/tests` | API-Endpunkte, Spiellogik, Policies |
| `db-architect` | `backend/database/CLAUDE.md` | `backend/database` | Migrationen, Seeder, Import-Commands |
| `cms-dev` | `backend/app/Filament/CLAUDE.md` | `backend/app/Filament` | Redaktionsoberfläche |
| `frontend-dev` | `frontend/CLAUDE.md` | `frontend/src` | Screens, Stores, API-Client |
| `design-system` | `frontend/src/styles/CLAUDE.md` | `frontend/src/styles` | Tokens, `lh-`-Komponenten, Kontrast |
| `devops` | `infra/CLAUDE.md` | `infra/` | Docker, Caddy, Backups, Runbooks |
| `pm` | `docs/CLAUDE.md` | `docs/` | Plan-Abgleich, Entscheidungslog, Meilensteine |
| `reviewer` | alle CLAUDE.md | nichts (nur lesen) | Diff-Review vor jedem Merge nach `dev` |
