# TESTING.md — Manuelle Testfälle (User-Checkliste)

Manuelle End-to-End-Checkliste für die **Live-Seite**. Als eingeloggter User durchspielen und
die Status-Spalte ausfüllen. Ergänzt die code-nahe Node-/Diff-Verifikation der Sessions um
echte Klick-Tests im Browser.

**Login:** `schueler1@lernhub.htl` / `lernhub123`

**Vorgehen:** Testfälle der Reihe nach durchspielen, Status eintragen, Abweichungen im
Abschnitt „Befund" unten notieren.

**Status-Legende:** ✅ OK · ❌ Fehler · ⚠️ Abweichung · — offen (noch nicht getestet)

> Hinweis: Manche Fälle setzen einen bestimmten Ausgangszustand voraus (z. B. Energie = 0
> oder = 5). Energie lädt automatisch +1 pro UTC-Kalendertag auf (Deckel 5); für Extremzustände
> ggf. mehrere Herausforderungen spielen (Energie senken) bzw. Trophäen tauschen (Energie erhöhen).

## Testfälle

| ID | Seite / URL | Schritte | Erwartetes Ergebnis | Status |
|----|-------------|----------|---------------------|--------|
| T-001 | `/fach.html?fach=<thema>` (Themen-Quiz) | Ein Thema öffnen, alle Fragen beantworten, „Auswerten" klicken. | Ergebniszeile zeigt „X von N richtig — +<EP> EP, +<Trophäen> 🏆" (1 EP + 1 🏆 pro richtige Antwort). Energie bleibt unverändert (Themen-Quiz kostet keine). | — |
| T-002 | `/tagesquiz.html` (Herausforderung) | Energie-Zahl in der Topbar merken, Herausforderung starten. | Energie in der Topbar ist um genau **1** gesunken. | — |
| T-003 | `/tagesquiz.html` | Herausforderung zu Ende spielen, dann „Nochmal" klicken. | Neuer Versuch startet sofort; Energie sinkt erneut um 1 (solange Energie > 0). Kein „schon gespielt"-Block. | — |
| T-004 | `/tagesquiz.html` | Mit Energie = 0 die Seite öffnen. | Statt des Quiz erscheint der Sperr-Screen (`#screen-gesperrt`); kein Start möglich, kein Warndialog. | — |
| T-005 | `/tauschen.html` | Mit **voller Energie (5/5)** die Tausch-Seite öffnen. | „Tauschen"-Button ist deaktiviert; Hinweis „Deine Energie ist bereits voll (5 / 5) — kein Tausch nötig." erscheint. | — |
| T-006 | `/tauschen.html` | Mit Energie **< 5** und **≥ 50 Trophäen** den Tausch-Button klicken. | Erfolgsmeldung „Tausch erfolgreich! +1 Energydrink erhalten."; Energie +1, Trophäen −50; Topbar-Werte aktualisiert. | — |
| T-007 | Topbar (alle Seiten) | Nach EP-Gewinn (T-001/T-002) das Level-Badge in der Topbar prüfen. | Badge zeigt die aus den Gesamt-EP berechnete Level-Zahl; Hexagon-Farbe passt zum Tier (bronze < 25, silber < 50, gold < 75, platin ≥ 75). | — |
| T-008 | `/tagesquiz.html` (Ergebnis-Screen) | Zyklus-Abschluss erreichen (Level 100 = 8.000 EP gesamt). | Nach dem Quiz erscheint die Prestige-Feier („Prestige N erreicht!"). ⚠️ **Manuell schwer testbar** — erfordert ~8.000 EP; realistisch nur mit DB-Vorbereitung prüfbar. | — |
| T-009 | `/dashboard.html` | Dashboard nach Login öffnen, Begrüßung lesen. | „Hey, Schueler!" — der Anzeigename ist gesäubert: kein roher E-Mail-Präfix („schueler1"), keine angehängten Ziffern, Erstbuchstabe groß. | — |
| T-010 | Topbar (Herausforderung → Dashboard/andere Seite) | Nach einer Herausforderung auf eine andere Seite wechseln, Energie-Pill vergleichen. | Energie-Anzeige in der Topbar ist überall konsistent und zeigt den aktuellen Wert nach Abzug. | — |

## Befund

**Stand:** — (noch nicht durchlaufen)
**Datum:** —
**Getestet:** —
**Offen / Abweichungen:** —

_(Nach dem ersten Durchlauf ausfüllen: Datum, welche IDs ✅/❌/⚠️, offene Punkte und Abweichungen.)_
