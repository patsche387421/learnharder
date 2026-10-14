# Pläne — Stand und Einordnung (2026-10-14)

## Welcher Plan gilt

| Plan | Status |
|---|---|
| `LEARNHARDER_V2_PROJEKTPLAN_PSP_RISIKEN.md` | **gültig**: Zielbild, PSP, Releases, Risiken |
| `SETUP_DOMAIN_UND_DATENBANK_MIGRATION.md` | **gültig**: Hetzner, Domains, Datenmigration |
| `GAMIFICATION_OEKONOMIE_MODELL.md` | **gültig, mit Fehlern** (siehe unten) |
| World4You-/Plain-PHP-Pläne | überholt und gelöscht (Tag `v1.1.0-w4y-plans-and-review`) |

## Ist-Stand gegen den Plan

| PSP | Inhalt | Stand |
|---|---|---|
| 1.4 | Design-System | ✅ Tokens und Komponenten da (Font und `tokens.json` fehlen) |
| 2.1 | Laravel-Setup | 🟡 läuft lokal, DB nicht entschieden, keine API, keine Tests |
| 2.2 | Rollen | 🟡 Rollen da, keine Policies |
| 2.3 | CMS | 🟡 generiert, noch nicht redaktionstauglich |
| 2.4–2.7 | Spiellogik, Klassen, API, Echtzeit | ⬜ nur Tabellen |
| 3.x | Vue-Frontend | ⬜ Gerüst |
| 5.x | Tests, CI, Hetzner | ⬜ |
| — | Content-Import aus V1 | ✅ 7 Fächer, 32 Themen, 158 Theorieblöcke, 158 Fragen (lokal) |
| — | Nutzer-Migration aus Supabase | ⬜ |

## Widersprüche in den Plänen

1. **Laravel 11** steht in beiden Plänen. Installiert ist **Laravel 13.35**.
2. **Datenbank:** „PostgreSQL / MariaDB“. Sail nutzt Postgres, `.env.example` SQLite. → `ENTSCHEIDUNGEN.md` O-001
3. **Versionen:** Der Setup-Plan nennt `v2.0.0-alpha.1` als Ziel, §4 nennt `v0.1.0`/`v0.2.0`. Beide Tags `v0.1.0` und `v2.0.0-plan` existieren mit derselben Beschreibung. Vorschlag: nur noch `v2.0.0-alpha.N` weiterführen.
4. **Ökonomie-Modell §2:** Die Formel `round(n^1.5 × 8)` passt zu Level 5/10/25/50/100, aber nicht zu Level 1 (Formel ergibt 8, Tabelle sagt 0) und Level 2 (Formel 23, Tabelle 8). Die Spalte „XP zum nächsten Level“ ist dadurch auch falsch. Vor der Umsetzung festlegen, ob die Schwelle für Level n `(n−1)` oder `n` verwendet.
5. **Schritt 1 des Plans** („Abstimmung mit Professoren/Lehrkräften“) ist nirgends als erledigt vermerkt.

## Einschätzung (Projektleitung)

Der Plan ist fachlich gut (Server-Autorität, Datenschutz, saubere Rollen), aber **das MVP ist für ein Ein-Personen-Projekt zu groß**.
Es enthält Landing Page mit Newsletter, KI-Übersetzung, PWA-Offline, Brainpower-Ökonomie, CI/CD und Staging.
Der kritische Pfad zum ersten echten Nutzer ist kürzer:

**Hetzner-Server steht → API mit Login → Lern-Screens → Supabase-Nutzer migriert → DNS umgestellt.**

### Vorschlag: Meilensteine

| M | Ziel | Fertig, wenn … |
|---|---|---|
| **M0** | Aufräumen | Struktur steht, Archiv entschieden, `backend/laravel` entfernt, Seeder abgesichert |
| **M1** | Backend-Fundament | DB entschieden, `routes/api.php` + Sanctum-Login, Policies, erste Feature-Tests, CI (Pint + Tests) |
| **M2** | Inhalte pflegbar | CMS mit Antwortoptionen, Wertelisten, de/en; Import mit Prüfung statt stillem Fallback |
| **M3** | Lernen funktioniert | Login → Fächer → Thema → Theorie → Quiz → Ergebnis mit serverseitigen XP/Level, Vue mit `lh-`-Komponenten |
| **M4** | Hetzner läuft | Prod-Stack in `infra/`, TLS, tägliche Backups, Staging |
| **M5** | Umzug | Supabase-Export importiert, 10 Accounts geprüft, DNS umgestellt → Tag `v2.0.0-alpha.1` |
| danach | | Brainpower-Einsatz, Streaks, Trophäen → Lehrkräfte/Klassen → Englisch + KI-Übersetzung → Landing Page → Reverb |

### Größte Risiken jetzt

1. **Prod-Daten nur in Supabase.** Ohne Traffic kann ein Free-Projekt pausiert werden. → Bald einen Dump ziehen und sicher ablegen (nicht ins Repo).
2. **Umfang.** Ohne gekürztes MVP (R4 im Plan) gibt es keinen Go-Live.
3. **Framework-Versionen.** Filament 3 auf Laravel 13: vor dem CMS-Ausbau klären, ob Filament 4 der bessere Weg ist.
