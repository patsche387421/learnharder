# Pläne — Stand und Einordnung (2026-10-15)

## Welcher Plan gilt

| Plan | Status |
|---|---|
| `LEARNHARDER_V2_PROJEKTPLAN_PSP_RISIKEN.md` | **gültig**: Zielbild, PSP, Releases, Risiken |
| `SETUP_DOMAIN_UND_DATENBANK_MIGRATION.md` | **gültig**: Hetzner, Domains. §2 Datenmigration entfällt (E-011) |
| `GAMIFICATION_OEKONOMIE_MODELL.md` | **gültig**: Level-Tabelle seit E-010 korrigiert |
| `DATENMODELL_SCHULE.md` | **gültig**: Abteilungen, Ausbildungsgänge, Klassen, Lehrkräfte, Registrierung (E-013). Schema, Models und CMS umgesetzt, Klassen, Sichtbarkeit und Registrierung offen (§8 Schritte 2–4) |
| World4You-/Plain-PHP-Pläne | überholt und gelöscht (Tag `v1.1.0-w4y-plans-and-review`) |

## Ist-Stand gegen den Plan

| PSP | Inhalt | Stand |
|---|---|---|
| 1.4 | Design-System | ✅ Tokens, `tokens.json`, Komponenten und beide Schriften da |
| 2.1 | Laravel-Setup | 🟡 läuft lokal in WSL2, Postgres (E-007), API für Login, Fächer, Themen und Lerneinheiten (`/api/v1/…`), 169 Backend-Tests |
| 2.2 | Rollen | ✅ Policies für Fach, Thema, Theorie, Frage (autor ohne Löschen, tester nur lesen). Veröffentlichen nur Admin (E-014) |
| 2.3 | CMS | ✅ redaktionstauglich auf Deutsch (E-012): Wertelisten, Antworten, Theorie, Veröffentlichen, Design-System. KI-Übersetzung offen (nach M5) |
| 2.4–2.7 | Spiellogik, Klassen, API, Echtzeit | 🟡 EP, Level, Rang, Prestige und Fach-Level rechnet der Server (E-015, E-016, E-018), EP je Frage einmal pro Tag. Brainpower, Streaks, Trophäen, Klassen und Echtzeit offen |
| 3.x | Vue-Frontend | 🟡 Login, Fächer, Thema, Theorie, Lerneinheit mit Ergebnis (M3), 94 Tests. Hub, Team, Liga und PWA offen |
| 5.x | Tests, CI, Hetzner | 🟡 CI bei jedem Push (Pint, Backend-Tests gegen Postgres, Frontend-Tests und Build). Hetzner offen |
| — | Content-Import aus V1 | ✅ 7 Fächer, 32 Themen, 158 Theorieblöcke, 158 Fragen (lokal). Überschreibt keine gepflegten Themen |
| — | Nutzer-Migration aus Supabase | entfällt, alle registrieren sich neu (E-011) |

## Widersprüche in den Plänen

1. **Laravel 11** steht in beiden Plänen. Installiert ist **Laravel 13.36**.
2. **Datenbank:** „PostgreSQL / MariaDB“. Sail und `.env.example` nutzen Postgres (`pgsql`). Die Pläne nennen noch beides. → entschieden: Postgres (`ENTSCHEIDUNGEN.md` E-007)
3. **Versionen:** Der Setup-Plan nennt `v2.0.0-alpha.1` als Ziel, §4 nennt `v0.1.0`/`v0.2.0`. Beide Tags `v0.1.0` und `v2.0.0-plan` existieren mit derselben Beschreibung. Vorschlag: nur noch `v2.0.0-alpha.N` weiterführen.
4. **Ökonomie-Modell §2:** Die Formel `round(n^1.5 × 8)` passt zu Level 5/10/25/50/100, aber nicht zu Level 1 (Formel ergibt 8, Tabelle sagt 0) und Level 2 (Formel 23, Tabelle 8). Die Spalte „XP zum nächsten Level“ ist dadurch auch falsch. Vor der Umsetzung festlegen, ob die Schwelle für Level n `(n−1)` oder `n` verwendet. → entschieden: `(n−1)` wie in V1, Tabelle korrigiert (`ENTSCHEIDUNGEN.md` E-010)
5. **Schritt 1 des Plans** („Abstimmung mit Professoren/Lehrkräften“) ist nirgends als erledigt vermerkt.
6. **Setup-Plan §2:** beschreibt die Übernahme der Supabase-Nutzer samt bcrypt-Hashes. → entfällt, alle registrieren sich neu (`ENTSCHEIDUNGEN.md` E-011)
7. **Setup-Plan §3:** Die App spricht mit `api.learnharder.app`, Staging liegt unter `staging.app…` und `staging.api…`. → entschieden: App und API unter einer Adresse, Staging unter `staging.learnharder.app` (`ENTSCHEIDUNGEN.md` E-019)

## Einschätzung (Projektleitung)

Der Plan ist fachlich gut (Server-Autorität, Datenschutz, saubere Rollen), aber **das MVP ist für ein Ein-Personen-Projekt zu groß**.
Es enthält Landing Page mit Newsletter, KI-Übersetzung, PWA-Offline, Brainpower-Ökonomie, CI/CD und Staging.
Der kritische Pfad zum ersten echten Nutzer ist kürzer:

**Hetzner-Server steht → API mit Login → Lern-Screens → Supabase-Nutzer migriert → DNS umgestellt.**

### Vorschlag: Meilensteine

| M | Ziel | Fertig, wenn … |
|---|---|---|
| **M0** ✅ | Aufräumen | Struktur steht, Archiv entschieden, `backend/laravel` entfernt, Seeder abgesichert |
| **M1** ✅ | Backend-Fundament | DB entschieden, `routes/api.php` + Sanctum-Login, Policies, erste Feature-Tests, CI (Pint + Tests) |
| **M2** ✅ | Inhalte pflegbar | CMS mit Antwortoptionen, Wertelisten, Deutsch (E-012); Import mit Prüfung statt stillem Fallback |
| **M3** ✅ | Lernen funktioniert | Login → Fächer → Thema → Theorie → Quiz → Ergebnis mit serverseitigen XP/Level, Vue mit `lh-`-Komponenten. EP je Frage einmal pro Tag (E-015), Ränge (E-016), ohne Klassenfilter (E-017), Fach-Level (E-018) |
| **M3b** | Klassen und Sichtbarkeit | Klassen und Einschreibungen in Filament, App zeigt nur Fächer der eigenen Klasse (Plan Schule §8 Schritte 2–3) |
| **M4** | Hetzner läuft | Prod-Stack in `infra/`, TLS, tägliche Backups, Staging |
| **M5** | Start | DNS umgestellt, Registrierung offen, Neuregistrierung angekündigt, V1-Supabase abgeschaltet → Tag `v2.0.0-alpha.1` |
| danach | | Brainpower-Einsatz, Streaks, Trophäen → Lehrkräfte/Klassen → Englisch + KI-Übersetzung → Landing Page → Reverb |

### Größte Risiken jetzt

1. **Prod-Daten aus Supabase.** Seit 2026-10-15 gesichert (E-009). Kein Import, V2 startet ohne V1-Nutzer (E-011).
2. **Umfang.** Ohne gekürztes MVP (R4 im Plan) gibt es keinen Go-Live.
3. **Framework-Versionen.** Entschieden: Filament 5 (E-008). Translatable-Plugin geprüft, vorerst keins, CMS nur Deutsch (E-012).
