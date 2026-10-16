# CLAUDE.md — backend/app/Filament/ (Rolle: CMS / Redaktion)

Filament-5-Adminpanel (E-008) unter `/admin` für Autor:innen, Tester:innen und Admins.
Allgemeine Regeln: `../../../CLAUDE.md`, Backend-Regeln: `../../CLAUDE.md`.

## Ziel

Autor:innen erfassen Fächer, Themen, Theorie (Markdown) und Fragen samt Antwortoptionen,
bis nach M5 nur auf Deutsch (E-012). Workflow: `draft` → `review` → `published` (E-014).

## Regeln

- Feste Wertelisten (`type`, `status`, `difficulty`) als `Select` mit Enum, nie als freies Textfeld.
- Antwortoptionen werden direkt in der Frage gepflegt (Repeater), inklusive Markierung der richtigen Antwort.
- Übersetzbare Felder vorerst nur auf Deutsch, direkt auf den Eintrag der JSON-Spalte (`TextInput::make('title.de')`). Kein Translatable-Plugin (E-012).
- Relationen auf JSON-Spalten (z. B. `topic.title`) über `getOptionLabelFromRecordUsing` anzeigen, nicht über die Rohspalte.
- Löschen und Veröffentlichen sind nur über Policies erlaubt (autor: kein Löschen und kein Veröffentlichen, tester: nur lesen). Kommentare gibt es noch nicht.
- Panel-Farben und Schrift kommen aus dem Design-System (`frontend/src/styles/tokens.css`): Bronze-Palette und `Color::Stone` im `AdminPanelProvider`, Atkinson Hyperlegible Next als Kopie in `public/fonts/`. Ändert sich `--accent` oder die Schrift im Frontend, hier nachziehen.
- Fehlermeldungen nennen das Label unverändert (`Field::configureUsing` im `AppServiceProvider`), Filament würde es kleinschreiben.
- Filament siezt im Deutschen. Texte, die das CMS zeigt, stehen in du-Form in `lang/vendor/filament-*/de/` (nur die geänderten Schlüssel). Kommt eine Funktion dazu (Profil, Passwort zurücksetzen …), ihre Texte dort ergänzen.
- Was Postgres ablehnt (Eindeutigkeit, auch ohne Groß-/Kleinschreibung), prüft auch das Formular. Löschen nur anbieten, wenn kein `RESTRICT` greift, sonst gibt es einen Serverfehler. Fächer, Themen und Fragen mit Lernverlauf verweigern das Löschen im Model (`BlocksDeletionWhileInUse`), das CMS sagt dann, was stattdessen geht (`DeletesOnlyUnused`).

## Stand (2026-10-16)

Die Ressourcen `SubjectResource`, `TopicResource` und `QuestionResource` haben deutsche Feldnamen, Sprachfelder schreiben auf `.de`,
Fach und Thema erscheinen auf Deutsch. Status, Typ und Schwierigkeit sind Enums (`ContentStatus`, `QuestionType`, `Difficulty`), neue Inhalte starten als Entwurf.
Antworten pflegt das Fragenformular direkt (2–6, sortierbar). Bei `single_choice` schaltet eine richtige Antwort die anderen aus, beim Speichern gilt: genau eine richtige bei `single_choice`, mindestens eine bei `multiple_choice`.
Im Fragenformular kommt erst das Fach, dann das Thema. Kürzel prüft das Formular auf Format und Eindeutigkeit, Felder haben kurze Hilfetexte.
Veröffentlichen dürfen nur Admins (`ContentPolicy::publish`, E-014): „Veröffentlicht“ ist sonst nicht wählbar, Änderungen an Veröffentlichtem gehen zurück in Prüfung. Gemeinsame Logik in `Resources/Concerns/`.
Theorie pflegt das Thema im Relation Manager „Theorie“ (Markdown, ohne Uploads). Ändern Autor:innen sie, geht ein veröffentlichtes Thema zurück in Prüfung.

Schulstruktur (Gruppe „Schule“, nur Admin über `SchoolStructurePolicy`): `SchoolResource` mit Mail-Domains, Abteilungen und Schuljahren als Relation Manager, `ProgramResource` mit dem Lehrplan, `SchoolClassResource` mit den Relation Managern „Schüler:innen“ (eintragen, austragen statt löschen, eine aktive Klasse pro Schuljahr) und „Lehrkräfte“ (Fach, Lehrkraft, Semester).

Konten (`UserResource`, nur Admin über `UserPolicy`, E-025/E-027): Personal und Schüler:innen unter 14 (nur mit bestätigtem Elternformular) anlegen, ohne Passwort. Rollen ändern, sperren, entsperren, Gamer-Tag zurücksetzen und Schüler:innen löschen laufen über `App\Support\Accounts`. Lehnt eine Schutzregel ab, meldet das CMS „Nicht gespeichert“ mit dem Grund. Gamer-Tag-Regeln stehen in `App\Support\GamerTag`. In der Liste liegen „Gamer-Tag zurücksetzen“ und „Löschen“ im ⋮-Menü, sonst passt die Zeile nicht.
