# DATENMIGRATION.md — Fragen von JSON nach Supabase

Strategie, wie die Quiz-Fragen von statischen JSON-Dateien in eine zentrale
Supabase-Tabelle (`questions`) überführt werden. Beschreibt Ist-Zustand,
Ziel-Zustand und die Migrations-Schritte. **Noch nicht umgesetzt — geplant.**

---

## Übersicht

Warum migrieren?

- **Skalierbarkeit:** Mit wachsender Fächer- und Themenzahl werden hunderte
  JSON-Dateien unübersichtlich. Eine Tabelle bündelt alle Fragen zentral.
- **Zufällige Auswahl aus großem Pool:** Statt nur die wenigen Fragen einer
  JSON-Datei zu mischen, kann aus einem beliebig großen Fragen-Pool pro Thema
  zufällig gezogen werden (per DB-Query, nicht clientseitig).
- **Lernphase mit mehr Versuchen:** Versuchs-Limits und Lernphasen-Logik
  (siehe [LEVEL_SYSTEM.md](LEVEL_SYSTEM.md) §7) lassen sich serverseitig in der
  DB abbilden, statt nur im Client.

---

## Ist-Zustand

- Fragen liegen in **statischen JSON-Dateien** unter
  `src/assets/data/<fach>/`.
- Pro Thema **zwei Dateien**:
  - `<thema>_fragen.json` — Fragen + Antwortoptionen (ohne Lösung)
  - `<thema>_antworten.json` — die richtigen Lösungs-Indizes (separat, damit
    die Lösungen nicht schon beim Seitenaufruf im Network-Tab sichtbar sind)
- Struktur `_fragen.json`:

  ```json
  {
    "fach": "POS",
    "fragen": [
      { "frage": "Welcher Datentyp …?", "optionen": ["int", "double", "string", "bool"] }
    ]
  }
  ```

- Struktur `_antworten.json` (Index der richtigen Option je Frage):

  ```json
  { "fach": "POS", "loesungen": [0, 0, 0, 0, 0] }
  ```

- **Zufällige Auswahl bereits per JS** geplant (6–10 Fragen, Standard 8 —
  siehe LEVEL_SYSTEM.md §7). Die Auswahl mischt aktuell nur die wenigen Fragen
  der jeweiligen JSON-Datei.

---

## Ziel-Zustand

- Fragen liegen in **Supabase** in einer `questions`-Tabelle.
- **Zufällige Auswahl per DB-Query** (`ORDER BY RANDOM() LIMIT n`), aus dem
  gesamten Fragen-Pool eines Themas.
- Vorteile: zentral verwaltbar, beliebig erweiterbar, Lernphasen-/Versuchs-Logik
  serverseitig möglich.

---

## Tabellen-Struktur (Ziel)

`questions`-Tabelle:

| Spalte          | Typ            | Beschreibung                                  |
|-----------------|----------------|-----------------------------------------------|
| `id`            | `uuid`         | Primärschlüssel (`gen_random_uuid()`)         |
| `subject_id`    | `text`         | Fach-Präfix, z. B. `"pos"`                    |
| `topic`         | `text`         | Thema-Slug, z. B. `"variablen"`               |
| `question`      | `text`         | Der Fragetext                                 |
| `answers`       | `json`         | Array der Antwortoptionen                     |
| `correct_index` | `int`          | Index der richtigen Option in `answers`       |
| `difficulty`    | `int` (1–3)    | Schwierigkeitsgrad                            |

```sql
CREATE TABLE questions (
  id            UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  subject_id    TEXT NOT NULL,            -- z. B. "pos"
  topic         TEXT NOT NULL,            -- z. B. "variablen"
  question      TEXT NOT NULL,
  answers       JSON NOT NULL,            -- ["int","double","string","bool"]
  correct_index INT  NOT NULL,            -- 0-basiert
  difficulty    INT  NOT NULL DEFAULT 1   -- 1 = leicht … 3 = schwer
);

CREATE INDEX ON questions (subject_id, topic);
```

> **RLS-Hinweis:** Anders als die user-bezogenen Stats-Tabellen
> (`fach_stats` etc. in [001_schema.sql](../sql/migrations/001_schema.sql), Policy
> `auth.uid() = user_id`) enthält `questions` **keine** `user_id`. Fragen sind
> gemeinsame Daten: **Lesen** für alle authentifizierten User erlauben,
> **Schreiben** nur über die Service-Role (Import-Script), nicht über den
> Anon-Key des Clients.
>
> ```sql
> ALTER TABLE questions ENABLE ROW LEVEL SECURITY;
> CREATE POLICY "questions lesbar"
>   ON questions FOR SELECT TO authenticated USING (true);
> -- Kein INSERT/UPDATE/DELETE fuer Clients: Import laeuft mit Service-Role,
> -- die RLS umgeht.
> ```

---

## Migrations-Schritte

1. **`questions`-Tabelle in Supabase anlegen** (z. B. als
   `sql/migrations/004_questions.sql`).
2. **RLS-Policy setzen** (Lesen für `authenticated`, Schreiben nur Service-Role).
3. **JSON-Daten importieren** per Node.js-Script `scripts/import_questions.js`
   (siehe Spezifikation unten).
4. **`src/js/app.js` auf DB-Queries umstellen:** `renderFach`/`renderQuiz` laden
   die Fragen statt aus `_fragen.json` per Supabase-Query (zufällige Auswahl,
   Limit konfigurierbar). Die Lösung (`correct_index`) wird weiterhin erst bei
   der Auswertung geprüft.
5. **JSON-Dateien als Backup behalten** (Datenquelle/Versionierung im Git),
   nicht löschen.

---

## Import-Script Spezifikation (`scripts/import_questions.js`)

Node.js-Script, das die vorhandenen JSONs in die `questions`-Tabelle überträgt.

**Ablauf:**

1. **Einlesen:** Alle `*_fragen.json` unter `src/assets/data/<fach>/` finden.
   Zu jeder Datei die passende `*_antworten.json` laden.
2. **Ableiten:**
   - `subject_id` ← Fach-Ordner bzw. Präfix der Thema-ID (z. B. `"pos"`).
   - `topic` ← Slug aus dem Dateinamen (z. B. `variablen_fragen.json` →
     `"variablen"`).
3. **Kombinieren** zu `questions`-Datensätzen — pro Frage `i`:
   - `question`      ← `fragen[i].frage`
   - `answers`       ← `fragen[i].optionen` (als JSON-Array)
   - `correct_index` ← `loesungen[i]`
   - `difficulty`    ← Default `1` (in den JSONs nicht vorhanden)
4. **Schreiben in Supabase** via JS-Client mit **Service-Role-Key**
   (nur lokal/CI, niemals im Frontend) — `upsert`, nicht blindes `insert`.
5. **Idempotent:** Mehrfaches Ausführen darf keine Duplikate erzeugen.
   - Entweder vor dem Import alle Zeilen eines `(subject_id, topic)` löschen
     und neu einfügen, **oder** ein deterministischer Schlüssel
     (z. B. `(subject_id, topic, question)` als `UNIQUE`) für `upsert`.

**Validierung:** Länge von `fragen` und `loesungen` müssen übereinstimmen;
`correct_index` muss ein gültiger Index in `answers` sein — sonst Abbruch mit
sprechender Fehlermeldung.

---

## Zeitplan

- **Phase 1 (jetzt):** Fragen in JSON-Dateien, zufällige Auswahl per JS
  (6–10, Standard 8). Kein DB-Zugriff für Fragen.
- **Phase 2 (nach Level-System):** DB-Migration — `questions`-Tabelle, Import-Script,
  `app.js` auf DB-Queries umstellen. JSONs bleiben als Backup.
- **Phase 3 (später):** Lernphasen-Logik in der DB (erweiterte Versuchs-Limits,
  Schwierigkeits-Steuerung über `difficulty`).

---
*Erstellt: Juni 2026 | Status: Geplant, noch nicht implementiert*
