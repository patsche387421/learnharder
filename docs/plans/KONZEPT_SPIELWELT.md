# Spielwelt — Konzept (Stand 2026-10-17)

> **Status:** Entwurf, Was und Warum. Reihenfolge: Tracks G1–G6 und L1 in `README.md`, Rahmen: E-030.
> Formeln für EP, Level und Brainpower stehen in `GAMIFICATION_OEKONOMIE_MODELL.md`, Begriffe und Ton in `../design/Sprache.md`.

## 1. Ziel

learnharder soll sich lebendig anfühlen, ohne dass Lernen zur Jagd nach Belohnungen wird.
Alleinstellungsmerkmale: ein Hub, der auf die Person reagiert, ein eigener Avatar, sichtbare Erfolge und eine faire Rangliste.

## 2. Leitsätze

1. **Kosmetik, kein Vorteil.** Avatar-Teile, Animationen und Titel bringen keine EP und keinen Platz in der Rangliste.
2. **Lernen bleibt frei** (Ökonomie-Modell §1). Kein Inhalt liegt hinter Brainpower oder dem Shop.
3. **Server-Autorität.** Freischalten, Kaufen und Erfolge vergibt der Server, die App zeigt nur an.
4. **Datenschutz.** Avatar statt Foto. Andere sehen nur Gamer-Tag, Avatar und Erfolge. Keine externen Bibliotheken, Fonts oder Tracker ohne Freigabe.
5. **Ruhige Oberfläche.** „Gamification steckt in den Funktionen, nicht im Styling“ (`../design/README.md`). Avatar und Effekte haben feste Plätze, jeder Effekt dauert unter einer Sekunde und blockiert nichts. `prefers-reduced-motion` schaltet Bewegung ab.

## 3. Was es schon gibt

- EP, Level, Rang, Prestige und Fach-Level rechnet der Server (`User::progression`), die App bekommt sie über `GET /api/v1/me`.
- Spalten ohne Logik: `users.brainpower_current` und `brainpower_max` (schon in `/me`), `user_stats.trophies`, `current_streak`, `longest_streak`, `last_study_date`.
- Der Hub (`/`) ist ein Platzhalter (`PlaceholderView`). Das Profil zeigt Gamer-Tag und Klasse.

## 4. Hub und Profil (G1)

Der Hub ist die Startseite und ändert sich mit dem, was die Person tut.

- **Immer:** Begrüßung mit Gamer-Tag, Level-Leiste, Brainpower, „Weiterlernen“ mit dem nächsten offenen Thema (E-022).
- **Zustände:** erster Besuch, heute schon gelernt, zurück nach einer Pause, gerade aufgestiegen, alles erledigt (dann Wiederholen vorschlagen). Ab G2 auch Streak läuft oder ist in Gefahr, im Ton von `Sprache.md` („5 Min. reichen“), nie drohend.
- **Tageszeit:** morgens, nachmittags, spät abends anders begrüßen.
- **Später:** Avatar im Hub (G4), zuletzt erreichter Erfolg (G3).
- **Profil:** EP gesamt, Level, Rang, Fach-Level je Fach, längster Streak. Später Erfolge und Avatar.

Der Server liefert Fakten (letzter Lerntag, nächstes Thema, letzter Aufstieg), die App wählt Text und Bild.

## 5. Brainpower und Streaks (G2)

Formeln: Ökonomie-Modell §3. Das Brainpower-Konzept wird vor dem Start mit den Lehrkräften abgestimmt (M5). Die Werte gehören deshalb an eine Stelle in `config/`, damit eine Änderung keinen Umbau braucht.

## 6. Erfolge (G3)

Erfolge sind benannte Abzeichen („Erste Lerneinheit“, „7 Tage in Folge“, „Fach-Level 10“). Sie sind etwas anderes als **Trophäen**: Trophäen sind laut Glossar eine Belohnung, die man gegen Brainpower tauschen kann (Ökonomie-Modell §4).

- Katalog in der Datenbank: Schlüssel, Titel, Beschreibung, Bild, Bedingung, geheim ja/nein. Pflege im CMS.
- Der Server prüft die Bedingungen nach Ereignissen (Lerneinheit fertig, Aufstieg, Streak), vergibt jeden Erfolg einmal und merkt sich das Datum.
- Profil: Raster, nicht erreichte als Silhouette, geheime als „?“.
- Erfolge schalten Avatar-Teile frei (§7).
- Das Wort „Erfolge“ fehlt noch im Glossar. Es kommt zuerst ins Design-Artefakt (E-006).

## 7. Avatar (G4) und Shop (G5)

**Aufbau.** Eine Figur aus Teilen, als SVG-Ebenen übereinander: Körper und Farbe, Augen, Mund, Kopfbedeckung, Oberteil, Accessoire. Jedes Teil hat feste Ankerpunkte, damit alle Teile zusammenpassen.

**Engine.** Eine eigene Vue-Komponente setzt die Ebenen zusammen. Animationen laufen als Zustände mit CSS und der Web Animations API: `idle` (atmen, blinzeln), `celebrate`, `wave`, `think`. Eine fremde Laufzeit (Lottie, Rive) erst prüfen, wenn das nicht reicht.

**Auftritte.** Im Hub, auf dem Ergebnis der Lerneinheit (`celebrate`) und ab und zu im Quiz: eine kleine Reaktion auf eine richtige Antwort, nicht bei jeder Frage und nie über dem Inhalt.

**Freischalten, dann kaufen.** Jedes Teil und jede Animation hat eine Bedingung (Level, Erfolg, Fach-Level) und einen Preis in Brainpower. Kaufen kann man erst nach dem Freischalten. Eine Grundausstattung ist kostenlos.

**Ökonomie.** Der Shop ist eine neue Brainpower-Senke neben Booster und Challenge. Die Preise kommen vor G5 als §3.4 ins Ökonomie-Modell.

**Stil.** Wie die Teile aussehen, entscheidet das Design-Artefakt (E-006), bevor das erste Teil gezeichnet wird.

## 8. Rangliste (G6)

Offen als O-006. Empfehlung:

- **EP pro Saison (Monat) in der eigenen Klasse**, daneben Ranglisten je Fach.
- **Nicht nach Brainpower:** Brainpower lädt sich täglich auf und sinkt beim Ausgeben. Wer im Shop kauft, fiele zurück, und „verdiente Brainpower“ zählt fast nur die Tage. EP messen das Lernen, die Tagesregel (E-015) bremst Farming.
- **Transparent:** Die Seite zeigt die Formel und die eigenen Punkte.
- **Datenschutz:** nur Gamer-Tags, nur die eigene Klasse (Projektplan R1). Bis dahin bleibt die Liga ausgeblendet (E-022).

## 9. Wow-Momente

Kleine Details, die man nicht sofort bemerkt:

- Beim Öffnen winkt der Avatar kurz, die Begrüßung kennt Tageszeit und letzte Aktivität.
- Beim Aufstieg zählt die Level-Zahl hoch.
- Die Streak-Anzeige wächst mit den Tagen.
- Der Avatar blinzelt und schläft spät nachts.
- Kleine Überraschungen, z. B. auf der 404-Seite oder am Jahrestag des Kontos.

Regeln aus §2.5 gelten. Töne nur, wenn die Person sie einschaltet.

## 10. Landing Page (L1)

- **Für wen:** Jugendliche, die sich eine HTL überlegen, Schüler:innen und Kolleg-Studierende der HTL, Lehrkräfte.
- **Was:** was learnharder ist, wie sich die HTL anfühlt (Fächer, Beispielfragen), eine Demo-Lerneinheit ohne Login, für Lehrkräfte: wie Inhalte in die App kommen.
- **Wie:** statisch, ersetzt die Wartungsseite in `public/` auf Netlify. Die Demo-Fragen liegen als Datei bei, ohne API und ohne gespeicherte Daten. Dafür braucht es kein Hetzner. Die Technik (statisches HTML, Vite oder Astro wie im Projektplan) wird beim Start von L1 entschieden.

## 11. Geparkt

- **Forschungsbaum:** Ohne Spiel, in dem man ihn nutzt, hat er keinen Zweck. Erst nach G5 wieder anschauen.
- **Minispiel mit dem Avatar im Browser:** Projektplan Phase 3, Risiko R4.
- **Native App:** Zuerst die PWA. Später vielleicht eine Hülle um dieselbe Vue-App. Nur ein Wunsch.

## 12. Offen

- O-006: Grundlage der Rangliste.
- Stil der Avatar-Teile und wer sie zeichnet.
- Shop-Preise (Ökonomie-Modell §3.4).
- Abstimmung des Brainpower-Konzepts mit den Lehrkräften (M5).
