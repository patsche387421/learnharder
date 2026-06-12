# Level-System — LernHub

## Übersicht
Das Gamification-System besteht aus vier Komponenten:
Energie, Lebensbalken, Trophäen und Erfahrungspunkte (EP).
Es ist noch NICHT implementiert — diese Datei beschreibt
den geplanten Stand für die nächste Entwicklungsphase.

## Aktueller Stand
Aktuell wird pro Thema nur ein Fortschritt gespeichert
(Quiz abgeschlossen ja/nein + Score in Prozent).
Das vollständige Gamification-System kommt in Phase 2.

---

## 1. Energie 🥤
- 5 Energydrinks pro User täglich
- Reset: täglich um Mitternacht
- 1 Energydrink wird beim Quiz-Start verbraucht
- Kein Energydrink verfügbar → Quiz nicht startbar
- 50 Trophäen können gegen 1 Energydrink eingetauscht werden

## 2. Lebensbalken ❤️
- Existiert nur während eines laufenden Quiz (nicht persistent)
- 10 Fragen pro Quiz → -20% Leben pro falscher Antwort
- = maximal 4 Fehler erlaubt
- Bei 0% Leben: Quiz kann zu Ende gespielt werden,
  aber es gibt weder EP noch Bonus
- Nach Quiz-Ende wird der Lebensbalken verworfen

## 3. Trophäen 🏆
- +1 Trophäe pro richtiger Antwort
- Werden IMMER vergeben — auch bei gescheitertem Quiz
- Persistent im User-Account gespeichert
- Einlösbar: 50 Trophäen = 1 Energydrink

## 4. Erfahrungspunkte (EP) & Level ⭐

### Basis
- 5 EP pro richtiger Antwort

### Bonus bei erfolgreichem Abschluss (Leben > 0%)
| Score    | Bonus EP |
|----------|----------|
| > 50%    | +10 EP   |
| > 70%    | +20 EP   |
| > 90%    | +35 EP   |
| = 100%   | +50 EP   |

### Wichtige Regeln
- EP werden erst AM ENDE des Quiz gebucht (nicht live)
- Bei gescheitertem Quiz (Leben = 0%): keine EP, keine Buchung
- Trophäen werden trotzdem vergeben

### Verteilung
- Gesamt-EP → globales User-Level
- EP pro Fach → eigenes Fach-Level
- Jede Frage gehört einem Fach → EP werden
  bei Quiz-Ende auf das richtige Fach gebucht

## 5. Level-Schwellen (steigend)

| Level | EP benötigt |
|-------|-------------|
| 1     | 0           |
| 2     | 100         |
| 3     | 250         |
| 4     | 500         |
| 5     | 900         |
| 6     | 1.400       |
| 7     | 2.000       |
| 8     | 2.700       |
| 9     | 3.500       |
| 10    | 4.500       |

## 6. Datenbank-Erweiterungen (geplant)

Zusätzlich zu den bestehenden Tabellen werden benötigt:

### users (Erweiterung)

```sql
ALTER TABLE users ADD COLUMN energy int DEFAULT 5;
ALTER TABLE users ADD COLUMN energy_last_reset timestamptz;
ALTER TABLE users ADD COLUMN trophies int DEFAULT 0;
ALTER TABLE users ADD COLUMN total_xp int DEFAULT 0;
ALTER TABLE users ADD COLUMN level int DEFAULT 1;
```

### subject_xp (neu)

```sql
CREATE TABLE subject_xp (
  user_id uuid REFERENCES auth.users,
  subject_id text,
  xp int DEFAULT 0,
  level int DEFAULT 1,
  PRIMARY KEY (user_id, subject_id)
);
```

## 7. Fragen-Pool & Versuche

- Zentraler Fragen-Pool in der Datenbank
- Pro Thema: zufällige Auswahl von 10 Fragen
- Normal: 3 Versuche pro Thema/Tag
- Lernphase (vor Test): 10 Versuche pro Thema/Tag
- Übungsmodus: keine EP, keine Trophäen

---
*Erstellt: Juni 2026 | Status: Geplant, noch nicht implementiert*
