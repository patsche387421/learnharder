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

## Lokal starten

```bash
# Backend
cd backend && ./vendor/bin/sail up -d && ./vendor/bin/sail artisan migrate --seed

# Frontend
npm --prefix frontend install && npm --prefix frontend run dev
```

Regeln für die Mitarbeit (Branches, Commits, Diff-Gates): siehe `CLAUDE.md`.
