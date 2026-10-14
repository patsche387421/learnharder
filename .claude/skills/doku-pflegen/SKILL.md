---
name: doku-pflegen
description: Regeln für Doku in learnharder.app – CLAUDE.md-Rollendateien, docs/, Entscheidungslog und Session-Report knapp, korrekt und am Code geprüft halten. Nutzen, wenn Doku geschrieben, abgeglichen oder aufgeräumt wird.
---

# Doku pflegen (learnharder.app)

## Grundsätze

1. **Code schlägt Doku.** Jede Aussage über Code (Pfad, Klasse, Spalte, Version, Befehl) vor dem Schreiben mit Grep/Glob/Read prüfen. Nicht prüfbar → nicht schreiben oder als offen markieren.
2. **Qualität vor Menge.** Lieber eine Zeile streichen als eine hinzufügen. Keine Wiederholung dessen, was im Code, in `composer.json`/`package.json` oder in einer anderen Doku-Datei schon steht: verlinken statt kopieren.
3. **Deutsch**, kurze Sätze, aktive Form. Code-Bezeichner bleiben englisch und stehen in Backticks.
4. **Datum** bei Ist-Ständen als `Stand JJJJ-MM-TT`. Keine relativen Angaben („gestern“, „bald“).
5. **Datenschutz/Sicherheit:** keine Secrets, keine Passwörter, keine Klarnamen von Schüler:innen.

## Wo steht was

| Art | Datei | Regel |
|---|---|---|
| Regeln einer Rolle | `<ordner>/CLAUDE.md` | Zweck, Zuständigkeit, Regeln, Stand. Max. ~40 Zeilen. Kein Tutorial. |
| Was/Warum, Reihenfolge | `docs/plans/*.md` | Widerspruch zum Code → in `docs/plans/README.md` „Widersprüche“ eintragen, Plan nicht still ändern |
| Entscheidungen | `docs/ENTSCHEIDUNGEN.md` | Neue Nummer `E-NNN`: Datum, Entscheidung, Warum, Folgen. Offene Fragen als `O-NNN` |
| Session-Verlauf | `agent/SESSION_REPORT.md` | Nur unten anhängen, nie ändern (Vorlage unten) |
| Einstieg für Menschen | `README.md` (Root) | Nur Aufbau + Start-Befehle |

**Nie anfassen:** `_archiv/` (nur lesen), `public/` (wird öffentlich ausgeliefert), Code-Dateien.

## Ablauf

1. Auftrag und betroffene Commits/Dateien lesen.
2. Betroffene Doku-Stellen finden (`Grep` nach Pfaden, Klassennamen, Versionen).
3. Jede Aussage gegen den Code prüfen. Veraltetes streichen oder korrigieren, „Stand“-Datum anpassen.
4. Ergebnis melden: geänderte Dateien, je ein Satz „was und warum“, offene Widersprüche. Nicht stagen, nicht committen.

## Vorlage Session-Report

```markdown
## JJJJ-MM-TT — <Thema>

- **Branch:** `<branch>`
- **Commits:** `<hash>` (<kurz>), …
- **Ergebnis:** <2–5 Punkte>
- **Geprüft:** <was lief, was nicht>
- **Offen:** <nächste Schritte>
```
