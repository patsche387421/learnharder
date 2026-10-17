# Betriebshandbuch für Admins (Stand 2026-10-17)

So verwaltest du Konten, Klassen und die Schulstruktur im CMS von learnharder.
Inhalte pflegen und veröffentlichen: `redaktion.md`. Server, Backups und Deploy: `infra/` (kommt mit M4).

Alles hier geht nur mit der Rolle **Admin**. Die Bereiche stehen im CMS unter „Schule“, die Erfolge unter „Spiel“.

## Rollen

| Rolle | CMS | App |
|---|---|---|
| Admin | alles | sieht alle veröffentlichten Fächer |
| Autor:in | Inhalte anlegen und bearbeiten | sieht alle veröffentlichten Fächer |
| Tester:in | Inhalte nur ansehen | sieht alle veröffentlichten Fächer |
| Lehrkraft | kein Zugang | sieht alle veröffentlichten Fächer |
| Schüler:in | kein Zugang | sieht nur den Lehrplan der eigenen Klasse (E-024) |

Eine Person kann mehrere Rollen haben. Schüler:innen haben keinen Namen im System, nur E-Mail und Gamer-Tag (E-025).

## Wie Schüler:innen ankommen

**Ab 14** registrieren sie sich selbst in der App:

1. Sie geben ihre Schuladresse ein, z. B. `20251234@htl-md.schule`. Erlaubt sind nur Mail-Domains mit „Registrierung erlaubt“ (siehe „Schule einrichten“).
2. Sie bekommen einen Code per Mail, wählen Gamer-Tag und Passwort und bestätigen „mindestens 14“ und die Rechtstexte.
3. **Du trägst sie in ihre Klasse ein.** Vorher sehen sie in der App keine Fächer, nur den Hinweis, sich bei Admin oder Lehrkraft zu melden.
   In der Kontenliste findest du sie mit dem Filter „Ohne Klasse“.

**Unter 14** gibt es keine Selbstregistrierung:

1. Die Eltern füllen das Elternformular aus. Die Schule bewahrt es auf, nicht die App.
2. Du legst das Konto an: „Konten“ → „Erstellen“ → „Schüler:in unter 14“, mit E-Mail und Gamer-Tag. Du bestätigst, dass das Formular vorliegt.
3. Die Person setzt ihr Passwort in der App über „Passwort vergessen“. Danach trägst du sie in die Klasse ein.

## Personal anlegen

„Konten“ → „Erstellen“ → „Personal“. Name, E-Mail und Rollen eintragen.

Ein Passwort vergibst du nie. Die Person setzt es selbst in der App über „Passwort vergessen“ und meldet sich damit auch im CMS an.
Ob jemand Lehrkraft ist, sieht man an der Adresse nicht, deshalb vergibt nur der Admin diese Rolle.

## Konten verwalten

In der Kontenliste hat jede Zeile „Bearbeiten“ und „Sperren“ bzw. „Entsperren“. Seltene Aktionen liegen im Menü ⋮.

| Aktion | Was passiert |
|---|---|
| Bearbeiten | Name, E-Mail, Gamer-Tag und Rollen ändern |
| Sperren | Mit Grund (intern, sachlich). Wirkt sofort in App und CMS, die Person ist überall abgemeldet. Sie kann den Grund mit einer Auskunft nach DSGVO einsehen |
| Entsperren | Nur ein Admin kann entsperren |
| Gamer-Tag zurücksetzen (⋮) | Wird zu „Spieler-“ mit einer Zahl. Für Namen, die beleidigen oder verraten, wer dahintersteckt |
| Löschen (⋮) | Nur Schüler:innen. Konto und Lernverlauf sind sofort weg, ohne Rückweg. Personal wird gesperrt, nicht gelöscht |

Schüler:innen können ihr Konto auch selbst in der App löschen, nach Eingabe ihres Passworts (E-027).

**Schutzregeln** (E-027). Das CMS meldet dann „Nicht gespeichert“ mit dem Grund:

- Du kannst dich nicht selbst sperren, löschen oder dir die Admin-Rolle nehmen.
- Nur aktive Admins dürfen Konten verwalten.
- Zum Start gibt es mindestens zwei Admins, einer davon von der Schule.

## Löschfristen

Wer sich 12 Monate nicht anmeldet, wird gelöscht. 30 Tage vorher kommt eine Hinweis-Mail, ein Login stoppt das Löschen. Personal ist ausgenommen (E-028).
**Noch nicht aktiv:** Hinweis-Mail und Löschlauf kommen vor dem Start. Die Spalte „Letzter Login“ zählt schon mit.

## Schule einrichten

Die Reihenfolge ist wichtig, weil jede Stufe die vorige braucht:

```
Schule ── Mail-Domains, Abteilungen, Schuljahre
  └── Ausbildungsgang (in einer Abteilung) ── Lehrplan: welche Fächer ab welchem Abschnitt
        └── Klasse (in einem Schuljahr) ── Schüler:innen, Lehrkräfte
```

1. **Schule:** Name, Ort, Land. Darin drei Bereiche:
   - **Mail-Domains**, ohne @. „Registrierung erlaubt“ nur bei Domains von Schüler:innen.
   - **Abteilungen** mit Kürzel und Farb-Token.
   - **Schuljahre** mit Beginn, Beginn des 2. Semesters und Ende.
2. **Ausbildungsgang:** Abteilung, Kürzel, Name, Ausbildungsform, ob er in Jahrgängen oder Semestern zählt, und Dauer.
   Darin der **Lehrplan**: Fach, „Ab Abschnitt“ und „Bis Abschnitt“.
3. **Klasse:** Ausbildungsgang, Schuljahr, Name, Abschnitt zu Beginn des Schuljahres, Klassenvorstand.
   Darin **Schüler:innen** (eintragen, austragen) und **Lehrkräfte** (Fach, Lehrkraft, Semester).

### Was Schüler:innen sehen

Ein Fach erscheint, wenn es im Lehrplan des Ausbildungsgangs steht und „Ab Abschnitt“ höchstens beim aktuellen Abschnitt der Klasse liegt.

- Der aktuelle Abschnitt ist der Abschnitt der Klasse. Zählt der Ausbildungsgang in Semestern, kommt ab „Beginn 2. Semester“ eins dazu.
- „Bis Abschnitt“ beschreibt den Lehrplan. Die App blendet Fächer danach nicht aus, wiederholen geht immer.
- Zusätzlich gelten die Regeln für Inhalte: Fach aktiv, Thema veröffentlicht (siehe `redaktion.md`).

### Klassen und Schuljahre

- Pro Schuljahr ist eine Person in höchstens einer Klasse aktiv. Wechselt jemand die Klasse, zuerst in der alten **austragen**, dann in der neuen eintragen.
- Austragen löscht nichts, die Einschreibung bleibt mit Datum erhalten.
- Für ein neues Schuljahr legst du neue Klassen an und trägst die Schüler:innen dort ein.
- Die Zuordnung der Lehrkräfte nutzt die App noch nicht. Sie wird mit dem Lehrkraft-Dashboard nach dem Start wichtig.

## Erfolge beim Einrichten

Den ersten Katalog mit 12 Erfolgen legt `php artisan db:seed` an, auch in Produktion (dort mit `--force`, `AchievementSeeder`). Er legt nur fehlende an und überschreibt nichts, was du im CMS geändert hast. Danach pflegst du die Erfolge unter „Spiel“ → „Erfolge“ (siehe `redaktion.md`).

## Notfall: kein Admin mehr

Sind alle Admins gesperrt oder weg, hilft ein Befehl auf dem Server:

```bash
php artisan learnharder:make-admin person@example.com
```

Er macht ein bestehendes Konto zum aktiven Admin und entsperrt es, falls nötig. Für ein neues Konto braucht er zusätzlich `--name="Vorname Nachname"`.
Das Passwort setzt die Person danach in der App über „Passwort vergessen“. Lokal läuft der Befehl über Sail: `./vendor/bin/sail artisan learnharder:make-admin …` im Ordner `backend/`.

## Meldungen im CMS

| Meldung | Was sie heißt |
|---|---|
| „Konto angelegt“ | Die Person setzt ihr Passwort über „Passwort vergessen“ |
| „Gesperrt. Die Person ist überall abgemeldet.“ | Sperre wirkt sofort |
| „Nicht gespeichert“ | Eine Schutzregel hat abgelehnt, der Grund steht darunter |
| „Für diese E-Mail gibt es schon ein Konto.“ | Groß- und Kleinschreibung zählt nicht |
| „Diesen Gamer-Tag gibt es schon.“ | Auch hier zählt Groß- und Kleinschreibung nicht |
| „Ist in diesem Schuljahr schon in einer Klasse. Dort zuerst austragen.“ | Siehe „Klassen und Schuljahre“ |
| „Eintragen kann man nur Schüler:innen.“ | Die Person hat keine Rolle Schüler:in |
