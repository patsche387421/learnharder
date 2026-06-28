# KI-Vorlage: Lerninhalte erstellen, migrieren und planen

Diese Datei enthält **drei fertige Prompts** zum Kopieren. Ersetze die
Platzhalter in `<GROSSBUCHSTABEN>` und füge die Ausgabe direkt ins Projekt ein.

---

## Welchen Prompt brauchst du?

| Situation                                       | Prompt |
|-------------------------------------------------|--------|
| Alten LernHub-Inhalt auf neue Struktur umbauen  | → [Prompt A](#prompt-a-migration)  |
| Komplett neues Thema anlegen (Theorie + Quiz)   | → [Prompt B](#prompt-b-neues-standard-thema) |
| Neues interaktives Tool-Thema anlegen           | → [Prompt C](#prompt-c-neues-tool-thema)     |
| DB-Anbindung für ein Fach planen                | → [Prompt D](#prompt-d-db-anbindung-planen)  |

---

## Hintergrund: Projektstruktur (kurz)

Die Navigation hat 5 Ebenen:

```
Login → Dashboard → Fächerübersicht → Fach-Seite → fach.html?fach=<thema-id>
```

`data/manifest.json` ist die **einzige** Quelle für Fächer und Themen.
Jeder Eintrag im Manifest benötigt passende JSON-Dateien in `data/`.

**Standard-Thema:** braucht `<thema-id>_theorie.json` + `<thema-id>_quiz.json`
**Tool-Thema:** braucht `toolScript` (.js), `toolStyle` (.css), `toolData` (.json), `toolQuiz` (.json)

---

## Prompt A — Migration

> Verwende diesen Prompt, wenn du Inhalte aus einer alten LernHub-Version hast
> (z. B. flache Fach-Struktur ohne verschachtelte Themen) und sie in die neue
> Struktur überführen willst.

```text
Du hilfst mir, alten LernHub-Inhalt in eine neue JSON-Struktur zu migrieren.

## Alte Struktur (was ich habe)

<HIER DEINEN ALTEN INHALT EINFÜGEN – z. B. alte manifest.json, alte JSON-Dateien
oder auch Freitext/Stichpunkte, was das Fach enthält>

## Neue Manifest-Struktur (Ziel)

Ein Fach-Eintrag in data/manifest.json sieht jetzt so aus:

{
  "id": "<fach-id>",
  "name": "<Kürzel>",
  "vollname": "<Ausgeschriebener Name>",
  "icon": "<Emoji>",
  "beschreibung": "<1-2 Sätze>",
  "seite": "/<fach-id>.html",
  "themen": [
    {
      "id": "<fach-id>-<thema-slug>",
      "name": "<Themenname>",
      "icon": "<Emoji>",
      "beschreibung": "<1 Satz>",
      "typ": "fach"
    }
  ]
}

## Themen-Datei-Struktur (Ziel)

Für jedes Thema mit "typ": "fach" brauche ich zwei Dateien:

data/<thema-id>_theorie.json:
{
  "$schema": "./schema/theorie.schema.json",
  "fach": "<Kürzel>",
  "themen": [
    { "titel": "<Überschrift>", "text": "<Fließtext, 2-4 Sätze>" }
  ]
}

data/<thema-id>_quiz.json:
{
  "$schema": "./schema/quiz.schema.json",
  "fach": "<Kürzel>",
  "fragen": [
    {
      "frage": "<Fragetext>",
      "optionen": ["<A>", "<B>", "<C>", "<D>"],
      "loesung": 0
    }
  ]
}

## Aufgabe

1. Analysiere meinen alten Inhalt.
2. Schlage eine sinnvolle Themen-Aufteilung vor (3-6 Themen pro Fach).
3. Gib aus:
   a) Den aktualisierten Manifest-Eintrag (als JSON-Block, bereit zum Einfügen).
   b) Für jedes Thema je eine _theorie.json und eine _quiz.json.
4. Gib EXAKT die Code-Blöcke zurück, keinen anderen Text außer kurzen Dateinamen
   als Überschrift vor jedem Block.

Regeln für alle JSON-Dateien:
- Gültiges JSON, UTF-8, doppelte Anführungszeichen, kein Komma nach letztem Element.
- Inhalte auf Deutsch, sachlich korrekt.
- Die "$schema"-Zeile exakt wie oben übernehmen.
- Themen-IDs: nur Kleinbuchstaben, Ziffern, Bindestriche (z.B. "pos-schleifen").
- "loesung" in quiz.json: 0-basierter Index auf die richtige Antwort.
- Pro Thema: 3-5 Theorieblöcke, 4-6 Quizfragen.
```

---

## Prompt B — Neues Standard-Thema

> Verwende diesen Prompt für ein neues Thema mit Theorie-Text und Quiz.
> Danach: Manifest-Eintrag ergänzen und `npx serve` starten.

```text
Erzeuge Lerninhalte für ein neues Thema einer HTL-Lernwebsite.

FACH-KÜRZEL:  <z.B. DBI>
FACH-VOLLNAME: <z.B. Datenbanken und Informationssysteme>
THEMA:        <z.B. "SQL Grundlagen – SELECT, WHERE, JOIN">
THEMA-ID:     <z.B. dbi-sql-grundlagen>   (nur a-z, 0-9, Bindestrich)
ZIELGRUPPE:   HTL-Schüler:innen, ca. 16-18 Jahre

Gib EXAKT zwei Code-Blöcke zurück. Vor jedem Block steht der Dateiname als
einfache Zeile (kein Markdown-Heading), sonst kein Text.

--- Datei 1: data/<THEMA-ID>_theorie.json ---
{
  "$schema": "./schema/theorie.schema.json",
  "fach": "<FACH-KÜRZEL>",
  "themen": [
    { "titel": "<Überschrift>", "text": "<Fließtext, 2-4 Sätze>" }
  ]
}
Regeln:
- 3 bis 5 Themen-Blöcke.
- "titel" und "text" dürfen nicht leer sein.
- KEINE weiteren Felder außer "titel" und "text".

--- Datei 2: data/<THEMA-ID>_quiz.json ---
{
  "$schema": "./schema/quiz.schema.json",
  "fach": "<FACH-KÜRZEL>",
  "fragen": [
    {
      "frage": "<Fragetext>",
      "optionen": ["<A>", "<B>", "<C>", "<D>"],
      "loesung": 0
    }
  ]
}
Regeln:
- 4 bis 6 Fragen.
- "optionen": mindestens 2, idealerweise 4 Antworten, alle nicht leer.
- "loesung": GANZZAHL, 0-basierter Index in "optionen" auf die RICHTIGE Antwort.
- KEINE weiteren Felder außer "frage", "optionen", "loesung".

Allgemeine Regeln für beide Dateien:
- Gültiges JSON, UTF-8, doppelte Anführungszeichen, kein Komma nach dem letzten Element.
- Inhalte auf Deutsch, sachlich korrekt, altersgerecht.
- Die "$schema"-Zeile exakt wie oben übernehmen.
```

**Nach der KI-Ausgabe:**

1. Dateien als `data/<thema-id>_theorie.json` und `data/<thema-id>_quiz.json` speichern.
2. In `data/manifest.json` beim passenden Fach in `themen` eintragen:

```json
{
  "id": "<thema-id>",
  "name": "<Themenname>",
  "icon": "📘",
  "beschreibung": "<1 Satz>",
  "typ": "fach"
}
```

3. `npx serve` → Thema erscheint automatisch in der Fach-Übersicht.

---

## Prompt C — Neues Tool-Thema

> Verwende diesen Prompt, wenn du ein interaktives Lern-Tool brauchst
> (wie das POS-Datenstrukturen-Tool). Tool-Themen haben eigenes JS + CSS.

```text
Erzeuge die Daten-Dateien für ein interaktives Lern-Tool auf einer HTL-Website.

FACH-KÜRZEL: <z.B. NSVS>
THEMA:       <z.B. "OSI-Schichtenmodell interaktiv">
THEMA-ID:    <z.B. nsvs-osi>   (nur a-z, 0-9, Bindestrich)

Das Tool lädt zwei JSON-Dateien:
- <thema-id>_data.json   → strukturierte Lerndaten (Schichten, Konzepte, …)
- <thema-id>_quiz.json   → Quiz-Fragen zum Tool-Thema

Gib EXAKT zwei Code-Blöcke zurück. Vor jedem Block der Dateiname als einfache Zeile.

--- Datei 1: data/<THEMA-ID>_data.json ---
Entwerfe eine sinnvolle JSON-Struktur, die die Kerninhalte des Themas
als interaktiv erkundbare Datenpunkte repräsentiert.
Beispiel für OSI: Array von Schicht-Objekten mit id, name, nummer, beschreibung,
beispiele (Array von Strings), protokolle (Array von Strings).
Wähle die Struktur passend zum Thema.

--- Datei 2: data/<THEMA-ID>_quiz.json ---
{
  "$schema": "./schema/quiz.schema.json",
  "fach": "<FACH-KÜRZEL>",
  "fragen": [
    {
      "frage": "<Fragetext>",
      "optionen": ["<A>", "<B>", "<C>", "<D>"],
      "loesung": 0
    }
  ]
}
Regeln:
- 4 bis 6 Fragen, spezifisch zum Tool-Thema.
- "loesung": 0-basierter Index auf die richtige Antwort.

Allgemeine Regeln:
- Gültiges JSON, UTF-8, kein Komma nach letztem Element.
- Inhalte auf Deutsch, sachlich korrekt.
```

**Nach der KI-Ausgabe** musst du noch manuell:

1. `js/<thema-id>.js` anlegen (IIFE, exportiert `window.<ModulName>` mit `.mount({root, dataUrl, quizUrl})`).
2. `css/<thema-id>.css` anlegen (alle Regeln unter `.scope-klasse { … }` scopen).
3. Manifest-Eintrag als `"typ": "tool"` mit allen vier `tool*`-Feldern ergänzen.

Orientiere dich dabei an `js/pos-datenstrukturen.js` und `css/pos-datenstrukturen.css`.

---

## Prompt D — DB-Anbindung planen

> **Hinweis (überholt):** Die DB-Anbindung ist bereits über **Supabase**
> umgesetzt (siehe Abschnitt „Backend (Supabase)" in `CLAUDE.md`). Die
> Statistik-Funktionen in `js/stats.js` sind keine Platzhalter mehr. Dieser
> Prompt beschreibt einen alternativen, **nicht** verwendeten Express/.NET-Ansatz
> und ist nur noch als historische Referenz erhalten.

```text
Ich baue eine HTL-Lernwebsite (Vanilla JS, kein Framework) und möchte die
Statistik-Funktionen für ein Fach an eine echte Datenbank anbinden.

## Aktueller Zustand

- js/stats.js enthält drei Platzhalter-Funktionen (geben Dummy-Werte zurück):
  - ladeFachStats(fachId)       → { fortschritt, themenBearbeitet, quizPunkte, letzteAktivitaet }
  - ladeThemaStats(themaId)     → { abgeschlossen, quizPunkte, letzterScore }
  - speichereQuizErgebnis(themaId, richtig, gesamt)  → void
- js/auth.js enthält Login via sessionStorage (kein echtes Token).
- HTML-Elemente tragen data-stat-Attribute als DOM-Hooks.

## Fach, das ich anbinden will

FACH-ID:       <z.B. pos>
FACH-VOLLNAME: <z.B. Programmieren und Software-Engineering>
THEMEN-IDs:    <kommasepariert, z.B. pos-datenstrukturen, pos-variablen>

## Gewünschter Backend-Stack

BACKEND: <z.B. Node.js + Express ODER .NET 8 Web API>
DB:      <z.B. PostgreSQL ODER SQLite>

## Aufgabe

Erstelle einen konkreten Migrationsplan in 5 Phasen:

Phase 1 – Backend-Grundstruktur
  - Welche Dateien/Module anlegen?
  - Welche npm-Pakete (Node) bzw. NuGet-Pakete (.NET) installieren?

Phase 2 – Auth-Integration
  - Exakter Ersatz-Code für js/auth.js login() und neue headers()-Funktion.
  - Wie wird der JWT im sessionStorage gespeichert?

Phase 3 – Stats lesen (GET-Endpunkte)
  - Exakter API-Endpunkt mit Response-Schema für ladeFachStats("<FACH-ID>").
  - Exakter Ersatz-Code für stats.js ladeFachStats().

Phase 4 – Stats schreiben (POST-Endpunkt)
  - Exakter API-Endpunkt mit Request/Response-Schema für speichereQuizErgebnis().
  - Exakter Ersatz-Code für stats.js speichereQuizErgebnis().

Phase 5 – Dashboard
  - Neuer Endpunkt GET /api/v1/stats/dashboard (aggregiert über alle Fächer).
  - Neuer Funktions-Stub Stats.ladeDashboardStats() für js/stats.js.
  - Wie wird dashboard.html damit befüllt?

Regeln:
- Kein Inline-JS in HTML.
- Keine API-Keys im Frontend-Code.
- Alle Frontend-Änderungen nur in js/auth.js und js/stats.js.
- Antwort auf Deutsch, Code-Blöcke mit Sprach-Tag (```js, ```sql, usw.).
```

---

## Leere Vorlagen (für manuelles Schreiben)

**`data/<thema-id>_theorie.json`**
```json
{
  "$schema": "./schema/theorie.schema.json",
  "fach": "",
  "themen": [
    { "titel": "", "text": "" }
  ]
}
```

**`data/<thema-id>_quiz.json`**
```json
{
  "$schema": "./schema/quiz.schema.json",
  "fach": "",
  "fragen": [
    { "frage": "", "optionen": ["", ""], "loesung": 0 }
  ]
}
```

**Manifest-Eintrag (Standard-Thema):**
```json
{
  "id": "",
  "name": "",
  "icon": "📘",
  "beschreibung": "",
  "typ": "fach"
}
```

**Manifest-Eintrag (Tool-Thema):**
```json
{
  "id": "",
  "name": "",
  "icon": "⚙",
  "beschreibung": "",
  "typ": "tool",
  "toolScript": "/js/<thema-id>.js",
  "toolStyle":  "/css/<thema-id>.css",
  "toolData":   "/data/<thema-id>_data.json",
  "toolQuiz":   "/data/<thema-id>_quiz.json"
}
```

---

## Qualitäts-Check (30 Sekunden)

Nach dem Einfügen der KI-Ausgabe:

- [ ] Öffne die JSON-Datei im Editor: **rote Kringel** = Schema-Fehler → Maus drüber.
- [ ] Stimmt bei jeder Quiz-Frage `loesung` auf die tatsächlich richtige Antwort?
      (`0` = erste Option, `1` = zweite, usw.)
- [ ] Manifest-`id` = Dateiname-Präfix? (`dbi-sql` → `dbi-sql_theorie.json`)
- [ ] `npx serve` starten und die neue Seite im Browser aufrufen – lädt sie fehlerfrei?
- [ ] Browser-Konsole auf Netzwerk-Fehler prüfen (F12 → Network → rote Einträge).
