# Daten-Migration V2: JSON → Supabase

## Status: Planung

## Ziel
Alle Lerninhalte (Quiz-Fragen, Theorie, Karteikarten, 
Wahr/Falsch, Tools) wandern aus src/assets/data/*.json 
in die Supabase-Datenbank.

## Unterstützte Content-Typen (langfristig)
- Quiz (Multiple Choice)
- Theorie (Lesetexte mit Code/Bildern)
- Karteikarten (Front/Back)
- Wahr/Falsch
- Tool/Interaktiv (z.B. POS-Datenstrukturen)

## Neue Tabellen (Entwurf)

### subjects
- id (uuid, PK)
- kuerzel (text, unique) - z.B. "POS", "DBI"
- name (text) - z.B. "Programmieren und Software-Entwicklung"
- icon (text) - Emoji oder Icon-Name
- farbe (text) - CSS-Token-Name oder Hex
- reihenfolge (int)

### topics
- id (uuid, PK)
- subject_id (uuid, FK → subjects)
- slug (text) - URL-freundlich
- titel (text)
- beschreibung (text, nullable)
- reihenfolge (int)

### content_items
- id (uuid, PK)
- topic_id (uuid, FK → topics)
- typ (enum: 'quiz', 'theorie', 'karteikarte', 'wahrfalsch', 'tool')
- titel (text)
- reihenfolge (int)
- daten (jsonb) - typ-spezifischer Content

### Beispiel daten-jsonb pro Typ

**quiz:**
{ "frage": "...", "antworten": ["A","B","C","D"], "richtig": 2 }

**theorie:**
{ "markdown": "# Titel\n\nText..." }

**karteikarte:**
{ "front": "...", "back": "..." }

**wahrfalsch:**
{ "aussage": "...", "ist_wahr": true, "erklaerung": "..." }

**tool:**
{ "tool_typ": "datenstrukturen", "config": {...} }

## Migrations-Phasen

Phase 1: SQL-Schema (sql/migrations/004_content_tables.sql)
Phase 2: Import-Skript (scripts/import_json_to_supabase.js)
  Liest src/assets/data/*.json, schreibt in neue Tabellen
Phase 3: Frontend-Anpassung
  src/js/content.js neu - lädt aus Supabase statt fetch JSON
  Bestehende Quiz/Theorie-Seiten umstellen
Phase 4: JSON-Dateien archivieren
  src/assets/data/ → src/assets/data_archive/ verschieben
  (NICHT löschen - als Backup)

## Sicherheit (RLS-Konzept)
- Alle neuen Tabellen mit RLS aktiviert
- SELECT-Policy: authenticated users dürfen lesen
- INSERT/UPDATE/DELETE-Policy: nur Admin-Rolle
- Admin-Definition offen (TODO: Rollen-Konzept in Phase 1)

## Datenbank-Hygiene
- Jede Tabelle bekommt created_at + updated_at (timestamptz, default now())
- Foreign Keys mit ON DELETE CASCADE wo sinnvoll
- Indices auf alle FK-Spalten
- Index auf content_items.typ für typ-spezifische Queries

## Architektur-Entscheidung
- Unified content_items mit jsonb statt separate Tabellen pro Typ
- Begründung: einfacheres Schema, einfache Erweiterbarkeit, 
  ausreichend für MVP-Scope
- Trade-off: keine DB-seitige Typ-Validierung; jsonb-Schema 
  wird im Frontend (TypeScript-Doc-Kommentare) gepflegt

## Offene Fragen
- Wie wird die POS-Tools-Wiederherstellung integriert?
- Soll content_items.daten validiert werden (jsonb_schema)?
- Performance: Caching im Frontend?

## Nächster Schritt
Phase 1 ausarbeiten - aber erst nach Bestätigung des Plans.

TODO: tagesquiz.html anpassen um src/assets/data/tagesquiz_test.json zu laden
(separater Prompt, isoliert).
