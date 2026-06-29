# Roadmap — LearnHarder

Diese Datei ist die **zentrale Single Source of Truth für die Reihenfolge** der
anstehenden Arbeit. Die inhaltlichen Details bleiben in den Detail-Dokumenten
(`docs/BUGS.md`, `docs/IDEEN.md`, `docs/KONZEPT_*.md`) — die Roadmap verweist nur darauf
und legt fest, was wann kommt.

## Aktuelle Phase — UI-Politur

| Session | Scope (1 Satz) | Status |
|---|---|---|
| **S1** | Roadmap + Vision als Doku festhalten (diese Session) | in Arbeit |
| **S2** | Token-Konsolidierung: Brand/Border/Shadow als Semantik-Token, `.btn`-Basis + Button-Token, Hardcodes raus | offen |
| **S3** | UI-Politur: Icons vereinheitlichen, Energie-Pill `X/5`, Level-Rang-Badge pro Band | offen |
| **S4** | Dashboard-Rework: Begrüßung raus, Premium-Look via Shadow-Token | offen |

## Danach (Bestand)

Bereits dokumentierte Aufgaben, die nach der UI-Politur-Phase folgen:

- **fix/trophy-shop** — Trophy-Shop wieder erreichbar machen (Energydrink-Kauf), siehe `docs/IDEEN.md` / BUG-011.
- **Public-Page-Topbar** — Topbar-Darstellung für ausgeloggte Nutzer auf `impressum.html` / `datenschutz.html`.
- **Bereich 5** — Touch-Targets auf mindestens 44 px für mobile Bedienbarkeit.
- **dev→main Deploy + Direktorin-Pitch** — produktiver Deploy und Präsentation des Stands an die Direktion.

## Zukunftsvision (Phase 2+)

Über die UI-Politur und den Daten-Migrations-Plan (`docs/DATA_MIGRATION_V2.md`) hinaus
ist das langfristige Ziel ein **schulweit einsetzbares, mandantenfähiges Lernsystem**
mit Schule-/Abteilung-/Klassen-Struktur und Lehrer-/Schüler-Rollen. Diese Vision ist
als eigenes Konzept festgehalten und kommt bewusst erst nach der aktuellen Phase —
Details in `docs/KONZEPT_MANDANTEN.md`.
