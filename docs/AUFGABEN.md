# Aufgaben (Stand 2026-10-17)

Alles Offene an einer Stelle. Die Testzentrale (Tab „Projekt“) und Claude lesen diese Datei.
Meilensteine und Reihenfolge: `plans/README.md`. Entscheidungen: `ENTSCHEIDUNGEN.md`.

- Eine Zeile ist eine Aufgabe. Erledigt → `[x]`, am Session-Ende entfernen (steht dann im Session-Report).
- Wer handelt: `@claude`, `@patsche`, `@schule` (Lehrkräfte, Sekretariat, Schul-IT).
- `seit:JJJJ-MM-TT`: seit wann wir auf jemanden warten.
- `!fokus`: höchstens eine Aufgabe, an der gerade gearbeitet wird.

## M4 — Hetzner läuft (pausiert, E-030)

- [ ] Antwort der Lehrkraft: Wer ist Vertragspartner und datenschutzrechtlich verantwortlich? @schule seit:2026-10-16
- [ ] Hetzner-Konto mit Zwei-Faktor-Anmeldung, Projekt „learnharder“, noch kein Server @patsche
- [ ] M4 planen: Server, DNS, Backup-Ziel, Deploy-Weg, Staging @claude
- [ ] Backups in `infra/` beschreiben: verschlüsselt, offsite, append-only, Aufbewahrung, Alarm, Restore-Test, Runbook @claude
- [ ] Scaleway TEM einrichten, DNS-Einträge SPF, DKIM, DMARC (E-026) @patsche
- [ ] Mails über die Queue senden @claude
- [ ] Logs täglich rotieren (`LOG_STACK=daily`, 14 Tage) @claude

## M5 — Start

- [ ] Rechtstexte: Platzhalter `[[…]]` füllen und prüfen lassen (E-023) @schule
- [ ] Absender `learnharder.app` von der Schul-IT freigeben lassen (E-026) @schule
- [ ] Offizielle Klassen- und Ausbildungsgang-Kürzel (Plan Schule §7.2) @schule
- [ ] Lernablauf, EP-Regeln und Brainpower-Konzept mit betreuenden Lehrkräften abstimmen @patsche
- [ ] Mindestens zwei Admins, einer davon von der Schule (E-027) @schule
- [ ] Löschlauf nach 12 Monaten und Hinweis-Mail 30 Tage vorher per Scheduler (E-028) @claude
- [ ] Erneute Zustimmung bei neuer `terms_version` @claude
- [ ] DNS umstellen, Neuregistrierung ankündigen, V1-Supabase abschalten @patsche

## G1 — Hub & Profil

- [ ] Team und Liga aus der Tab-Leiste (E-022) @claude
- [ ] Hub-Zustände: erster Besuch, Pause, Aufstieg, alles erledigt, Tageszeit @claude
- [ ] Profil mit Statistik: EP, Level, Rang, Fach-Level, längster Streak @claude
- [ ] Nach dem Login zum Hub statt zu `/learn` (`meta.guest` im Router-Guard) @claude

## G2 — Brainpower & Streaks

- [ ] Brainpower und Streaks nach Ökonomie-Modell §3, Werte in `config/` (Konzept Spielwelt §5) @claude

## G3 — Erfolge

- [ ] „Erfolge“ ins Glossar des Design-Artefakts, danach Kopie `design/Sprache.md` @claude
- [ ] Erfolge: Katalog im CMS, Vergabe auf dem Server, Raster im Profil (Konzept Spielwelt §6) @claude

## G4 — Avatar

- [ ] Avatar-Teile mit KI-Hilfe entwerfen und als PNG an Claude geben, Stil in Konzept Spielwelt §7 (E-031) @patsche
- [ ] Werkzeug je Fach und Starter-Kit je Abteilung festlegen (Konzept Spielwelt §7) @patsche
- [ ] Avatar-Engine mit Platzhalter-Teilen: Skelett, 8 Slots, Lackierung, Stimmungen, Posen (Konzept Spielwelt §7, E-031, E-033) @claude
- [ ] Werkhalle: Klassenseite mit Posen, Klassenprojekt, Meilensteine (Opt-in) und Reaktionen (E-034) @claude

## G5 — Shop

- [ ] Shop-Preise als §3.4 ins Ökonomie-Modell @claude
- [ ] Shop: freischalten, dann mit Brainpower kaufen @claude

## G6 — Rangliste

- [ ] O-006 entscheiden: Grundlage der Rangliste @patsche

## L1 — Landing Page

- [ ] Inhalt und Technik der Landing Page festlegen (Konzept Spielwelt §10) @patsche

## Nebenbei

- [ ] Verschlüsselte Zweitkopie des Supabase-Dumps (E-009) @patsche
- [ ] Design-Artefakt: Häkchen und Gefahr-Button in die Komponententabelle der README, `lastChange` im Index, danach Kopie `design/README.md` @claude
- [ ] Design-Artefakt: Icons für Spielhalle und Shop. Der Hub nutzt vorläufig `icon-campaign` und `icon-trophy` @claude
- [ ] `DemoSchoolSeeder`: prüfen, warum MEDT keine Themen bekommt @claude
- [ ] Unit-Test für die Weiterleitung nach einer Sperre in `main.js` @claude
- [ ] Python-Tests aus `scripts/` in die CI @claude
- [ ] Fach im CMS: Feld „Icon“ nutzt die App nicht, Bild und Farbe hängen am URL-Kürzel (`subject-<kürzel>`, `color_token` nicht im Formular). Klären, dann Formular und `handbuch/redaktion.md` anpassen @claude
- [ ] Testzentrale fertig durchklicken: Details, Suche, „Ältere ausblenden“ im Log @patsche
- [ ] Testzentrale bei Handy-Breite breiter als der Bildschirm (Kopfzeile, Übersicht) @claude
- [ ] Handbücher im laufenden CMS gegenlesen: Namen der Knöpfe und Bereiche (`docs/handbuch/`) @patsche

## Übergabe (E-029)

- [ ] API-Referenz mit Scramble, Version gepinnt, nur lokal @claude
- [ ] Architektur-Überblick auf einer Seite mit Grafik: App, API, CMS, Datenbank, Mail, Server @claude
- [ ] Klassen-Docblocks für Models, Policies und Controller (28 Klassen ohne) @claude
- [ ] Statische Analyse mit Larastan in der CI, niedrig anfangen und Stufe für Stufe anheben @claude
- [ ] ESLint im Frontend und in der CI @claude
- [ ] Einstieg für neue Entwickler:innen im README prüfen: frisches Setup nach Anleitung durchspielen @patsche

## Später (nach dem Start, E-021)

- [ ] Klarnamen klären: Der Projektplan nennt „Klarnamensicht“ für Lehrkräfte, E-025 speichert für Schüler:innen keine Namen. Vor dem Lehrkraft-Dashboard @patsche
- [ ] Personal anonymisieren @claude
- [ ] Zwei-Faktor-Anmeldung fürs CMS @claude
- [ ] EP zurücksetzen @claude
- [ ] Änderungsverlauf für Inhalte @claude
