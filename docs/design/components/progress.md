# Fortschrittspfad

**Typ:** Komponente
**Datei:** `progress.html`

## Kurzbeschreibung
Visualisierung des Lernfortschritts als Pfad/Stationen: erledigte, aktive
und gesperrte Themen mit Zustands-Icons und Verbindungslinien.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--surface`, `--surface-2` (Stations-Karten)
- `--primary` (aktive Station, Pfad-Linie)
- `--success` (erledigte Station)
- `--text-muted` (gesperrte Station)
- `--text`
- `--radius`

## Verwendet auf `src/`-Seiten
- `src/dashboard.html` (Gesamt-Übersicht)
- `src/pos.html`, `src/dbi.html` und weitere Fach-Themenübersichten
- `src/faecher.html` (zusammengefasst pro Fach)

## TODOs / Abweichungen vom aktuellen Stand
- Datenquelle: `thema_progress` aus Supabase (`src/js/stats.js`) — Felder
  prüfen, ob Status (`gesperrt`/`aktiv`/`abgeschlossen`) eindeutig ableitbar.
- Mobile-Variante (vertikaler Pfad statt horizontal) ist im Mockup
  nicht enthalten → später ergänzen.
