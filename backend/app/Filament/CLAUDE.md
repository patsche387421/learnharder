# CLAUDE.md — backend/app/Filament/ (Rolle: CMS / Redaktion)

Filament-5-Adminpanel (E-008) unter `/admin` für Autor:innen, Tester:innen und Admins.
Allgemeine Regeln: `../../../CLAUDE.md`, Backend-Regeln: `../../CLAUDE.md`.

## Ziel

Autor:innen erfassen Fächer, Themen, Theorie (Markdown) und Fragen samt Antwortoptionen,
auf Deutsch zuerst, Englisch optional. Workflow: `draft` → `review` → `published`.

## Regeln

- Feste Wertelisten (`type`, `status`, `difficulty`) als `Select` mit Enum, nie als freies Textfeld.
- Antwortoptionen werden direkt in der Frage gepflegt (Repeater oder RelationManager), inklusive Markierung der richtigen Antwort.
- Übersetzbare Felder vorerst nur auf Deutsch, direkt auf den Eintrag der JSON-Spalte (`TextInput::make('title.de')`). Kein Translatable-Plugin (E-012).
- Relationen auf JSON-Spalten (z. B. `topic.title`) über `getOptionLabelFromRecordUsing` anzeigen, nicht über die Rohspalte.
- Löschen und Veröffentlichen sind nur über Policies erlaubt (autor: kein Löschen und kein Veröffentlichen, tester: nur lesen und kommentieren).
- Panel-Farben und Schrift an das Design-System anlehnen (`frontend/src/styles/tokens.css`), nicht `Color::Amber`.
- Fehlermeldungen nennen das Label unverändert (`Field::configureUsing` im `AppServiceProvider`), Filament würde es kleinschreiben.
- Was Postgres ablehnt (Eindeutigkeit, auch ohne Groß-/Kleinschreibung), prüft auch das Formular. Löschen nur anbieten, wenn kein `RESTRICT` greift, sonst gibt es einen Serverfehler.

## Stand (2026-10-15)

Die Ressourcen `SubjectResource`, `TopicResource` und `QuestionResource` haben deutsche Feldnamen, Sprachfelder schreiben auf `.de`,
Fach und Thema erscheinen auf Deutsch. Status, Typ und Schwierigkeit sind Enums (`ContentStatus`, `QuestionType`, `Difficulty`), neue Inhalte starten als Entwurf.
Antworten pflegt das Fragenformular direkt (2–6, sortierbar). Bei `single_choice` schaltet eine richtige Antwort die anderen aus, beim Speichern gilt: genau eine richtige bei `single_choice`, mindestens eine bei `multiple_choice`.
Im Fragenformular kommt erst das Fach, dann das Thema. Kürzel prüft das Formular auf Format und Eindeutigkeit, Felder haben kurze Hilfetexte.
Veröffentlichen dürfen nur Admins (`ContentPolicy::publish`, E-014): „Veröffentlicht“ ist sonst nicht wählbar, Änderungen an Veröffentlichtem gehen zurück in Prüfung. Gemeinsame Logik in `Resources/Concerns/`.

Schulstruktur (Gruppe „Schule“, nur Admin über `SchoolStructurePolicy`): `SchoolResource` mit Mail-Domains, Abteilungen und Schuljahren als Relation Manager, `ProgramResource` mit dem Lehrplan.
