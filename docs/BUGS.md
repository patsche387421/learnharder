# LearnHarder – Bug & Issue Tracker

Stand: Juni 2026
Legende: 🔴 Kritisch · 🟡 Wichtig · 🟢 Nice-to-have · ✅ Erledigt

---

## 🟡 Wichtig

### BUG-003: EP-Buchung nicht verifiziert
- **Symptom:** `Level.buecheQuizErgebnis()` wurde ohne `await` aufgerufen —
  Rückgabewert (EP/Trophäen) war nicht nutzbar, Fehler wurden lautlos
  verschluckt.
- **Status:** Teilweise behoben — Code-Fix in `9ed1548` (`await` + EP/🏆
  werden im Result-Text angezeigt). Manuelle Verifikation in Supabase
  Table Editor (`user_stats`, `subject_xp`) steht noch aus.
- **Aktion:** Nach Login einmal Quiz absolvieren, dann in Supabase prüfen
  ob `total_xp`, `trophies` und `subject_xp.xp` korrekt aktualisiert wurden.

### BUG-004: POS-Datenstrukturen-Tool verschwunden
- **Symptom:** Interaktives Tool aus POS-Fach ist weg
- **Ursache:** Bei einer Refactoring-Runde entfernt
- **Status:** Wiederherstellung geplant (Punkt 5 der Prio-Liste)

### BUG-005: 5 leere Fächer (NSVS, TINF, WIR, MEDT, SYP)
- **Status:** Inhaltlich, kein Code-Bug. Wird nach Daten-Migration befüllt.

### BUG-006: level_log Tabelle fehlt
- **Symptom:** Profil-Grafik zeigt nur einen Punkt
- **Aktion:** SQL-Migration 004_level_log.sql erstellen
- **Status:** Offen

### BUG-010: Stats.speichereQuizErgebnis() ohne await — Fehler lautlos
- **Symptom:** In `src/js/app.js:262` wird `Stats.speichereQuizErgebnis()`
  ohne `await` aufgerufen (fire-and-forget). Wenn Supabase langsam ist oder
  ein Netzwerkfehler auftritt, werden `quiz_results`, `thema_progress` und
  `fach_stats` nicht aktualisiert — ohne Fehlermeldung im UI.
- **Ursache:** Bewusstes Design (blockiert UI nicht), aber kein Error-Handling.
- **Aktion:** `.catch(err => console.error(...))` ergänzen oder mit `await`
  + Try/Catch absichern und Fehler im UI signalisieren.
- **Status:** Offen

---

## 🟢 Nice-to-have

### BUG-007: DESIGN_GUIDELINES.md veraltet
- **Symptom:** Beschreibt altes --color-* CSS-System
- **Aktion:** Auf aktuelles tokens.css System aktualisieren
- **Status:** Offen — Design-Dateien in docs/design/ 
  verwenden bereits korrekte Token-Namen aus tokens.css.
  Nur DESIGN_GUIDELINES.md selbst ist noch veraltet.

### BUG-008: Formatierung der Lerninhalte suboptimal
- **Symptom:** Keine einheitlichen Render-Regeln für Code, Listen,
  Bilder, Tabellen in Quiz/Theorie
- **Aktion:** Content-Render-Modul planen
- **Status:** Offen

### BUG-009: LEVEL_SYSTEM.md §4 veraltet (EP-Unterschied fehlt)
- **Status:** ✅ Erledigt — LEVEL_SYSTEM.md vollständig aktualisiert
  (dieser Commit: docs: LEVEL_SYSTEM aktualisiert, Reiter-Konzept angelegt).
  §1 Energie, §2 Lebensbalken, §4 EP, §7 Fragen-Pool, §11 Offene
  Entscheidungen — alle auf tatsächlichen Code-Stand gebracht.

---

## ⚠️ Bekannte Diskrepanzen

### LEVEL_SYSTEM.md §4 vs. tatsächlicher Code
- **Doku sagt:** 5 EP pro richtige Antwort (pauschal)
- **Code seit `9ed1548`:** `level.js:89` — Themen-Quiz: 1 EP, Tagesquiz: 5 EP
  + Completion-Bonus (10/20/35/50)
- **Auswirkung:** Wer die Spec liest, erwartet 5 EP im Themen-Quiz —
  tatsächlich gibt es nur 1 EP.
- **Lösung ausstehend:** Siehe BUG-009.

### tagesquiz_test.json: 5 Fragen statt geplanter 6–10
- **LEVEL_SYSTEM.md §7** plant 6–10 Fragen pro Quiz (Standard 8).
- **Aktuell:** `tagesquiz_test.json` enthält nur 5 Testfragen → `schadensProFehler`
  = 20 % statt geplanter 12,5 % (bei 8 Fragen).
- **Kein Code-Bug** — bewusste Test-Daten-Limitation.
- **Lösung:** Nach Daten-Migration V2 liefert Supabase einen echten Fragen-Pool;
  Test-JSON ist dann obsolet.

### Energie-Auto-Regen: geplant, nicht implementiert
- **LEVEL_SYSTEM.md §1** plant: +1 Energydrink pro Tag, Deckel bei 5.
- **Code:** `verbrauchEnergie()` (`level.js`) dekrementiert nur. Kein
  automatisches Aufladen in JS implementiert. Kein Supabase-Trigger/Edge-Function
  erkennbar.
- **Auswirkung:** Energie kann aktuell nur durch Trophäen-Tausch steigen.
  Ohne Implementierung haben User nach einigen Tagesquiz-Versuchen dauerhaft 0.
- **Lösung ausstehend:** Supabase Edge-Function oder DB-Trigger (LEVEL_SYSTEM.md §11.2).

### BUG-001-Workaround: Tagesquiz läuft nur mit Test-JSON
- Tagesquiz funktioniert aktuell über `tagesquiz_test.json` (5 statische Fragen).
- Die ursprünglich geplante dynamische Fragen-Auswahl aus Themen-JSONs wurde
  entfernt (war fehlerhaft, BUG-001).
- **Endlösung:** Supabase `content_items`-Tabelle (Daten-Migration V2).

---

## ✅ Erledigt

- **BUG-001: Tagesquiz schwarzer Screen** — Workaround via Test-JSON
  (`e347c6f`: ladeFragen() ersetzt, `7fbb73f`: SyntaxError in tagesquiz.html).
  Endgültige Lösung folgt nach Daten-Migration V2.
- **Themen-Quiz Auswerten-Übersicht fehlte** — nach „Auswerten" erscheint
  jetzt `+X EP, +Y 🏆` (`9ed1548`).
- **Lebensanzeige-Breite sprang** — Start- und Quiz-Screen jetzt beide
  max-width 600 px, kein Layout-Sprung mehr (`9ed1548`).
- Topbar vereinheitlicht (Logo+Seite links, Level mitte, Stats+PROFIL+HILFE rechts)
- Themen-Quiz Completion-Bonus entfernt; EP quiztyp-abhängig
  (Themen: 1 EP/Antwort, Tagesquiz: 5 EP + Bonus — `9ed1548`)
- Lokale Entwicklung mit npx serve src + config.js funktioniert
- Login/Logout via Supabase Auth produktiv
