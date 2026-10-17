# Pläne — Stand und Einordnung (2026-10-17)

## Welcher Plan gilt

| Plan | Status |
|---|---|
| `LEARNHARDER_V2_PROJEKTPLAN_PSP_RISIKEN.md` | **gültig**: Zielbild, PSP, Releases, Risiken |
| `SETUP_DOMAIN_UND_DATENBANK_MIGRATION.md` | **gültig**: Hetzner, Domains. §2 Datenmigration entfällt (E-011) |
| `GAMIFICATION_OEKONOMIE_MODELL.md` | **gültig**: Level-Tabelle seit E-010 korrigiert |
| `KONZEPT_SPIELWELT.md` | **Entwurf**: Hub, Erfolge, Avatar, Shop, Rangliste, Landing Page (E-030) |
| `DATENMODELL_SCHULE.md` | **gültig**: Abteilungen, Ausbildungsgänge, Klassen, Lehrkräfte, Registrierung (E-013). §8 Schritte 1–4 umgesetzt (bis M4a), offen nur §7.2 |
| World4You-/Plain-PHP-Pläne | überholt und gelöscht (Tag `v1.1.0-w4y-plans-and-review`) |

## Ist-Stand gegen den Plan

| PSP | Inhalt | Stand |
|---|---|---|
| 1.4 | Design-System | ✅ Tokens, `tokens.json`, Komponenten und beide Schriften da |
| 2.1 | Laravel-Setup | 🟡 läuft lokal in WSL2, Postgres (E-007), API für Login, Registrierung, Passwort, Konto löschen, Rechtstexte, Fächer, Themen, Lerneinheiten und Hub (`/api/v1/…`), Sichtbarkeit nach Klasse (E-024), Mail lokal über Mailpit (E-026), 271 Backend-Tests |
| 2.2 | Rollen | ✅ Policies für Fach, Thema, Theorie, Frage (autor ohne Löschen, tester nur lesen). Veröffentlichen nur Admin (E-014) |
| 2.3 | CMS | ✅ redaktionstauglich auf Deutsch (E-012): Wertelisten, Antworten, Theorie, Veröffentlichen, Design-System. KI-Übersetzung offen (nach M5) |
| 2.4–2.7 | Spiellogik, Klassen, API, Echtzeit | 🟡 EP, Level, Rang, Prestige und Fach-Level rechnet der Server (E-015, E-016, E-018), EP je Frage einmal pro Tag. Klassen mit Einschreibungen und Lehrkräften im CMS (M3b). Konten im CMS mit Sperren und Admin-Schutz (M4a, E-027). Brainpower, Streaks, Trophäen, Join-Codes und Echtzeit offen |
| 3.x | Vue-Frontend | 🟡 Login, Fächer, Thema, Theorie, Lerneinheit mit Ergebnis (M3), Hinweis ohne Klasse (M3b), Registrierung, Passwort vergessen, Rechtstexte und Konto löschen (M4a), Hub mit Weiterlernen (G1), Avatar-Engine mit Platzhalter-Teilen im Hub und im Profil (G4, E-031 bis E-034), 153 Tests. Team, Liga und PWA offen |
| 5.x | Tests, CI, Hetzner | 🟡 CI bei jedem Push (Pint, Backend-Tests gegen Postgres, Frontend-Tests und Build). Hetzner offen |
| — | Content-Import aus V1 | ✅ 7 Fächer, 32 Themen, 158 Theorieblöcke, 158 Fragen (lokal). Überschreibt keine gepflegten Themen |
| — | Nutzer-Migration aus Supabase | entfällt, alle registrieren sich neu (E-011) |

## Widersprüche in den Plänen

1. **Laravel 11** steht in beiden Plänen. Installiert ist **Laravel 13.36**.
2. **Datenbank:** „PostgreSQL / MariaDB“. Sail und `.env.example` nutzen Postgres (`pgsql`). Die Pläne nennen noch beides. → entschieden: Postgres (`ENTSCHEIDUNGEN.md` E-007)
3. **Versionen:** Der Setup-Plan nennt `v2.0.0-alpha.1` als Ziel, §4 nennt `v0.1.0`/`v0.2.0`. Beide Tags `v0.1.0` und `v2.0.0-plan` existieren mit derselben Beschreibung. Vorschlag: nur noch `v2.0.0-alpha.N` weiterführen. → entschieden: nur `v2.0.0-alpha.N`, `v0.1.0` gibt es nicht mehr (`ENTSCHEIDUNGEN.md` E-020)
4. **Ökonomie-Modell §2:** Die Formel `round(n^1.5 × 8)` passt zu Level 5/10/25/50/100, aber nicht zu Level 1 (Formel ergibt 8, Tabelle sagt 0) und Level 2 (Formel 23, Tabelle 8). Die Spalte „XP zum nächsten Level“ ist dadurch auch falsch. Vor der Umsetzung festlegen, ob die Schwelle für Level n `(n−1)` oder `n` verwendet. → entschieden: `(n−1)` wie in V1, Tabelle korrigiert (`ENTSCHEIDUNGEN.md` E-010)
5. **Schritt 1 des Plans** („Abstimmung mit Professoren/Lehrkräften“) ist nirgends als erledigt vermerkt. → noch nicht passiert, wird vor dem Start nachgeholt (Patsche, 2026-10-16, siehe M5)
6. **Setup-Plan §2:** beschreibt die Übernahme der Supabase-Nutzer samt bcrypt-Hashes. → entfällt, alle registrieren sich neu (`ENTSCHEIDUNGEN.md` E-011)
7. **Setup-Plan §3:** Die App spricht mit `api.learnharder.app`, Staging liegt unter `staging.app…` und `staging.api…`. → entschieden: App und API unter einer Adresse, Staging unter `staging.learnharder.app` (`ENTSCHEIDUNGEN.md` E-019)

## Einschätzung (Projektleitung)

Der Plan ist fachlich gut (Server-Autorität, Datenschutz, saubere Rollen), aber **das MVP ist für ein Ein-Personen-Projekt zu groß**.
Es enthält Landing Page mit Newsletter, KI-Übersetzung, PWA-Offline, Brainpower-Ökonomie, CI/CD und Staging.
Der kritische Pfad zum ersten echten Nutzer ist kürzer:

**Hetzner-Server steht → API mit Login → Lern-Screens → Klassen und Registrierung → DNS umgestellt.** (Keine Nutzer-Migration, E-011.)

### Meilensteine (Reihenfolge bestätigt 2026-10-16: M3b → M4a, parallel M4 → M5; Spielwelt-Tracks seit 2026-10-17, E-030)

| M | Ziel | Fertig, wenn … |
|---|---|---|
| **M0** ✅ | Aufräumen | Struktur steht, Archiv entschieden, `backend/laravel` entfernt, Seeder abgesichert |
| **M1** ✅ | Backend-Fundament | DB entschieden, `routes/api.php` + Sanctum-Login, Policies, erste Feature-Tests, CI (Pint + Tests) |
| **M2** ✅ | Inhalte pflegbar | CMS mit Antwortoptionen, Wertelisten, Deutsch (E-012); Import mit Prüfung statt stillem Fallback |
| **M3** ✅ | Lernen funktioniert | Login → Fächer → Thema → Theorie → Quiz → Ergebnis mit serverseitigen XP/Level, Vue mit `lh-`-Komponenten. EP je Frage einmal pro Tag (E-015), Ränge (E-016), ohne Klassenfilter (E-017), Fach-Level (E-018) |
| **M3b** ✅ | Klassen und Sichtbarkeit | Klassen und Einschreibungen in Filament, App zeigt nur Fächer der eigenen Klasse (Plan Schule §8 Schritte 2–3, E-024) |
| **M4a** ✅ | Ankommen | Registrierung mit Mail-Code (Plan Schule §8 Schritt 4, E-025), „Passwort vergessen“, Datenschutzerklärung, Impressum, Nutzungsregeln und Elternformular als Entwurf und von der Schule geprüft (E-023), eigenes Konto löschen. Benutzerverwaltung im CMS mit Sperren und Schutz des letzten Admins (E-027), `last_login_at` für die Frist (E-028). Mail lokal über Mailpit (E-026). Offen: Rechtstexte von der Schule prüfen lassen (Platzhalter `[[…]]`) |
| **M4** ⏸ | Hetzner läuft | Prod-Stack in `infra/` (Caddy nach E-019), TLS, tägliche Backups mit Restore-Test, Staging, Mailversand aus der EU. Patsche führt am Server aus. Pausiert, bis die Schule den Vertragspartner nennt (Stand 2026-10-17) |
| **M5** | Start | Lernablauf, EP-Regeln und Brainpower-Konzept mit betreuenden Lehrkräften abgestimmt, Hub schlank, Team und Liga ausgeblendet (E-022), DNS umgestellt, Registrierung offen, Neuregistrierung angekündigt, V1-Supabase abgeschaltet → Tag `v2.0.0-alpha.1` (E-020) |
| **G1** | Hub & Profil | Hub statt Platzhalter: Begrüßung nach Zustand und Tageszeit, Level, Brainpower, „Weiterlernen“. Team und Liga aus der Tab-Leiste (E-022). Profil mit Statistik (Konzept Spielwelt §4) |
| **G2** | Brainpower & Streaks | Aufladen, Streak, Booster und Challenge nach Ökonomie-Modell §3, Werte in `config/` (§5) |
| **G3** | Erfolge | Katalog im CMS, Vergabe auf dem Server, Raster im Profil (§6) |
| **G4** | Avatar | Figur aus SVG-Teilen mit `idle`, `celebrate`, `wave`, `think`, im Hub und auf dem Ergebnis (§7) |
| **G5** | Shop | Teile und Animationen freischalten, dann mit Brainpower kaufen. Preise im Ökonomie-Modell §3.4 (§7) |
| **G6** | Rangliste | Nach Entscheidung O-006, nur Gamer-Tags der eigenen Klasse (§8) |
| **L1** | Landing Page | Ersetzt die Wartungsseite, Demo-Lerneinheit ohne Login, ohne Hetzner (§10) |
| danach | | Lehrkraft-Dashboard → Englisch + KI-Übersetzung → PWA-Offline → Reverb |

### Nächste Schritte (Stand 2026-10-17)

1. **G1 fertig machen:** Der Server liefert Fakten für den Hub (letzter Lerntag, Tage weg, noch nicht gesehener Aufstieg). Daraus entstehen die Hub-Zustände und die Stimmung des Roboters in einem Schritt. Danach Tab-Leiste (E-022), nach dem Login zum Hub, Profil mit Statistik.
2. **G4 weiter, sobald Patsche liefert:** Werkzeug je Fach und Starter-Kit je Abteilung festlegen, Teile mit KI-Hilfe entwerfen. Dann Abteilung in `/me`, Starter-Kits, Avatar speichern (Server prüft den Besitz), Antippen, Porträt, `celebrate` auf dem Ergebnis.
3. **G2 Brainpower und Streaks:** Erst damit bekommt der Akku echte Werte und der Shop (G5) eine Währung.
4. **Danach** G3 Erfolge, G5 Shop, Werkhalle, G6 Rangliste. M4 geht weiter, sobald die Schule antwortet.

### Größte Risiken jetzt

1. **Umfang.** MVP gekürzt (E-021). Mit den Spielwelt-Tracks (E-030) wächst die Liste wieder. Der Start wartet deshalb nicht auf sie, und neue Wünsche kommen ins Konzept, nicht in M5.
2. **Prod-Daten aus Supabase.** Seit 2026-10-15 gesichert (E-009). Kein Import, V2 startet ohne V1-Nutzer (E-011).
3. **Framework-Versionen.** Entschieden: Filament 5 (E-008). Translatable-Plugin geprüft, vorerst keins, CMS nur Deutsch (E-012).
