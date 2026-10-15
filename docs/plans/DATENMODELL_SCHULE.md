# Datenmodell Schule, Klassen und Registrierung (Plan, Stand 2026-10-15)

Wer sieht welche Inhalte, wer unterrichtet was, und wie kommen Schüler:innen in die App.
Vorlage ist die HTL Mödling. Entscheidung: E-013 in `../ENTSCHEIDUNGEN.md`.
Umgesetzt (Stand 2026-10-15): Tabellen und Datenbankregeln aus §3, Schritt 1 aus §8 (Models und Filament für die Schulstruktur). Offen: Schritte 2–4.

## 1. Begriffe

| Begriff | Tabelle | Beispiel |
|---|---|---|
| Schule | `schools` | HTL Mödling |
| Abteilung | `departments` | Elektrotechnik, Wirtschaftsingenieure (12 an der HTL Mödling) |
| Ausbildungsgang | `programs` | Höhere Lehranstalt Elektrotechnik (5 Jahre), Kolleg Informatik-Systemtechnik (4 Semester) |
| Abschnitt (`stage`) | – | Jahrgang 1–5 oder Semester 1–n, je nach `programs.unit` |
| Lehrplan | `curriculum_entries` | POS kommt im Ausbildungsgang X in den Abschnitten 1–5 vor |
| Schuljahr | `school_years` | 2026/27 |
| Klasse | `school_classes` | 2AHIT im Schuljahr 2026/27 |

Ausbildungsformen (`programs.form`): `higher_technical` (Höhere Lehranstalt, 5 Jahre), `technical_school` (Fachschule, 4 Jahre),
`add_on_course` (Aufbaulehrgang, 5 Semester), `college` (Kolleg, 4 Semester), `preparatory_course` (Vorbereitungslehrgang).

## 2. ERD

```
schools ─┬─< school_email_domains
         ├─< departments ──< programs ──< curriculum_entries >── subjects ──< topics ──< …
         └─< school_years
programs, school_years ──< school_classes ──┬─< class_enrollments >── users (student)
                                            └─< teaching_assignments >── users (teacher), subjects
school_classes.head_teacher_id → users      school_classes.previous_class_id → school_classes
```

## 3. Tabellen

Neue Tabellen mit UUID (Laravel erzeugt UUIDv7). `users` bleibt bei `bigint`.

| Tabelle | Spalten | Schlüssel und Regeln |
|---|---|---|
| `schools` (gibt es) | `name`, `city`, `country` | `domain` fällt weg → `school_email_domains` |
| `school_email_domains` | `school_id`, `domain`, `allows_registration` | `UNIQUE(lower(domain))`. Mehrere Domains pro Schule (Schüler:innen und Lehrkräfte) |
| `departments` | `school_id`, `code`, `name`, `color_token`, `is_active` | `UNIQUE(school_id, code)`. Kein JSON, Schuldaten werden nicht übersetzt |
| `programs` | `department_id`, `code`, `name`, `form`, `unit` (`year` \| `semester`), `duration`, `is_active` | `UNIQUE(department_id, code)`, `CHECK(duration >= 1)` |
| `curriculum_entries` | `program_id`, `subject_id`, `stage_from`, `stage_to` | `UNIQUE(program_id, subject_id)`, `CHECK(stage_from >= 1 AND stage_from <= stage_to)` |
| `school_years` | `school_id`, `name`, `starts_on`, `second_term_starts_on`, `ends_on` | `UNIQUE(school_id, name)`, `CHECK(starts_on < second_term_starts_on AND second_term_starts_on < ends_on)` |
| `school_classes` (ersetzt `classes`) | `program_id`, `school_year_id`, `name`, `stage`, `head_teacher_id`, `previous_class_id`, `join_code` | `UNIQUE(school_year_id, name)`, `UNIQUE(id, school_year_id)` für den Fremdschlüssel unten, `CHECK(stage >= 1)` |
| `class_enrollments` (gibt es) | `school_class_id`, `school_year_id`, `user_id`, `enrolled_at`, `left_at` | Fremdschlüssel `(school_class_id, school_year_id)` → `school_classes(id, school_year_id)`. Teilweiser Index `UNIQUE(user_id, school_year_id) WHERE left_at IS NULL` |
| `teaching_assignments` | `school_class_id`, `subject_id`, `teacher_id`, `semester` (leer = ganzes Jahr) | `UNIQUE NULLS NOT DISTINCT(school_class_id, subject_id, teacher_id, semester)`, `CHECK(semester IN (1, 2))`. Mehrere Lehrkräfte pro Fach und Klasse erlaubt |
| `users` (gibt es) | + `deactivated_at` | `UNIQUE(lower(email))`, `UNIQUE(lower(gamer_tag))` |

Teilweise Indizes, `lower()`-Indizes und `NULLS NOT DISTINCT` (ab Postgres 15) entstehen per `DB::statement` in der Migration.

## 4. Regeln

**Sichtbarkeit (Schüler:innen):** Maßgeblich ist die **letzte aktive Einschreibung**, nicht das Kalenderdatum (sonst sind die Sommerferien leer).
Aktueller Abschnitt = `school_classes.stage`, bei `unit = semester` ab `second_term_starts_on` +1.
Sichtbar sind die Fächer aus `curriculum_entries` mit `stage_from <= aktueller Abschnitt`, davon alle veröffentlichten Themen.
Ohne aktive Einschreibung: keine Inhalte, nur der Hinweis „Melde dich bei Admin oder Lehrkraft“.

**Löschregeln nach Richtung:**

| Beziehung | `ON DELETE` | Warum |
|---|---|---|
| Daten einer Person → `users` (Einschreibung, Versuche, Statistik) | `CASCADE` | Recht auf Löschung (DSGVO) |
| Verlauf → Inhalte (`topics`, `questions`) | `RESTRICT` oder Soft Delete | CMS-Löschungen dürfen keinen Verlauf löschen |
| Schulstruktur untereinander (Klasse → Ausbildungsgang, Einschreibung → Klasse) | `RESTRICT` | Struktur wird deaktiviert, nicht gelöscht |
| `teaching_assignments.teacher_id` | `RESTRICT` | Lehrkräfte werden über `deactivated_at` gesperrt, nicht gelöscht |
| `head_teacher_id`, `previous_class_id` | `SET NULL` | Verweis ist optional |

**Registrierung (nur Schüler:innen):**
1. E-Mail eingeben. Die Domain muss in `school_email_domains` mit `allows_registration` stehen, sonst Abbruch. Daraus ergibt sich die Schule.
2. 6-stelliger Code per Mail: gehasht im Cache (Redis), 10 Minuten gültig, wenige Versuche, Rate-Limit pro E-Mail und IP.
3. Danach Gamer-Tag und Passwort festlegen. Rolle immer `student`.
4. Zuordnung zur Klasse durch Admin (später auch Lehrkraft oder `join_code`).

Lehrkräfte legt der Admin in Filament an. Über die Registrierung wird niemand Lehrkraft.
Dass `teacher_id` und `head_teacher_id` auf eine Lehrkraft zeigen, prüft die App (Validierung, Policy, Feature-Test), nicht die Datenbank.
E-Mail-Adressen enthalten oft Klarnamen: nur intern, nie in API-Antworten über Schüler:innen.

## 5. Geplante Komfort-Funktionen

- **Schuljahreswechsel:** Filament-Aktion „Klasse ins neue Schuljahr übernehmen“. Legt die Folgeklasse mit `stage + 1` und `previous_class_id` an und übernimmt die aktiven Einschreibungen. Wiederholende werden einzeln umgehängt.
- **Klassenwechsel im Jahr:** alte Einschreibung bekommt `left_at`, neue wird angelegt. Der Verlauf bleibt.

## 6. Gemerkt, bewusst später

| Fall | Lösung, wenn es soweit ist |
|---|---|
| Gruppenteilung in Labor und Werkstätte | `group_label` in `teaching_assignments` und `class_enrollments`, fürs Lehrkraft-Dashboard |
| Neuer Lehrplan für neue Jahrgänge | Lehrplan-Versionen `curricula(program_id, valid_from)`, Klasse zeigt auf die Version |
| Kolleg behandelt HTL-Stoff in anderer Reihenfolge | Freischaltung pro Thema und Ausbildungsgang (`curriculum_topics`) |
| Schul-Postfach nach dem Abgang gelöscht | Aufbewahrungsdauer der Konten festlegen, hängt an der Einwilligung |
| Microsoft-365-Login | Braucht eine App-Registrierung der Schul-IT, danach als zweiter Weg neben dem Code |
| Zweite Schule | Modell trägt das schon (Abteilungen und Schuljahre je Schule) |

## 7. Offen

1. Mail-Domain(s) der Schüler:innen und Lehrkräfte an der HTL Mödling. Nutzt die Schule Microsoft 365?
2. Offizielle Klassen- und Ausbildungsgang-Kürzel (z. B. „HIT“), Quelle: Sekretariat oder WebUntis.
3. `users.name` ist Pflicht, die Registrierung fragt aber keinen Namen ab. Nullable machen oder aus dem Gamer-Tag füllen?
4. Einwilligung bei unter 14-Jährigen (Einzelfälle im 1. Jahrgang).

## 8. Reihenfolge der Umsetzung

1. Schulstruktur: `school_email_domains`, `departments`, `programs`, `school_years`, `curriculum_entries` + Filament-Ressourcen
2. Klassen: `school_classes`, `class_enrollments`, `teaching_assignments` + Zuordnung in Filament
3. Sichtbarkeit in der API (`/api/v1/subjects` nach Einschreibung gefiltert) + Feature-Tests für die Szenarien oben
4. Registrierung mit Mail-Code
