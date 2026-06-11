# Prompt für Claude Code: DBI-Migration einarbeiten

## Kontext

In `data/import/dbi/` liegen die **Originaldateien** des alten DBI-Lernhubs
(unveränderter Altbestand, nicht löschen):
- `data.json` — 21 Topics mit Theorie-Levels, Beispielen, Cheatsheets
- `quiz.json` — 24 open-answer Fragen, Drills, Schritte
- `sql-data.json` — SQL-Schemas (DDL) und Abfragen
- `testdata.json` — Testanalyse (10 Aufgaben mit Fehlern und Lösungen)

In `data/import/dbi/converted/` liegen die **bereits konvertierten Dateien**
im neuen Format, bereit zum Einarbeiten:
- 21× `dbi-<thema-id>_theorie.json`
- 21× `dbi-<thema-id>_quiz.json`
- `manifest_dbi_entry.json` — fertiger Manifest-Eintrag für DBI

---

## Aufgabe

### Schritt 1 — Konvertierte Dateien verschieben

Kopiere alle `dbi-*_theorie.json` und `dbi-*_quiz.json` aus
`data/import/dbi/converted/` nach `data/`.

Keine Umbenennung, keine Änderung am Inhalt.

### Schritt 2 — Manifest ergänzen

Öffne `data/manifest.json`. Füge den Inhalt aus
`data/import/dbi/converted/manifest_dbi_entry.json` als neuen Eintrag
im `faecher`-Array ein (alphabetisch nach `id` einordnen, also nach `dbi`).

Bestehende Einträge **nicht** verändern.

### Schritt 3 — dbi.html anlegen

Erstelle `dbi.html` analog zu den bestehenden Fach-Seiten (z.B. `pos.html`).
Einzige Unterschiede:
- `<title>DBI — LernHub</title>`
- Die `fachId`-Variable im inline-freien Script-Block: `const FACH_ID = 'dbi';`

Kein Inline-JS in HTML (CLAUDE.md-Regel).

### Schritt 4 — Hosting-Check

Prüfe ob folgende Punkte für einen echten Webserver erfüllt sind:

**Pfade:** Alle `fetch()`-Aufrufe in `js/app.js` und anderen JS-Dateien
verwenden absolute Pfade (z.B. `/data/dbi-erd-lesen_theorie.json`),
nicht relative. Falls relative Pfade gefunden werden: korrigieren.

**index.html:** Lädt `tokens.css` als erstes Stylesheet (CLAUDE.md-Regel —
wurde in einem früheren Fix bereits ergänzt, nur verifizieren).

**Keine hardcodierten `localhost`-Referenzen** in JS oder HTML.

### Schritt 5 — Verifizierung

Führe folgende Checks durch:
```
1. ls data/dbi-*.json | wc -l  → muss 42 ergeben (21 theorie + 21 quiz)
2. cat data/manifest.json | python3 -c "import json,sys; d=json.load(sys.stdin); print([f['id'] for f in d['faecher']])"
   → 'dbi' muss in der Liste sein
3. grep -r "localhost" js/ html *.html  → kein Treffer
4. head -5 index.html  → tokens.css vor style.css
```

---

## Commit (nach Verifikation)

```
feat: DBI-Fach aus altem Lernhub migriert (21 Themen, 42 JSON-Dateien)
```

---

## Hinweis: Quiz-Qualität

Die generierten `_quiz.json`-Dateien haben **funktionale, aber nicht optimale
Distraktoren** — die falschen Antworten wurden aus anderen Fragen desselben
Topics generiert. Das reicht für den Demo-Betrieb.

Für Produktionsqualität: Nutze `KI_VORLAGE.md` → **Prompt A (Migration)**,
um pro Thema bessere Multiple-Choice-Fragen zu generieren. Priorität:
Themen mit nur 1 Frage (chen-notation, erd-rekonstruieren, usw.).

---

## Hosting-Bereitschaft: Was noch fehlt

Für einen echten Server (nicht nur `npx serve`) müssen folgende Punkte
**manuell** entschieden werden — Claude Code kann diese nicht automatisch lösen:

| Thema | Problem | Lösung |
|---|---|---|
| Auth | `sessionStorage` + hardcoded Passwörter | Vor echtem Hosting: `.env` + Server-Side Auth |
| CORS | `fetch()` auf JSON-Dateien funktioniert lokal, auf manchen Servern nicht | Alle JSON über denselben Origin ausliefern (same-origin reicht) |
| HTTPS | `@view-transition` und `sessionStorage` funktionieren nur auf HTTPS oder localhost | SSL-Zertifikat auf dem Server |
| `data/` direkt aufrufbar | JSON-Dateien sind ohne Login per direkter URL erreichbar | Nginx/Apache-Regel: `data/` nur via App erlauben, nicht direkt |

Für **HTL-internes Hosting** (Schulserver, lokales Netz): `npx serve` oder
ein einfacher Nginx reicht. Die vier Punkte oben sind erst relevant wenn
die Site öffentlich erreichbar ist.
