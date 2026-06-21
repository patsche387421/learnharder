# Namenskonvention & Domänen-Glossar — LearnHarder

## Zweck
Referenz „für Claude" und für Entwickler: einheitliche Namen für das Level-System und seine
Begriffe. Ergänzt — überschreibt nicht — die Regeln aus `docs/CLAUDE.md`. Bei jeder neuen
Session zuerst hier nachsehen, wie ein Begriff/eine Funktion **real** heißt, bevor neuer
Code geschrieben wird.

> Stand: dokumentiert den **IST-Zustand** (verifiziert aus `src/js/level.js` und
> `src/js/stats.js`). Geplante Umbenennungen stehen in Abschnitt 4 und sind **noch nicht**
> umgesetzt.

---

## 1. Konventionsregeln (ergänzt CLAUDE.md)

- **Identifier deutsch, camelCase** für Funktionen und Variablen
  (`verbrauchEnergie`, `ladeFachStats`, `aktuelleFrage`).
- **Module PascalCase** als IIFE-Singletons (`Level`, `Stats`, `Auth`, `App`, `Icons`,
  `ContentIcons`). Aufbau: `const Modul = (() => { /* privat */ return { /* API */ }; })();`
- **Kommentare deutsch**, durchgängig (CLAUDE.md).
- **DB-Spalten snake_case; neue Spalten englisch** (`total_xp`, `subject_id`,
  `correct_answers`).
  > ⚠️ **IST gemischt:** Tabellen aus Migration 003 (`user_stats`, `subject_xp`,
  > `daily_quiz_log`) sind englisch; ältere (`fach_stats`, `thema_progress`, `quiz_results`)
  > deutsch (`themen_bearbeitet`, `quiz_punkte_gesamt`, `abgeschlossen`, `richtig`,
  > `gesamt`). Bestehende Spalten **nicht** umbenennen (RLS/Queries hängen daran); die
  > englische Regel gilt für **neue** Tabellen/Spalten.
- **Pfade absolut** (`/css/…`, `/js/…`, `/assets/data/…`).
- **Kein Inline-JS** in HTML (Ausnahme `onerror`-Fallback), **kein `!important`**.

---

## 2. Domänen-Glossar

| Begriff | Bedeutung | JS-Identifier | DB-Spalte |
|---|---|---|---|
| **EP / Erfahrungspunkte** | Punkte fürs richtige Beantworten (Themen-Quiz 1/Antwort, Tagesquiz 5/Antwort + Bonus) | `ep`, `totalXp`, `fachXp`, `gesamtEp` | `user_stats.total_xp`, `subject_xp.xp` |
| **Level** | aus EP **abgeleitete** Stufe 1–10 (EP ist die Wahrheit, Level ist berechnet) | `level`, `berechneLevel()` | `user_stats.level`, `subject_xp.level` *(denormalisierter Cache)* |
| **Trophäen** | +1 pro richtige Antwort, immer vergeben, eintauschbar (50 = 1 Energie) | `trophien`, `trophies` | `user_stats.trophies`, `daily_quiz_log.trophies_earned` |
| **Energie** | Energydrinks; Tagesquiz kostet 1 beim Start | `energy` | `user_stats.energy` |
| **Schwelle** | EP-Grenze, ab der ein Level erreicht ist | `LEVEL_THRESHOLDS`, `naechsteSchwelle` | — *(Code-Konstante)* |
| **Band** | EP-Spanne **innerhalb** eines Levels (untere→obere Schwelle) | `epImBand`, `bandGroesse` *(Schritt A)* | — *(berechnet)* |
| **Saison** | resetbarer Zeitraum (geplant: Kalendermonat) fürs Team-/Gemeinschaftslevel | — *(noch keiner)* | *(geplant: `xp_events.created_at` / `season_id`)* |

---

## 3. Funktions-Registry Level-System (IST-Zustand)

„Art" = reine Berechnung vs. Seiteneffekt (DB-Lesen / DB-Schreiben / DOM / Konsole).

### 3.1 Modul `Level` — `src/js/level.js`

| Export | Signatur | Zweck | Art |
|---|---|---|---|
| `LEVEL_THRESHOLDS` | `number[]` = `[0,100,250,500,900,1400,2000,2700,3500,4500]` | EP-Schwellen, Index = Levelnummer | Daten-Konstante (rein) |
| `getUserStats` | `() ⇒ Promise<{energy, level, trophies, totalXp}>` | globale Gamification-Werte des Users | Seiteneffekt: DB-Lesen (`user_stats`) |
| `verbrauchEnergie` | `() ⇒ Promise<boolean>` | zieht 1 Energydrink ab (false bei 0) | Seiteneffekt: DB-Schreiben (`user_stats`) |
| `hatHeuteTagesQuizGespielt` | `() ⇒ Promise<boolean>` | Tagessperre-Prüfung (UTC-Tag) | Seiteneffekt: DB-Lesen (`daily_quiz_log`) |
| `vergibBelohnungen` | `({richtig, gesamt, fachId, istTagesQuiz=false, lebenProzent}) ⇒ Promise<{ep, trophien, bonus, levelUp}>` | berechnet **und** schreibt EP/Trophäen/Level + Tagesquiz-Log | Seiteneffekt: DB-Lesen+Schreiben (`user_stats`, `subject_xp`, `daily_quiz_log`) + `console.log` |
| `tauscheTrophäen` | `(anzahlEnergie) ⇒ Promise<{erfolg, fehler?}>` | tauscht 50 Trophäen → 1 Energie | Seiteneffekt: DB-Lesen+Schreiben (`user_stats`) |
| `renderTopbar` | `(stats?) ⇒ Promise<void>` | rendert die Topbar in `#topbar` | Seiteneffekt: DOM-Schreiben + ggf. DB-Lesen (Session, `getUserStats`) |

**Privater Helfer (nicht exportiert, IST):** `berechneLevel(totalXp) ⇒ number (1–10)` — **rein**
(nur Mathe). Zentrale Funktion für die SSOT; wird in **Schritt A** exportiert. Ebenfalls
privat: `topbarInitialen`, `aktiverNav`, `topbarGrundgeruest` (UI-Helfer, keine Domänenlogik).

### 3.2 Modul `Stats` — `src/js/stats.js`

| Export | Signatur | Zweck | Art |
|---|---|---|---|
| `ladeFachStats` | `(fachId) ⇒ Promise<{fortschritt, themenBearbeitet, quizPunkte, letzteAktivitaet}>` | Fach-Aggregat | Seiteneffekt: DB-Lesen (`fach_stats`) |
| `speichereLernfortschritt` | `(themaId, richtig, gesamt) ⇒ Promise<void>` | schreibt Quiz-Verlauf + Thema-Fortschritt + Fach-Aggregat | Seiteneffekt: DB-Schreiben (`quiz_results`, `thema_progress`, `fach_stats`) |
| `ladeFachThemenProgress` | `(fachId) ⇒ Promise<{ [thema_id]: {abgeschlossen, letzter_score} }>` | Abschluss-Map aller Themen eines Fachs | Seiteneffekt: DB-Lesen (`thema_progress`) |
| `ladeDashboardStats` | `() ⇒ Promise<{themenBearbeitet, quizPunkte}>` | Aggregat über alle Fächer | Seiteneffekt: DB-Lesen (`fach_stats`) |
| `ladeFachStatsKomplett` | `(fachId) ⇒ Promise<{fachLevel, fachXp, correctAnswers}>` | Fach-Level/-XP für die Level-Leiste | Seiteneffekt: DB-Lesen (`subject_xp`) |

**Privater Helfer (IST):** `leerFachStats()` — liefert Leer-Objekt.

---

## 4. Offene Entscheidung: vollständige Englisch-Migration

Eine durchgängige Umstellung aller Identifier **und** Kommentare auf Englisch (nach
internationalen Konventionen) ist **zurückgestellt** — eigener Scope, eigene Session:
- betrifft alle ~23 Dateien (HTML + JS),
- widerspricht der aktuellen CLAUDE.md (müsste mit angepasst werden),
- rein kosmetisch, kein Funktionsgewinn für ein Solo-Projekt.

Bis dahin gilt: **deutsch** wie in Abschnitt 1. Diese Entscheidung wird hier vermerkt, damit
sie nicht implizit „nebenbei" passiert.
