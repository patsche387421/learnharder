# Level-System — LernHub

## Übersicht
Das Gamification-System besteht aus vier Komponenten:
Energie, Lebensbalken, Trophäen und Erfahrungspunkte (EP).

> Aktualisiert Juni 2026 — war vorher: „Es ist noch NICHT implementiert".
> Das System ist teilweise aktiv (EP, Trophäen, Energie, Lebensbalken im
> Tagesquiz). Offene Punkte sind mit ⚠️ gekennzeichnet.

## Aktueller Stand
Das Gamification-System ist für das **Tagesquiz** vollständig aktiv.
Das **Themen-Quiz (Aufgaben-Tab)** vergibt EP und Trophäen, hat aber
keinen Lebensbalken und verbraucht keine Energie.

---

## 1. Energie 🥤

> Aktualisiert Juni 2026 — war vorher: „1 Energydrink wird beim Quiz-Start
> verbraucht" (galt pauschal für alle Quiz-Typen).

- **Tagesquiz:** kostet 1 Energydrink beim Start
- **Themen-Quiz (Aufgaben):** kostet KEINE Energie — unbegrenzt spielbar
- Energie = 0 beim Tagesquiz → Seite zeigt `#screen-gesperrt` (Quiz gesperrt,
  kein Warndialog — Offene Entscheidung §11.1 damit beantwortet)
- 50 Trophäen können gegen 1 Energydrink eingetauscht werden (Tausch-Seite)

### Energie-Regeneration ⚠️ Geplant, noch nicht implementiert
- 1 Energydrink pro Tag automatisch, Deckel bei 5
- Reset-Modus (Mitternacht vs. rollierend 24 h) noch nicht entschieden — §11.2

## 2. Lebensbalken ❤️

> Aktualisiert Juni 2026 — war vorher: „Existiert nur während eines laufenden
> Quiz" ohne Unterscheidung nach Quiz-Typ.

- **Nur im Tagesquiz** — `tagesquiz.html` zeigt `#leben-fill` dynamisch
- **Kein Lebensbalken im Themen-Quiz (Aufgaben)** — `app.js` übergibt immer
  `lebenProzent: 100`; `fach.html` hat keine Lebensbalken-UI
- **Dynamisch:** Schaden pro falscher Antwort = `100 / Anzahl_Fragen` %
  - Beispiel (Tagesquiz, 5 Fragen): 20 % pro Fehler
  - Beispiel (Tagesquiz, 8 Fragen): 12,5 % pro Fehler
- Bei 0 % Leben: Quiz läuft weiter, aber keine EP und kein Bonus; Trophäen
  werden trotzdem vergeben
- Nach Quiz-Ende wird der Lebensbalken verworfen

## 3. Trophäen 🏆
- +1 Trophäe pro richtiger Antwort
- Werden IMMER vergeben — auch bei gescheitertem Quiz (Leben = 0)
- Gilt für **beide** Quiz-Typen (Tagesquiz und Themen-Quiz)
- Persistent im User-Account (`user_stats.trophies`)
- Einlösbar: 50 Trophäen = 1 Energydrink

## 4. Erfahrungspunkte (EP) & Level ⭐

> Aktualisiert Juni 2026 — war vorher: „5 EP pro richtige Antwort" (pauschal
> für alle Quiz-Typen). Jetzt typ-abhängig.

### Themen-Quiz (Aufgaben-Tab in fach.html)
- **1 EP** pro richtige Antwort
- **Kein Completion-Bonus**
- Kein Lebensbalken → `lebenProzent` wird immer als 100 übergeben
- Mehrfach spielbar (zum Trophäen-Grinden)

### Tagesquiz (tagesquiz.html)
- **5 EP** pro richtige Antwort
- **Completion-Bonus bei erfolgreichem Abschluss (Leben > 0 %):**

| Score    | Bonus EP |
|----------|----------|
| > 50 %   | +10 EP   |
| > 70 %   | +20 EP   |
| > 90 %   | +35 EP   |
| = 100 %  | +50 EP   |

- Bei Leben = 0 %: keine EP, kein Bonus (Trophäen zählen trotzdem)
- 1 Versuch pro Tag (kontrolliert über `daily_quiz_log`)

### Wichtige Regeln (beide Quiz-Typen)
- EP werden erst AM ENDE des Quiz gebucht (nicht live)
- Bei gescheitertem Quiz (Leben = 0 %): keine EP, keine Buchung
- Trophäen werden trotzdem vergeben

### Verteilung
- Gesamt-EP → globales User-Level (`user_stats.total_xp`)
- EP pro Fach → eigenes Fach-Level (`subject_xp.xp`)
- `subject_xp` wird nur aktualisiert wenn `ep > 0` und `fachId` bekannt

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

## 6. Datenbank-Erweiterungen (aktiv)

> Aktualisiert Juni 2026 — war vorher: „geplant". Tabellen existieren und
> werden aktiv genutzt (Migration 003).

### user_stats (aktiv)
Spalten: `user_id`, `total_xp`, `level`, `energy`, `trophies`, `updated_at`

### subject_xp (aktiv)
```sql
CREATE TABLE subject_xp (
  user_id uuid REFERENCES auth.users,
  subject_id text,
  xp int DEFAULT 0,
  level int DEFAULT 1,
  correct_answers int DEFAULT 0,
  PRIMARY KEY (user_id, subject_id)
);
```

### daily_quiz_log (aktiv)
Speichert pro Tagesquiz-Versuch: `score`, `xp_earned`, `trophies_earned`,
`success` (ob Leben > 0). Verhindert Mehrfach-Spielen pro UTC-Tag.

## 7. Fragen-Pool & Versuche

> Aktualisiert Juni 2026 — war vorher: Beschreibt einen geplanten Zustand.
> Tatsächlicher Stand von Themen-Quiz und Tagesquiz abweichend.

### Tatsächlicher Stand (Juni 2026)

**Themen-Quiz (Aufgaben):**
- Lädt ALLE Fragen aus `<thema>_fragen.json` (keine Zufallsauswahl)
- Lösungen werden erst beim Klick „Auswerten" aus `_antworten.json` geladen
- Anzahl Versuche pro Tag: nicht limitiert (kein Counter implementiert)

**Tagesquiz:**
- Lädt aktuell `src/assets/data/tagesquiz_test.json` (5 statische Testfragen)
- Fragen werden vor Anzeige zufällig gemischt (`.sort(() => Math.random() - 0.5)`)
- 1 Versuch pro Tag (via `daily_quiz_log`)
- Endgültige Quelle: Supabase `content_items`-Tabelle (DATA_MIGRATION_V2.md)

### Geplant (noch nicht implementiert)
- Zufällige Auswahl von 6–10 Fragen aus einem größeren Pool (Standard: 8)
- Versuchs-Limits pro Thema/Tag (3 normal, 10 Lernphase)
- Übungsmodus (keine EP, keine Trophäen)

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
Die Topbar enthält:
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
- **Fix unten** (sticky footer)
- „© LernHub 2026"
- Links zu `/impressum.html` und `/datenschutz.html` (DSGVO)

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

THEMEN-SEITE / AUFGABEN (fach.html)
├── Kein Lebensbalken
├── Alle Fragen auf einmal (Formular)
├── „Auswerten"-Button lädt Lösungen nach
└── Ergebnis: X von N richtig — +<ep> EP, +<trophien> 🏆

TAGESQUIZ (tagesquiz.html)
├── Lebensbalken oben (dynamisch, nur hier)
├── Frage-für-Frage Flow
└── Ergebnis-Screen am Ende:
    ├── Score %
    ├── EP verdient (inkl. Bonus)
    ├── Trophäen verdient
    └── Bonus angezeigt

TAUSCHEN-SEITE (tauschen.html)
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

1. ✅ **Gelöst — Energie = 0:** Tagesquiz-Start ist bei Energie = 0 komplett
   gesperrt (`#screen-gesperrt` in `tagesquiz.html`). Kein Warndialog.

2. **⚠️ Offen — Energie-Regeneration:** Derzeit kein automatisches Aufladen
   implementiert. Geplant: +1 Energydrink pro Tag, Deckel bei 5. Modus
   (Mitternacht UTC vs. rollierend 24 h) noch nicht entschieden. Wird als
   Supabase-Funktion oder scheduled Edge-Function umgesetzt.

3. **⚠️ Offen — Impressum/Datenschutz-Inhalt:** vorerst Platzhalter, echter
   rechtlicher Inhalt (DSGVO) folgt später.

---
*Erstellt: Juni 2026 | Aktualisiert: Juni 2026 | Status: Teilweise implementiert*
