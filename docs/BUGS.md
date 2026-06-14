# LearnHarder – Bug & Issue Tracker

Stand: Juni 2026
Legende: 🔴 Kritisch · 🟡 Wichtig · 🟢 Nice-to-have · ✅ Erledigt

---

## 🔴 Kritisch

### BUG-001: Tagesquiz lädt nicht
- **Symptom:** Schwarzer Screen beim Aufruf von /tagesquiz.html
- **Vermutete Ursache:** JSON-Ladefehler (manifest.json oder Fach-JSONs)
- **Status:** Offen
- **Priorität:** Wird nach Daten-Architektur-Migration neu bewertet
  (eventuell hinfällig durch Supabase-Umstellung)


---

## 🟡 Wichtig

### BUG-003: EP-Buchung nicht verifiziert
- **Symptom:** Fix committed, aber Supabase-Daten nicht geprüft
- **Aktion:** Manuell in Supabase Table Editor prüfen ob 
  user_stats + subject_xp nach Quiz-Ende aktualisiert wird
- **Status:** Offen

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

---

## 🟢 Nice-to-have

### BUG-007: DESIGN_GUIDELINES.md veraltet
- **Symptom:** Beschreibt altes --color-* CSS-System
- **Aktion:** Auf aktuelles tokens.css System aktualisieren
- **Status:** Offen

### BUG-008: Formatierung der Lerninhalte suboptimal
- **Symptom:** Keine einheitlichen Render-Regeln für Code, Listen,
  Bilder, Tabellen in Quiz/Theorie
- **Aktion:** Content-Render-Modul planen
- **Status:** Offen

---

## ✅ Erledigt

- Topbar vereinheitlicht (Logo+Seite links, Level mitte, Stats+PROFIL+HILFE rechts)
- Themen-Quiz Completion-Bonus entfernt (nur Tagesquiz)
- Lokale Entwicklung mit npx serve src + config.js funktioniert
- Login/Logout via Supabase Auth produktiv