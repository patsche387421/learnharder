# CLAUDE.md — backend/app/Filament/ (Rolle: CMS / Redaktion)

Filament-5-Adminpanel (E-008) unter `/admin` für Autor:innen, Tester:innen und Admins.
Allgemeine Regeln: `../../../CLAUDE.md`, Backend-Regeln: `../../CLAUDE.md`.

## Ziel

Autor:innen erfassen Fächer, Themen, Theorie (Markdown) und Fragen samt Antwortoptionen,
auf Deutsch zuerst, Englisch optional. Workflow: `draft` → `review` → `published`.

## Regeln

- Feste Wertelisten (`type`, `status`, `difficulty`) als `Select` mit Enum, nie als freies Textfeld.
- Antwortoptionen werden direkt in der Frage gepflegt (Repeater oder RelationManager), inklusive Markierung der richtigen Antwort.
- Übersetzbare Felder brauchen ein Plugin für Spatie Translatable, damit Deutsch und Englisch getrennt bearbeitet werden. Für Filament 5 kommt `lara-zeus/spatie-translatable` (v2) in Frage, noch nicht installiert.
- Relationen auf JSON-Spalten (z. B. `topic.title`) über `getOptionLabelFromRecordUsing` anzeigen, nicht über die Rohspalte.
- Löschen und Veröffentlichen sind nur über Policies erlaubt (autor: kein Löschen und kein Veröffentlichen, tester: nur lesen und kommentieren).
- Panel-Farben und Schrift an das Design-System anlehnen (`frontend/src/styles/tokens.css`), nicht `Color::Amber`.

## Stand (2026-10-14)

Die Ressourcen `SubjectResource`, `TopicResource` und `QuestionResource` sind automatisch generiert und noch nicht redaktionstauglich:
Antwortoptionen fehlen, Wertelisten sind Freitext, und die Sprachfelder sind nicht getrennt.
