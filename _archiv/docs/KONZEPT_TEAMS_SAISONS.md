# Teams & Saisons: Gemeinschaftslevel mit XP-Ledger

## Status: Konzept — kein Code, keine DB-Migration

Dieses Dokument hält die finale Architektur-Entscheidung fest, **bevor** das
Teams-Feature gebaut wird. Es wird in dieser Session nicht umgesetzt. Ziel ist, dass
das jetzige Level-System (Schritt A: reine SSOT-Funktionen in `level.js`) ohne Umbau
zur Team-/Saison-Logik erweitert werden kann.

## Ziel
- **Gemeinschaftslevel pro Gruppe:** Mehrere User bilden ein Team; die EP der Mitglieder
  ergeben ein gemeinsames Team-Level.
- **Monatlich + automatische Saison-Resets:** Jede Saison (ein Kalendermonat) startet bei
  0 EP. Am Saisonende wird das Team-Level „eingefroren" (Rangliste/Historie), die neue
  Saison beginnt frisch.
- **Persönliche Historie bleibt:** Lifetime-EP eines Users gehen durch Resets nie verloren
  (Profil, Fach-Level).

## Kern-Entscheidung: XP-Ledger als Daten-SSOT

Heute schreibt das System nur **kumulative Gesamtwerte** (`user_stats.total_xp`,
`subject_xp.xp`). Damit ist ein Saison-Reset ohne Datenverlust unmöglich und es gibt keine
Zeitreihe (BUG-006). Lösung: ein **append-only Event-Log** als maßgebliche Quelle aller EP.
Dieser `xp_events`-Ledger **ersetzt** zugleich die in BUG-006 geplante Tabelle `level_log`
(Migration `004_level_log.sql`): gleiche Funktion (Zeitreihe für die Profil-Grafik), aber
allgemeinerer Name, weil derselbe Ledger zusätzlich die Saison- und Team-Aggregation trägt.

### Vorgeschlagene Tabelle (Schema-Skizze, NICHT ausführen)
```sql
-- append-only: Zeilen werden nur eingefügt, nie geändert/gelöscht
xp_events (
  id          bigint generated always as identity primary key,
  user_id     uuid    not null references auth.users,
  amount      int     not null,        -- EP dieses Events (immer serverseitig gesetzt)
  source      text    not null,        -- 'themen_quiz' | 'tages_quiz' | ...
  subject_id  text,                    -- optional: Fach-Zuordnung
  team_id     uuid,                    -- optional: für spätere Team-Aggregation
  created_at  timestamptz not null default now()
)
```

### Ableitungen aus dem Ledger (alles per Query, kein Reset-Löschen)
| Wert | Berechnung |
|---|---|
| **Lifetime-EP** (User) | `SUM(amount) WHERE user_id = ?` |
| **Fach-EP** (User) | `SUM(amount) WHERE user_id = ? AND subject_id = ?` |
| **Saison-EP** (User) | `SUM(amount) WHERE user_id = ? AND created_at IN [saisonStart, saisonEnde)` |
| **Team-EP** (Saison) | `SUM(amount) WHERE team_id = ? AND created_at IN [fenster)` |
| **Profil-Zeitreihe** | `amount` gruppiert nach Tag/Woche → **löst BUG-006** (Grafik braucht die Reihe) |

**Reset = kein Löschen, nur ein anderes Query-Fenster.** Die alten Saisons bleiben als
Daten erhalten und sind jederzeit wieder auswertbar.

## Saison-Modell
- **Vorschlag:** `season_id = 'YYYY-MM'` (Kalendermonat). Das Fenster ergibt sich direkt aus
  dem Monat — keine separate Tabelle zwingend nötig (kann später als `seasons`-Tabelle mit
  Start/Ende formalisiert werden, falls unregelmäßige Saisons gewünscht sind).
- **⚠️ Offene Entscheidung — Reset-Modus:** Kalendermonat UTC (Mitternacht 1.→1.) vs.
  rollierend. Vorschlag Kalendermonat UTC (deckt sich mit der gleichen offenen Frage beim
  Energie-Regen, LEVEL_SYSTEM.md §11.2 — sollte einheitlich entschieden werden).

## Mapping: heutige Tabellen → Ledger
Die kumulativen Tabellen verschwinden nicht, sie werden zu **denormalisierten Caches**
(schnelle Reads für Topbar/Rangliste), während der **Ledger die Wahrheit** ist.

| Heute | Künftige Rolle |
|---|---|
| `user_stats.total_xp` | Cache von `SUM(amount)` pro User |
| `user_stats.level` | abgeleitet via `Level.berechneLevel(total_xp)` — reiner Cache |
| `subject_xp.xp` / `.level` | Cache von Fach-`SUM` bzw. abgeleitet |
| `daily_quiz_log` | bleibt (Tagessperre); EP-Anteil wird zusätzlich Ledger-Event |

### Migrationspfad (beschrieben, NICHT ausgeführt)
1. `xp_events` anlegen (neue Migration, separate Session).
2. Backfill: bestehende `user_stats`/`subject_xp`-Summen als je ein Start-Event je User/Fach
   einbuchen (`source = 'backfill'`), damit Lifetime-Summen stimmen.
3. Buchungslogik umstellen: pro Quiz ein `xp_events`-Insert; Caches werden aus dem Ledger
   aktualisiert (siehe Atomarität).
4. Team-/Saison-Reads ergänzen — die reine Mathe existiert bereits (siehe nächster Abschnitt).

## Warum die reinen Funktionen (Schritt A) das trivial machen
`Level.berechneLevel(ep)` und `Level.berechneFortschritt(ep)` nehmen **nur eine EP-Zahl**
entgegen und sind quelle-agnostisch. Damit gilt **dieselbe Mathe** für:
- User-Lifetime-EP (Profil, Topbar),
- Fach-EP (Fach-Seiten),
- **Team-EP** (Gemeinschaftslevel),
- **Saison-EP** (resetbares Saison-Level).

Das Teams-Feature ruft exakt dieselben Funktionen auf — kein Neuschreiben der Level-Logik,
nur eine andere EP-Summe als Eingabe.

## ⚠️ Offener Sicherheitspunkt: EP dürfen nicht vom Client kommen
**Problem:** Würde der Client EP-Beträge direkt in `xp_events` schreiben (analog zu heute, wo
das Frontend `total_xp` per Upsert setzt), könnte ein User beliebige EP eintragen →
**XP-Cheat-Vektor**, der bei Team-Ranglisten besonders schädlich ist.

**Autoritativer Pfad (Vorschlag):** Eine **Postgres-RPC** (Security-Definer-Funktion), die
- das Quiz-Ergebnis serverseitig **validiert** (welches Quiz, welche Antworten/Score),
- den EP-Betrag **selbst berechnet** und einbucht,
- sodass `amount` **nie** vom Client gesetzt wird.

RLS auf `xp_events`: **kein** direktes `INSERT` für `authenticated`; Schreibzugriff nur über
die RPC. **⚠️ Offene Entscheidung** — Validierungstiefe (reicht serverseitige Score-Prüfung
gegen einen Fragen-Pool, der ohnehin für DATA_MIGRATION_V2 in die DB kommt).

## Hinweis: Atomarität der Buchung
**Heute:** „Lernfortschritt speichern" (`Stats`) und „Belohnung buchen" (`Level`) sind
**zwei getrennte Writes ohne Transaktion** (siehe `app.js` / `tagesquiz.html`). Bricht der
zweite ab, ist der Zustand inkonsistent (Fortschritt da, EP fehlen — oder umgekehrt).

**Saubere Lösung (später):** **ein** RPC, der Validierung, Fortschritt und EP-Buchung
**atomar** in einer Transaktion erledigt. Der Ledger ermöglicht das, weil EP dann ein
einzelner Insert sind statt eines Read-Modify-Write auf kumulative Spalten.

## Abhängigkeiten
- `xp_events`-Migration und RPC: eigene Session, nach DATA_MIGRATION_V2 (Fragen-Pool in DB,
  damit serverseitige Validierung möglich ist).
- Reset-Modus muss mit dem Energie-Regen-Modus (LEVEL_SYSTEM.md §11.2) **gemeinsam**
  entschieden werden.
- Reine SSOT-Funktionen (Schritt A dieser Session) sind die Voraussetzung — danach ist das
  Team-/Saison-Level „nur noch" eine andere EP-Summe.
