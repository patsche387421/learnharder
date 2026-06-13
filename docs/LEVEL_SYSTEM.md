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
- Reset: täglich (Mitternacht vs. rollierend 24 h — siehe §11 Offene Entscheidungen ⚠️)
- 1 Energydrink wird beim Quiz-Start verbraucht
- Kein Energydrink verfügbar → Verhalten beim Quiz-Start siehe §11 ⚠️
- 50 Trophäen können gegen 1 Energydrink eingetauscht werden (Tausch-Seite, siehe §9)

## 2. Lebensbalken ❤️
- Existiert nur während eines laufenden Quiz (nicht persistent)
- **Dynamisch:** Schaden pro falscher Antwort = `100 / Anzahl_Fragen` %
  (skaliert mit der tatsächlichen Fragenanzahl des Quiz)
  - Beispiel: 6 Fragen → ~16,7 % pro Fehler
  - Beispiel: 8 Fragen → 12,5 % pro Fehler
  - Beispiel: 10 Fragen → 10 % pro Fehler
- Eine komplett falsch beantwortete Runde führt damit auf 0 % Leben
  (kein fixes -20% mehr)
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

- **Fragen-Auswahl per JS:** Pro Quiz werden zufällig **6–10 Fragen**
  geladen. Die Anzahl ist konfigurierbar (**Standard: 8**).
- **Datenquelle (Phase 1):** Die Fragen kommen vorerst weiterhin aus den
  vorhandenen JSON-Dateien (`<thema>_fragen.json` / `<thema>_antworten.json`);
  die Zufallsauswahl passiert clientseitig in JS.
- **Datenquelle (Phase 2):** Später zentraler Fragen-Pool in der Datenbank
  (zufällige Auswahl per DB-Query) — siehe [DATENMIGRATION.md](DATENMIGRATION.md).
- Normal: 3 Versuche pro Thema/Tag
- Lernphase (vor Test): 10 Versuche pro Thema/Tag
- Übungsmodus: keine EP, keine Trophäen

---

## 8. Anzeige von Level & Fortschritt

**Level werden pro Fach geführt — nicht pro Thema.** Die Level-/EP-Leiste lebt auf
der **Fach-Seite**; Themen-Karten zeigen weiterhin nur den thema-bezogenen
Fortschritt.

### Fach-Seite (z. B. `pos.html`, `dbi.html`)
Zusätzlich zur bereits vorhandenen Stats-Leiste
(Fortschritt % · Themen bearbeitet · Quiz-Punkte) erscheint eine
**Fach-Level-Leiste**:
- „Fach-Level X" (klein angezeigt)
- Fortschrittsbalken: EP bis zum nächsten Level
- Richtige Antworten gesamt in diesem Fach

### Themen-Karten (unverändert)
- Fortschritts-Badge (% richtige Antworten)
- Abgehakt-Icon (✓), wenn das Thema abgeschlossen ist
- **Keine** Level-Anzeige auf Themen-Karten

### Globales Level
Das globale User-Level (aus Gesamt-EP über alle Fächer) wird in der **Topbar** und
auf dem **Dashboard** angezeigt — auf der Fach-Seite gilt dagegen das **Fach-Level**.

---

## 9. Seitenstruktur & Navigation

### Topbar (alle Seiten)
Die Topbar erhält neben „Abmelden" drei neue Anzeigen:
- 🥤 Energydrinks (verbleibende Anzahl heute)
- ⭐ Globales Level (Zahl)
- 🏆 Trophäen (Zahl) — **verlinkt auf `/tauschen.html`**

### Neue Seite: `src/tauschen.html`
Einfache, klare Tausch-Seite:
- Zeigt die aktuelle Trophäen-Anzahl (X 🏆)
- Zeigt die aktuelle Energydrink-Anzahl (X 🥤)
- Tauschen-Button: **50 Trophäen = 1 Energydrink**
  (deaktiviert, solange weniger als 50 Trophäen vorhanden sind)

### Footer (alle Seiten)
- **Fix unten** (sticky footer), **größer** als bisher
- „© LernHub 2026"
- Links zu `/impressum.html` und `/datenschutz.html` (DSGVO)

### Neue Seiten: `impressum.html` + `datenschutz.html`
Vorerst mit **Platzhalter-Inhalt** (echter rechtlicher Text folgt später — siehe
§11 Offene Entscheidungen).

> Hinweis: `tauschen.html`, `impressum.html` und `datenschutz.html` sind hier nur
> **spezifiziert**. Die eigentliche Umsetzung (HTML/JS/CSS) erfolgt in der
> Implementierungsphase — diese Datei bleibt reine Dokumentation.

---

## 10. Vollständige UI-Übersicht

Wo wird was angezeigt?

```text
TOPBAR (alle Seiten)
├── 🥤 Energydrinks (Zahl)
├── ⭐ Globales Level (Zahl)
├── 🏆 Trophäen (Zahl) → Link zu tauschen.html
└── Logout

DASHBOARD
├── Gesamt-EP
├── Globales Level + Fortschrittsbalken
├── Energydrinks heute
└── Trophäen gesamt

FACH-SEITE (pos.html, dbi.html etc.)
├── Stats-Leiste (bereits vorhanden):
│   Fortschritt % · Themen bearbeitet · Quiz-Punkte
├── NEU: Fach-Level-Leiste
│   ├── "Fach-Level X"
│   ├── Fortschrittsbalken (EP bis nächstes Level)
│   └── Richtige Antworten gesamt in diesem Fach
└── Themen-Karten (unverändert):
    ├── Fortschritts-Badge (%)
    └── Abgehakt wenn abgeschlossen

QUIZ (fach.html)
├── Lebensbalken oben (dynamisch)
├── Frage-für-Frage Flow
└── Ergebnis-Screen am Ende:
    ├── Score %
    ├── EP verdient
    ├── Trophäen verdient
    └── Bonus angezeigt

TAUSCHEN-SEITE (neu)
├── Trophäen: X 🏆
├── Energydrinks: X 🥤
└── Tauschen-Button (50 🏆 = 1 🥤)

FOOTER (alle Seiten, fix unten)
├── © LernHub 2026
├── Impressum
└── Datenschutz (DSGVO)
```

---

## 11. Offene Entscheidungen ⚠️

Diese Punkte müssen **vor der Implementierung** geklärt werden:

1. **⚠️ Offen — Energie = 0:** Wird der Quiz-Start komplett **gesperrt** (mit
   Hinweis „Keine Energie mehr"), oder nur eine **Warnung** gezeigt und das Quiz
   bleibt spielbar (dann ggf. ohne EP/Belohnung)?
2. **⚠️ Offen — Täglicher Energie-Reset:** Fix **um Mitternacht** (Serverzeit),
   oder **rollierend 24 h** nach dem letzten Reset?
3. **⚠️ Offen — Impressum/Datenschutz-Inhalt:** vorerst **Platzhalter**, echter
   rechtlicher Inhalt (DSGVO) später?

---
*Erstellt: Juni 2026 | Status: Spezifikation finalisiert, noch nicht implementiert*
