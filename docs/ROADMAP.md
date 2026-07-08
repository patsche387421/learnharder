# Roadmap — LearnHarder

Diese Datei ist die **zentrale Single Source of Truth für die Reihenfolge** der
anstehenden Arbeit. Die inhaltlichen Details bleiben in den Detail-Dokumenten
(`docs/BUGS.md`, `docs/IDEEN.md`, `docs/KONZEPT_*.md`) — die Roadmap verweist nur darauf
und legt fest, was wann kommt.

## Abgeschlossen — UI-Politur (Juni–Juli 2026)

| Session | Scope | Status |
|---|---|---|
| **S1** | Roadmap + Vision als Doku festhalten | ✅ erledigt |
| **S2** | Token-Konsolidierung: `.btn`-Basis + Button-/Shadow-Token, Hardcodes raus | ✅ erledigt ¹ |
| **S3** | UI-Politur: Icons vereinheitlichen, Level-Rang-Badge pro Band | ✅ erledigt ² |
| **S4** | Dashboard-Rework: Premium-Look via Shadow-Token | ✅ erledigt ³ |

¹ `--btn-shadow` bewusst nicht eingeführt (Buttons nutzen keinen eigenen Shadow-Token).
² Energie-Pill `X/5` war Teil des S3-Scopes und ist **nicht** umgesetzt → eigene Session (S6).
³ Teil-Scope „Begrüßung raus" wurde durch BUG-014 revidiert: die Begrüßung bleibt und
  wird stattdessen personalisiert (`Auth.displayName()`, Merge `e260320`).

## Aktuelle Phase — Qualität & Abschluss

| Session | Scope (1 Satz) | Status |
|---|---|---|
| **S5** | Manuellen Testlauf T-001–T-005 live durchspielen und `tests/runs/…/ergebnis.md` füllen | offen |
| **S6** | Energie-Pill in der Topbar auf `X/5` umstellen (`layout.js:269`) | offen |
| **S7** | Touch-Targets auf mind. 44 px für mobile Bedienbarkeit (ehem. „Bereich 5") | offen |
| **S8** | `docs/design/components/nav.md` aufräumen: TODOs Z. 22–24 sind erledigt (docs-only) | offen |
| **S9** | Nav-Breite „Herausforderung" auf Desktop prüfen — kann bei S5 mitlaufen | offen |

## Danach (Bestand)

- **Account-Löschung (DSGVO Art. 17)** — mehrere Tabellen + Auth-Layer, siehe `docs/IDEEN.md`.
- **BUG-006** — SQL-Migration `004_level_log.sql`; ohne sie zeigt die Profil-Grafik nur einen Punkt.
- **BUG-007** — `docs/DESIGN_GUIDELINES.md` auf das aktuelle `tokens.css`-System aktualisieren.
- **BUG-008** — Content-Render-Modul für Code/Listen/Bilder/Tabellen in Theorie & Quiz.
- **BUG-003** — EP-Buchung einmalig manuell in Supabase gegenprüfen.
- **Direktorin-Pitch** — Präsentation des Stands an die Direktion.

> `dev → main` ist kein Roadmap-Punkt mehr, sondern Routine (zuletzt `e73d94a`).

## Zukunftsvision (Phase 2+)

Über die UI-Politur und den Daten-Migrations-Plan (`docs/DATA_MIGRATION_V2.md`) hinaus
ist das langfristige Ziel ein **schulweit einsetzbares, mandantenfähiges Lernsystem**
mit Schule-/Abteilung-/Klassen-Struktur und Lehrer-/Schüler-Rollen. Diese Vision ist
als eigenes Konzept festgehalten und kommt bewusst erst nach der aktuellen Phase —
Details in `docs/KONZEPT_MANDANTEN.md`.
