# KI-Vorlage: Lerninhalte als JSON erzeugen

Du musst die JSON-Dateien **nicht selbst schreiben**. Kopiere den Prompt unten in
eine KI (Claude, ChatGPT o. Ä.), ersetze die Platzhalter und füge die erzeugten
Dateien ins Projekt ein. Die Vorlagen entsprechen 1:1 den Schemas in
[`data/schema/`](schema/).

---

## So gehst du vor

1. Prompt unten kopieren, **THEMA** und **FACH-ID** ersetzen.
2. Antwort der KI als zwei Dateien speichern:
   - `data/<FACH-ID>_theorie.json`
   - `data/<FACH-ID>_quiz.json`
3. In [`data/manifest.json`](manifest.json) einen Eintrag ergänzen (siehe unten).
4. `npx serve` starten – das neue Fach erscheint automatisch im Dashboard.

> **FACH-ID-Regel:** nur Kleinbuchstaben, Ziffern, Bindestriche (`^[a-z0-9-]+$`),
> z. B. `physik`, `bio`, `deutsch-grammatik`. Sie bestimmt die Dateinamen.

---

## Der Prompt (kopieren & ausfüllen)

```text
Erzeuge Lerninhalte als zwei JSON-Dateien für eine Lernwebsite.

THEMA: <z. B. "Photosynthese für die 7. Klasse">
FACH-ID: <z. B. physik>     (nur a-z, 0-9, Bindestrich)
FACH-NAME: <z. B. Physik>   (Anzeigename)

Gib EXAKT zwei Code-Blöcke zurück, sonst keinen Text.

--- Block 1: <FACH-ID>_theorie.json ---
{
  "$schema": "./schema/theorie.schema.json",
  "fach": "<FACH-NAME>",
  "themen": [
    { "titel": "<Überschrift>", "text": "<Fließtext, 2-4 Sätze>" }
  ]
}
Regeln:
- 3 bis 5 Themen.
- "titel" und "text" sind nicht leer.
- KEINE weiteren Felder als "titel" und "text".

--- Block 2: <FACH-ID>_quiz.json ---
{
  "$schema": "./schema/quiz.schema.json",
  "fach": "<FACH-NAME>",
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
- "loesung": GANZZAHL, 0-basierter Index in "optionen" (0 = erste Antwort).
  Muss kleiner als die Anzahl der Optionen sein und auf die RICHTIGE Antwort zeigen.
- KEINE weiteren Felder als "frage", "optionen", "loesung".

Allgemeine Regeln für beide Dateien:
- Gültiges JSON, UTF-8, doppelte Anführungszeichen, kein Komma nach dem letzten Element.
- Inhalte auf Deutsch, sachlich korrekt, altersgerecht zum THEMA.
- Die "$schema"-Zeile exakt wie oben übernehmen.
```

---

## Leere Vorlagen (falls du es doch von Hand machst)

**`data/<FACH-ID>_theorie.json`**
```json
{
  "$schema": "./schema/theorie.schema.json",
  "fach": "",
  "themen": [
    { "titel": "", "text": "" }
  ]
}
```

**`data/<FACH-ID>_quiz.json`**
```json
{
  "$schema": "./schema/quiz.schema.json",
  "fach": "",
  "fragen": [
    { "frage": "", "optionen": ["", ""], "loesung": 0 }
  ]
}
```

**Manifest-Eintrag** in [`data/manifest.json`](manifest.json) → in das `faecher`-Array:
```json
{ "id": "", "name": "", "icon": "📘", "beschreibung": "" }
```

---

## Qualitäts-Check (30 Sekunden, ohne Tools)

- Öffne die Datei im Editor: erscheinen **rote Kringel**? Dann meldet das Schema einen
  Fehler – Maus drüber zeigt, was fehlt. (Funktioniert dank der `$schema`-Zeile.)
- Stimmt bei jeder Frage der `loesung`-Index? `0` = erste Option, `1` = zweite usw.
- Manifest-`id` = Dateinamen-Präfix? (`mathe` → `mathe_theorie.json` / `mathe_quiz.json`)
