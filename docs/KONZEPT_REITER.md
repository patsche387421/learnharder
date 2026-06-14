# Reiter-Struktur: Themen-Seiten (fach.html)

## Status: Geplant — noch nicht implementiert

## Entscheidung
Modell 2: Reiter auf Thema-Ebene (fach.html?fach=pos-variablen)
Tools bleiben auf Fach-Ebene (pos.html, dbi.html etc.)

## Reiter pro Thema

### 1. Theorie
- Aufbereitete Lerninhalte (aktuell aus _theorie.json,
  später aus Supabase content_items WHERE typ='theorie')
- Ziel: schön formatiert, Code-Blöcke, Listen, Bilder
- MVP: bestehender renderTheorie() bleibt, nur in Reiter eingebettet

### 2. Aufgaben
- Multiple Choice Quiz (aktuell aus _fragen.json + _antworten.json,
  später aus Supabase content_items WHERE typ='quiz')
- Belohnung: 1 EP pro richtige Antwort + Trophäen, KEIN Bonus
- Kein Energydrink-Verbrauch
- Mehrfach spielbar (zum Trophäen grinden)
- MVP: bestehender renderQuiz() bleibt, nur in Reiter eingebettet

### 3. Modi (Platzhalter)
- Karteikarten (content_items WHERE typ='karteikarte')
- Wahr/Falsch (content_items WHERE typ='wahrfalsch')
- Speedmodus (geplant)
- MVP: Platzhalter-Tab mit "Kommt bald"

## Tools (auf Fach-Ebene, NICHT pro Thema)
- Interaktive Übungstools (z.B. POS-Datenstrukturen)
- Bleiben auf pos.html / dbi.html etc. als eigener Bereich
- Grund: Tools sind oft fachübergreifend, nicht themenspezifisch

## Umsetzungsplan

Phase 1: HTML-Struktur (fach.html)
  - Tab-Navigation (<nav class="reiter-nav">)
  - Drei <section> Bereiche (theorie / aufgaben / modi)
  - Aktiver Tab via JS + CSS (data-reiter Attribut)
  - URL-Hash für direkten Tab-Aufruf (#theorie, #aufgaben, #modi)

Phase 2: JS-Anpassung (app.js)
  - renderFach() aufteilen in renderTheorie() + renderAufgaben()
  - Tab-Switching Logik
  - Hash-basierte Navigation (#theorie default)

Phase 3: CSS (style.css + tokens.css)
  - .reiter-nav Styles (aktiver Tab, Hover, Mobile)
  - Mobile: Tabs scrollen horizontal wenn zu schmal

Phase 4: Supabase-Migration (nach DATA_MIGRATION_V2.md)
  - content.js lädt aus DB statt JSON
  - Reiter-Struktur bleibt identisch, nur Datenquelle ändert sich

## Abhängigkeiten
- DATA_MIGRATION_V2.md Phase 1-3 muss abgeschlossen sein
  bevor Phase 4 dieser Reiter-Struktur umgesetzt werden kann
- POS-Tool-Wiederherstellung (BUG-004) ist unabhängig davon

## Mobile-Verhalten
- Reiter-Navigation: horizontal scrollbar auf kleinen Screens
- Theorie: Fließtext, Code-Blöcke 100% Breite
- Aufgaben: Antwort-Buttons untereinander auf Mobile
- Modi: Karteikarten zentriert, 100% Breite auf Mobile
