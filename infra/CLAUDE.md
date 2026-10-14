# CLAUDE.md — infra/ (Rolle: Betrieb / DevOps)

Alles, was den Hetzner-Server betrifft. Allgemeine Regeln: `../CLAUDE.md`.
Fachliche Grundlage: `docs/plans/SETUP_DOMAIN_UND_DATENBANK_MIGRATION.md` §3.

## Zielbild

Ein Hetzner-Cloud-VPS in der EU, Dienste in Docker, Caddy als Reverse Proxy mit automatischem TLS:

| Host | Dienst |
|---|---|
| `learnharder.app` | Landing Page (zuerst statisch, später Astro) |
| `app.learnharder.app` | Vue-PWA (statischer Build) |
| `api.learnharder.app` | Laravel (PHP-FPM) + Filament `/admin` |
| `ws.learnharder.app` | Laravel Reverb (erst Phase 2) |

Dazu Postgres, Redis, ein Queue-Worker und der Laravel-Scheduler (Brainpower-Regeneration, Streak-Reset).

## Geplanter Inhalt dieses Ordners

```
docker-compose.prod.yml   Produktions-Stack (nicht die Sail-compose.yaml)
Caddyfile
backup/                   tägliche DB-Dumps, offsite (S3-kompatibel)
README.md                 Runbook: Server aufsetzen, deployen, zurückrollen
```

## Regeln

- **Keine Secrets hier einchecken.** Nur `*.example`-Dateien. Echte Werte liegen auf dem Server.
- Jede Änderung am Server muss hier als Datei nachvollziehbar sein (kein Klicken ohne Doku).
- Agent führt **nie** selbst Befehle auf dem Server aus und ändert keine DNS-Einträge. Er bereitet Dateien und Runbooks vor, Patsche führt aus.
- Erst Staging, dann Produktion.

## Stand (2026-10-14)

Leer. Netlify liefert bis zur Umstellung die Wartungsseite aus `../public/`.
