# Aufgaben (Stand 2026-10-17)

Alles Offene an einer Stelle. Die Testzentrale (Tab „Projekt“) und Claude lesen diese Datei.
Meilensteine und Reihenfolge: `plans/README.md`. Entscheidungen: `ENTSCHEIDUNGEN.md`.

- Eine Zeile ist eine Aufgabe. Erledigt → `[x]`, am Session-Ende entfernen (steht dann im Session-Report).
- Wer handelt: `@claude`, `@patsche`, `@schule` (Lehrkräfte, Sekretariat, Schul-IT).
- `seit:JJJJ-MM-TT`: seit wann wir auf jemanden warten.
- `!fokus`: höchstens eine Aufgabe, an der gerade gearbeitet wird.

## M4 — Hetzner läuft

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
- [ ] Hub schlank, Team und Liga aus der Tab-Leiste (E-022) @claude
- [ ] Löschlauf nach 12 Monaten und Hinweis-Mail 30 Tage vorher per Scheduler (E-028) @claude
- [ ] Erneute Zustimmung bei neuer `terms_version` @claude
- [ ] DNS umstellen, Neuregistrierung ankündigen, V1-Supabase abschalten @patsche

## Nebenbei

- [ ] Verschlüsselte Zweitkopie des Supabase-Dumps (E-009) @patsche
- [ ] Design-Artefakt: Häkchen und Gefahr-Button in die Komponententabelle der README, `lastChange` im Index, danach Kopie `design/README.md` @claude
- [ ] `DemoSchoolSeeder`: prüfen, warum MEDT keine Themen bekommt @claude
- [ ] Unit-Test für die Weiterleitung nach einer Sperre in `main.js` @claude
- [ ] Python-Tests aus `scripts/` in die CI @claude
- [ ] Testzentrale fertig durchklicken: Details, Suche, „Ältere ausblenden“ im Log @patsche

## Später (nach dem Start, E-021)

- [ ] Klarnamen klären: Der Projektplan nennt „Klarnamensicht“ für Lehrkräfte, E-025 speichert für Schüler:innen keine Namen. Vor dem Lehrkraft-Dashboard @patsche
- [ ] Personal anonymisieren @claude
- [ ] Zwei-Faktor-Anmeldung fürs CMS @claude
- [ ] EP zurücksetzen @claude
- [ ] Änderungsverlauf für Inhalte @claude
