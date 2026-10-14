# Mandanten-Modell: Schule, Abteilung, Klasse, Rollen

## Status: Vision (Phase 2+) — kein Code, keine DB-Migration

Dieses Dokument hält die langfristige Vision fest, LearnHarder von einer Einzel-Lernapp zu
einem **mandantenfähigen, schulweiten Lernsystem** auszubauen. Es wird in der aktuellen
Phase **nicht** umgesetzt — die UI-Politur (siehe `docs/ROADMAP.md`) und der
Daten-Migrations-Plan (`docs/DATA_MIGRATION_V2.md`) gehen klar vor. Hier wird nur die
Richtung dokumentiert, damit spätere DB-/Auth-Entscheidungen sie nicht versperren.

## 1. Ziel & Pitch

Die Plattform soll **schulweit** einsetzbar sein: nicht nur eine Klasse oder ein Fach,
sondern die gesamte Schule mit ihren Abteilungen und Klassen. Das Ziel ist ein Stand, den
die **Direktion gegenüber Schule und Schulträger pitchen kann** — als nachhaltiges,
abteilungs- und klassenspezifisches Lernsystem, das den bestehenden Unterricht ergänzt und
über Jahre weiterläuft, statt als Einzelprojekt zu enden.

## 2. Hierarchie

Eine klare, mehrstufige Struktur:

**Schule → Abteilung → Klasse → Schüler.**

- Eine **Schule** ist der oberste Mandant (Tenant).
- Eine Schule hat mehrere **Abteilungen** (z. B. Fachrichtungen einer HTL).
- Eine Abteilung hat mehrere **Klassen**.
- Eine Klasse hat mehrere **Schüler**.
- **Lehrer** sind einer oder mehreren Abteilungen/Klassen zugeordnet und hängen damit
  ebenfalls an der Hierarchie.

## 3. Rollen & Sichtbarkeit

Drei Grundrollen mit klar abgegrenzter Sicht:

- **Schüler:** sieht nur die Inhalte seiner Klasse bzw. Abteilung und seine **eigenen**
  Statistiken (EP, Level, Fortschritt). Keine fremden Schülerdaten.
- **Lehrer:** verwaltet die Inhalte der eigenen Klassen/Abteilungen und sieht den
  **Fortschritt der eigenen Klassen** (aggregiert und je Schüler).
- **Admin / Direktion:** schulweite Übersicht über alle Abteilungen und Klassen.

## 4. Datenmodell-Implikationen (nur Skizze)

Nur eine grobe Richtung, keine fertige Migration:

- Relevante Tabellen bekommen Mandanten-/Hierarchie-Spalten, z. B. `tenant` (Schule),
  `abteilung_id`, `klasse_id`.
- **Inhalte** (Fächer, Themen, Fragen) werden an Abteilung/Klasse gekoppelt, statt global
  für alle Nutzer zu gelten.
- Die genaue Verteilung dieser Spalten über Nutzer-, Stats- und Inhalts-Tabellen ist Teil
  der späteren Detailplanung.

## 5. Sicherheit

Die Mandanten-Trennung muss **serverseitig erzwungen** werden, nicht im Client:

- **Row-Level-Security (RLS)** pro Rolle in Supabase: Ein Schüler kann per Query nur an
  Zeilen seiner Klasse/Abteilung, ein Lehrer nur an seine Klassen, Admin schulweit.
- **EP- und Schreibzugriffe laufen serverseitig über RPC**, nie direkt vom Client. Das
  knüpft an den `xp_events`-Ledger aus `docs/KONZEPT_TEAMS_SAISONS.md` an: EP-Beträge
  werden von einer Security-Definer-RPC berechnet und gebucht, der Client setzt nie selbst
  Werte. In einem Mehr-Mandanten-Kontext mit Ranglisten ist dieser Schutz noch wichtiger.

## 6. DSGVO-Hinweis

HTL-Schüler sind teils **minderjährig**. Das muss von Anfang an mitgedacht werden:

- **Einwilligung / Elternzustimmung** bei Minderjährigen einholen.
- **Datenminimierung**: nur erheben/speichern, was fürs Lernsystem wirklich nötig ist.

Diese Punkte früh einplanen, nicht nachträglich aufsetzen.

## 7. Abgrenzung

Das Mandanten-Modell ist ein **großer DB- und Auth-Umbau**. Es kommt bewusst:

- **nach** der UI-Politur (`docs/ROADMAP.md`),
- **nach** dem Daten-Migrations-Plan (`docs/DATA_MIGRATION_V2.md`).

Jetzt wird es **nur als Vision dokumentiert** — keine Umsetzung, keine Migration in dieser
oder den nächsten Phasen.

## 8. Offene Fragen

- **Klassen-Zuordnung beim Onboarding:** Wie landet ein Schüler in der richtigen Klasse
  (Einladungscode, Lehrer-Zuweisung, Selbstauswahl mit Bestätigung)?
- **Lehrer-Verifizierung:** Wie wird sichergestellt, dass eine Lehrer-Rolle echt ist?
- **Content-Ownership:** Wem gehören erstellte Inhalte (Lehrer, Abteilung, Schule)?
- **DSGVO bei Minderjährigen:** Konkreter Einwilligungs-/Zustimmungs-Flow.
