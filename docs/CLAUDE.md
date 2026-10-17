# CLAUDE.md — docs/ (Rolle: Projektleitung & Produkt)

Pläne, Entscheidungen und Produktkonzepte für V2. Allgemeine Regeln: `../CLAUDE.md`.

## Inhalt

| Datei | Zweck |
|---|---|
| `plans/README.md` | Welcher Plan gilt, Ist-Stand, Widersprüche, nächste Meilensteine |
| `plans/LEARNHARDER_V2_PROJEKTPLAN_PSP_RISIKEN.md` | Gesamtplan: Rollen, PSP, Releases, Risiken |
| `plans/SETUP_DOMAIN_UND_DATENBANK_MIGRATION.md` | Hetzner, Domains, Datenmigration, Versionen |
| `plans/GAMIFICATION_OEKONOMIE_MODELL.md` | XP-, Level- und Brainpower-Formeln |
| `plans/DATENMODELL_SCHULE.md` | Schule, Abteilungen, Klassen, Lehrkräfte, Registrierung |
| `ENTSCHEIDUNGEN.md` | Entscheidungslog: was entschieden ist, was offen ist |
| `handbuch/` | Anleitungen für Menschen, die mit learnharder arbeiten (Redaktion, später Betrieb) |
| `AUFGABEN.md` | Alle offenen Aufgaben mit Zuständigkeit, Quelle für den Projekt-Tab der Testzentrale |
| `design/` | Brand Book, Sprache & Glossar, Komponenten-Anleitungen. Kopie aus dem Design-System-Artefakt, Änderungen dort machen (siehe `frontend/src/styles/CLAUDE.md`) |

## Regeln

- **Code schlägt Doku.** Wenn ein Plan und der Code sich widersprechen, den Widerspruch in `plans/README.md` notieren, nicht stillschweigend eine Seite „korrigieren“.
- Neue Entscheidungen bekommen eine Nummer in `ENTSCHEIDUNGEN.md` (Datum, Entscheidung, Begründung, Folgen).
- Überholte Dokumente: erst nach `_archiv/docs/` verschieben, Patsche entscheidet über Löschen (Git-Historie bleibt).
- Konzepte aus V1 (`_archiv/docs/KONZEPT_*.md`) werden bei Bedarf hier **neu** geschrieben, mit V2-Begriffen (Brainpower statt Energie, Laravel statt Supabase).
- Pläne beschreiben das Was und Warum. Wie es umgesetzt ist, steht im Code und in den CLAUDE.md der Ordner.
