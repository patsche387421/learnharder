# Testhandbuch (Stand 2026-10-17)

So testest du learnharder: automatische Tests ausführen und schreiben, und Zustände in der App von Hand mit festen Testpersonen prüfen.
Inhalte pflegen: `redaktion.md`. Konten und Klassen verwalten: `betrieb.md`. Regeln zum Behalten und Löschen von Tests: E-039 in `../ENTSCHEIDUNGEN.md`.

## Was es gibt

| Teil | Wo | Werkzeug | Prüft |
|---|---|---|---|
| Backend | `backend/tests/Feature`, `backend/tests/Unit` | PHPUnit über Sail | API, Spielregeln, Rechte, CMS, Datenbank-Regeln |
| Frontend | `*.test.js` direkt neben der Datei, die geprüft wird | Vitest mit jsdom | Seiten, Bausteine, Store, Router |
| Testzentrale | `scripts/test_*.py` | `unittest` | die Auswertung der Testzentrale selbst |
| Abläufe im echten Browser | noch keine | Playwright, geplant | z. B. registrieren → lernen → Level-Up |

Tests sind Code und liegen im Git neben dem Code, den sie prüfen. Wer einen Test geschrieben oder zuletzt geändert hat, zeigt `git log` oder `git blame` auf die Datei. Ein eigenes Autor-Feld gibt es nicht.

## Datenbanken

| Datenbank | Wofür | Was Tests damit machen |
|---|---|---|
| `laravel` | Entwicklung: was du in App und CMS siehst, auch die Testpersonen | nichts |
| `testing` | automatische Backend-Tests | Jeder Test beginnt mit einer leeren Datenbank (`RefreshDatabase`) |
| Produktion | gibt es noch nicht, kommt mit dem Hetzner-Server (M4) | – |

Welche Datenbank App und Tests nutzen, steht in `backend/.env` und `backend/phpunit.xml`. Die Testzentrale zeigt beides in der Übersicht unter „Datenbanken“ und warnt, wenn beide dieselbe wären.

## Tests ausführen

**In der Testzentrale** (`scripts/dev.py web`, http://localhost:8090), Tab „Tests“:

- „Alle Tests“, nur Backend oder nur Frontend.
- „Gezielt testen“: eine Gruppe mit einem Klick, z. B. Testnutzer, Lernen, Brainpower und Booster, Hub und Shop. Welche Dateien zu einer Gruppe gehören, steht in `scripts/dev.py` (`TEST_GROUPS`).
- Häkchen „Danach Testpersonen zurücksetzen“: nach jedem Testlauf aus der Testzentrale die Testpersonen zurücksetzen. So startest du danach frisch ins Ausprobieren.
- Jeder Lauf landet im Verlauf (die letzten 50) mit Ergebnis, Dauer, Branch und Commit. „Ausgabe“ zeigt das ganze Protokoll.

**Im Terminal:**

```bash
cd backend && ./vendor/bin/sail artisan test                          # alle Backend-Tests
cd backend && ./vendor/bin/sail artisan test --filter=BoosterApiTest  # eine Klasse oder ein Testname
cd backend && ./vendor/bin/sail artisan test tests/Feature/Factories  # ein Ordner
npm --prefix frontend run test                                        # alle Frontend-Tests
npm --prefix frontend run test -- src/views/ShopView.test.js          # eine Datei
python3 -m unittest discover -s scripts                               # Testzentrale
```

Beim Arbeiten reichen die betroffenen Tests. **Vor jedem Commit laufen alle**, dazu `sail pint --test` (Code-Stil) und im Frontend `npm --prefix frontend run build` und `run check:tokens`. Die CI auf GitHub macht bei jedem Push dasselbe noch einmal. Eine Reihenfolge nach Wichtigkeit gibt es nicht: Es laufen immer alle, ein roter Test macht den ganzen Lauf rot.

## Logs

- Backend-Tests schreiben nach `backend/storage/logs/testing.log`, getrennt vom App-Log. Fehler dort sind meist gewollt, weil Tests Fehlerfälle absichtlich auslösen.
- Die Testzentrale zeigt sie im Tab „Log“ unter „Tests“, zusammen mit Fehlern der Datenbank `testing`.
- Läufe der CI stehen auf GitHub unter „Actions“.

## Testpersonen (von Hand prüfen)

Sieben feste Konten in der Demo-Klasse 2AHIT, nur lokal. Anmelden in der App mit `<name>@test.learnharder.app` und dem Passwort der Demo-Konten (`learnharder123`).

| Anmeldung | Zustand | Damit prüfst du |
|---|---|---|
| `neuling@…` | Level 1, nichts gelernt | erster Besuch, kein Streak-Chip |
| `mittendrin@…` | Level 12, Streak 6 seit gestern, 15 Brainpower | „Noch nichts gelernt heute“, zu wenig Brainpower für Booster |
| `boosterfan@…` | Level 30 (Silber), Streak 3 heute, 2×-Booster für 25 Min. | Marken in Hub und Shop, „Danach kannst du den nächsten starten“ |
| `fastprestige@…` | Level 100, 10 EP vor Prestige 1 | Prestige-Aufstieg mit einer Lerneinheit |
| `prestigeprofi@…` | Prestige 2, Level 10, Streak 40 | Prestige-Ring, Brainpower-Maximum 200 |
| `ohneklasse@…` | Schüler:in ohne Klasse | Hinweis „Noch keiner Klasse zugeordnet“ |
| `gesperrt@…` | gesperrtes Konto | Weiterleitung nach dem Login |

- **Zurücksetzen:** Button in der Testzentrale oder `./vendor/bin/sail artisan db:seed --class=TestPersonasSeeder`. Das löscht die Testpersonen samt Lernverlauf und legt sie im Ausgangszustand neu an. Andere Konten bleiben.
- „Neu aufsetzen“ in der Testzentrale legt sie nach Import und Demo-Klasse ebenfalls an.
- Der Stand jeder Testperson steht in der Testzentrale (alle 10 Sekunden, `artisan learnharder:test-personas`).
- Die Personen stehen in `backend/database/seeders/TestPersonasSeeder.php`. Eine neue Testperson kommt dort und in den Test `TestPersonasSeederTest` dazu.

## Einen Test schreiben

### Grundregeln

- **Ein Test prüft ein Verhalten** und heißt auf Deutsch so, wie man es erzählen würde: `test_booster_zaehlt_bis_zur_wiener_mitternacht`, im Frontend `it('zeigt den Streak als ersten Chip …')`.
- **So prüfen, wie die App es sieht:** im Backend über die API (`GET /api/v1/me`, `POST …`), nicht über interne Spalten. Im Frontend über das, was auf dem Bildschirm steht, und über `aria-label`.
- **Zeit festlegen**, wenn Tage oder Uhrzeiten eine Rolle spielen: `$this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'))`, im Frontend `vi.setSystemTime(…)`. Der Spieltag gilt in Wien.
- **Ein behobener Fehler bekommt einen Test**, der ohne die Korrektur rot wäre (siehe E-039).

### Backend: Testnutzer:innen mit den Bausteinen

Die `UserFactory` und die `SchoolClassFactory` bauen jeden Zustand in einer Zeile. Die Bausteine lassen sich kombinieren:

| Baustein | Ergebnis |
|---|---|
| `student()` | Rolle Schüler:in, ohne Klasse |
| `inClass()`, `inClass($klasse)` | Schüler:in in einer neuen oder in der angegebenen Klasse |
| `level(25)`, `level(1, prestige: 2)` | Gesamt-EP genau am Anfang dieses Levels |
| `xp(7990)` | Gesamt-EP direkt |
| `streak(6)`, `streak(3, today: true, longest: 9)` | Streak in Gefahr (zuletzt gestern) oder heute schon gelernt |
| `brainpower(20)` | Brainpower 20, heute schon aufgeladen |
| `booster(2.0, minutes: 23)` | laufender EP-Booster |
| `SchoolClass::factory()->stage(3)->semester()->withSubjects($pos)` | Klasse im 3. Abschnitt, Kolleg, mit POS im Lehrplan |

Beispiel:

```php
public function test_zu_wenig_brainpower_fuer_den_grossen_booster(): void
{
    $this->travelTo(CarbonImmutable::parse('2026-10-17 14:00', 'Europe/Vienna'));
    $user = User::factory()->inClass()->level(12)->brainpower(15)->create();

    $this->actingAs($user)->postJson('/api/v1/boosters', ['kind' => 'x2_60'])->assertUnprocessable();
}
```

Ändert sich der Zustand mitten im Test (erst gesperrt, dann Level 2 und offen), dann den Wert im Test selbst ändern. Die Bausteine gelten nur beim Anlegen.

API-Tests brauchen am Anfang die Zeilen aus den bestehenden Tests (`sanctum.stateful` und den `Origin`-Header), damit Anfragen wie aus der App kommen. Bei zwei Personen in einem Test vor dem Wechsel `$this->flushSession()`. Mehr Regeln: `backend/CLAUDE.md`.

### Frontend

- Die Testdatei liegt neben der Komponente: `HubView.vue` → `HubView.test.js`.
- Anfragen an die API ersetzt `mockFetch(…)` aus `src/test/api.js`. Seiten mit Router hängt `mountRoute(…)` aus `src/test/router.js` ein.
- Icons über `icons['name']` vergleichen, nicht über den Dateinamen.
- Jeder `Lh`-Baustein hat einen eigenen Test. Mehr Regeln: `frontend/CLAUDE.md`.

## Tests behalten, ändern, löschen (E-039)

Ein Test beschreibt, wie die App sich **jetzt** verhalten soll.

| Fall | Was mit dem Test passiert |
|---|---|
| Funktion oder Screen wird **bewusst** geändert | Test im selben Commit anpassen. Der Diff zeigt dann, was sich am Verhalten ändert |
| Funktion wird **entfernt** | Test im selben Commit löschen |
| Test für einen **behobenen Fehler** | bleibt für immer, er verhindert, dass der Fehler zurückkommt |
| Test wird rot, **ohne dass sich etwas ändern sollte** | Das ist ein Fund. Ursache suchen, den Test nicht löschen oder abschwächen, damit es grün wird |
| Zwei Tests prüfen dasselbe | einen behalten, den mit dem klareren Namen |

Übersprungene Tests (`markTestSkipped`, `it.skip`) gibt es nicht dauerhaft. Wer einen überspringt, trägt in `docs/AUFGABEN.md` ein, warum und bis wann.
