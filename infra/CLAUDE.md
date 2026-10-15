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

## Login über zwei Subdomains (vor M4)

Die App auf `app.learnharder.app` liest das Cookie `XSRF-TOKEN`, das die API auf `api.learnharder.app` setzt (Sanctum im SPA-Modus).
Das klappt nur, wenn das Cookie für die ganze Domain gilt. In der Produktions-`.env` des Backends:

```
SESSION_DOMAIN=.learnharder.app
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=app.learnharder.app
CORS_ALLOWED_ORIGINS=https://app.learnharder.app
```

Der Frontend-Build bekommt `VITE_API_URL=https://api.learnharder.app`.
Offen: Staging unter `staging.app…` und `staging.api…` läge ebenfalls unter `.learnharder.app`, die Cookies von Staging und Produktion würden sich dann überschreiben. Auch `.staging.learnharder.app` reicht nicht ganz: Der Browser schickt die Cookies von `.learnharder.app` dorthin mit, dann gibt es zwei `XSRF-TOKEN`. Vor M4 entscheiden, am sichersten ist eine eigene Domain für Staging.

## Regeln

- **Keine Secrets hier einchecken.** Nur `*.example`-Dateien. Echte Werte liegen auf dem Server.
- Jede Änderung am Server muss hier als Datei nachvollziehbar sein (kein Klicken ohne Doku).
- Agent führt **nie** selbst Befehle auf dem Server aus und ändert keine DNS-Einträge. Er bereitet Dateien und Runbooks vor, Patsche führt aus.
- Erst Staging, dann Produktion.

## Stand (2026-10-14)

Leer. Netlify liefert bis zur Umstellung die Wartungsseite aus `../public/`.
