# learnharder.app

Lern- und Gamification-Plattform für HTL-Schüler:innen: Theorie, Quizze, XP, Level und Brainpower.
Gerade läuft der Neubau (V2). Bis dahin zeigt die Domain eine Wartungsseite.

## Aufbau

| Ordner | Inhalt |
|---|---|
| `backend/` | Laravel 13 + Filament-CMS (`/admin`), lokal mit Sail |
| `frontend/` | Vue 3 + Vite PWA, Design-System in `src/styles/` |
| `infra/` | Hetzner-Server (Docker, Caddy, Backups), in Vorbereitung |
| `docs/` | Pläne und Entscheidungen, Einstieg: `docs/plans/README.md` |
| `public/` | Wartungsseite (Netlify, bis zum Umzug) |
| `_archiv/` | V1-Prototyp und V1-Material für den Umzug, nur lesen |

## Lokal starten (Windows → WSL2)

Das Repo gehört ins Linux-Dateisystem von Ubuntu-WSL2, nicht nach `C:\` (sonst ist Laravel im Container sehr langsam).
Voraussetzungen: Docker Desktop mit aktivierter WSL-Integration für Ubuntu, Node 22 in WSL (über nvm).

```bash
git clone https://github.com/patsche387421/learnharder.git ~/learnharder.app
cd ~/learnharder.app && git switch dev
git worktree add ../learnharder.app-main main    # Prod-Stand als zweiter Checkout
```

- **Backend:** Befehle in `backend/CLAUDE.md` (`.env` anlegen, `sail up -d`, Migrationen, Content-Import).
- **Frontend:** `npm --prefix frontend install && npm --prefix frontend run dev` → http://localhost:5173

### Prod-Stand prüfen (`~/learnharder.app-main`)

```bash
git pull
npm --prefix frontend ci && npm --prefix frontend run build && npm --prefix frontend run preview   # http://localhost:4173
```

Soll das Backend parallel zum Dev-Stack laufen, in `backend/.env` des Worktrees eigene Namen und Ports setzen:
`COMPOSE_PROJECT_NAME=learnharder-main`, `APP_PORT=8081`, `FORWARD_DB_PORT=5433`, `FORWARD_REDIS_PORT=6380`, `VITE_PORT=5175`.

Regeln für die Mitarbeit (Branches, Commits, Diff-Gates): siehe `CLAUDE.md`.
