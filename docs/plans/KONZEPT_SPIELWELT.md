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

**Figur.** Ein Roboter (E-031). Er passt zur HTL, braucht keine Hautfarben und kein Geschlecht, und sein Display-Gesicht lässt sich leicht animieren.

**Aufbau.** Alle Roboter haben dasselbe Skelett mit festen Steckplätzen (Hals, Schultern, Hand, Hüfte). Jedes Teil sitzt an einem Steckplatz, deshalb passt jedes Teil zu jedem anderen. Die Teile sind SVG-Ebenen und färben sich über drei Lack-Variablen (`--paint-main`, `--paint-trim`, `--paint-accent`). Eine Zeichnung reicht so für jede Lackierung.

| Slot | Pflicht | Varianten in G4 | Grundausstattung |
|---|---|---|---|
| Kopf | ja | 1 je Abteilung + 2 allgemeine | Kopf der Abteilung |
| Aufsatz | nein | 4: Antenne, keiner, Kappe, Propeller | Antenne |
| Rumpf | ja | 1 je Abteilung + 1 allgemeiner | Rumpf der Abteilung |
| Arme | ja, einmal gezeichnet, gespiegelt | 1 je Abteilung + 1 allgemeine | Arme der Abteilung |
| Antrieb | ja | 3: Beine, Räder, Ketten. Schweben später als Prestige-Teil | Beine |
| Werkzeug | nein, rechte Hand | 1 je Fach, in Stufen | keins |
| Abzeichen | nein, Brust | Abteilung, später Erfolge (G3) | Abzeichen der Abteilung |
| Lackierung | ja, nur Farben | 8 Paletten | Farbe der Abteilung, Neutralgrau |

Bei vier Abteilungen sind das etwa 31 Zeichnungen plus Werkzeuge.

**Abteilungen.** Jede Abteilung hat ein kostenloses Starter-Kit, z. B. einen Bildschirm-Kopf für IT oder einen Platinen-Rumpf für Elektronik. Die Abteilung ergibt sich aus der Klasse (Klasse → Ausbildungsgang → Abteilung). Ohne Abteilung gibt es das allgemeine Kit. Teile anderer Abteilungen kann man sich später verdienen.

**Werkzeug wächst mit dem Fach.** Jedes Fach hat ein eigenes Werkzeug, das mit dem Fach-Level aufgerüstet wird: Mk I ab Fach-Level 3, Mk II ab 10, Mk III ab 25, Gold ab 100. Die Werte stehen in `config/`. Die Hand ist am Anfang leer, damit sich der Roboter in den ersten Tagen durch Lernen sichtbar verändert.

**Engine statt Slot.** Display-Gesicht (Stimmungen), Akku und die Animationen gehören keinem Slot, sie zeichnet die Engine. Eine eigene Vue-Komponente setzt die Ebenen zusammen. Animationen laufen als Zustände mit CSS und der Web Animations API: `idle` (atmen, blinzeln), `celebrate`, `wave`, `think`. Der Antrieb bestimmt die Bewegung im `idle`: Räder rollen, Beine federn. Animiert werden nur `transform` und `opacity`, ohne Filter. Roboter außerhalb des Bildes pausieren. Eine fremde Laufzeit (Lottie, Rive) oder Canvas/WebGL erst prüfen, wenn das nicht reicht. Zusätzliche Emotes sind bis G5 geparkt.

**Akku.** Der Akku zeigt die Brainpower, die sich täglich auflädt. Ist sie niedrig, zeigt ein Blitz, dass er lädt. Keine rote Warnung.

**Größen.** Ab etwa 96 px Breite (Platz im Hub) die ganze Figur, kleiner ist das Display nicht mehr lesbar, in Listen (32–40 px) nur ein Porträt aus Kopf, Aufsatz und Display, als statisches Bild ohne Animation.

**Stimmungen.** Das Display zeigt eine Stimmung. Die App wählt sie aus Fakten des Servers (letzter Lerntag, Aufstieg, neue Erfolge) und der Uhrzeit des Geräts, passend zur Begrüßung im Hub. Es gilt die oberste, die zutrifft:

| Stimmung | Auslöser | Display und Bewegung |
|---|---|---|
| Stolz | Aufstieg, Erfolg oder neue Werkzeug-Stufe, noch nicht gesehen | einmal `celebrate`, danach strahlende Augen |
| Wiedersehen | zurück nach 3 oder mehr Tagen | `wave`, große Augen. Text „Schön, dass du da bist“, nie „Wo warst du?“ |
| Müde | 22 bis 5 Uhr Gerätezeit | halb geschlossene Augen, „zZz“. Antippen weckt ihn mit Gähnen und `wave` |
| Zufrieden | heute schon gelernt | entspanntes Lächeln, ruhigeres `idle` |
| Neugierig (Standard) | heute noch nicht gelernt, erster Besuch | Blick wandert ab und zu zu „Weiterlernen“ |

Kurze Reaktionen gehen vor: `think`, während eine Quiz-Antwort geprüft wird, Sternaugen nach einem fehlerfreien Quiz, ein „?“ auf der 404-Seite. Antippen löst eine kleine Reaktion aus (blinzeln, Räder drehen, Antenne wackeln). Am Jahrestag des Kontos trägt er einen Tag lang einen Partyhut.

**Nie negativ, für andere nur Pose** (E-033). Der Roboter ist nie traurig, besorgt, kaputt oder hungrig. Hinweise wie „Streak in Gefahr“ (ab G2) stehen nur im Text, im Ton von `../design/Sprache.md`. Die Stimmung bleibt privat: Andere sehen den Roboter in einer selbst gewählten Pose, weil die Stimmung verraten würde, wann jemand lernt, schläft oder weg war.

**Mit der Klasse** (E-034). Sichtbar nur in der eigenen Klasse (E-024), nur mit Gamer-Tag. Der Akku bleibt wie die Stimmung privat. Die Grundausstattung sieht so gut aus wie verdiente Teile, damit niemand als Anfänger:in auffällt.

- **Werkhalle:** Klassenseite, auf der alle Roboter der Klasse in ihrer Pose stehen, mit Gamer-Tag und Level. Antippen zeigt eine Karte mit Roboter, Level, Erfolgen und längstem Streak, aber ohne laufenden Streak und ohne „zuletzt online“. Die Werkhalle kann den Tab „Team“ füllen, der zum Start fertig sein muss (E-035).
- **Klassenprojekt:** Die Klasse baut gemeinsam eine große Maschine in der Werkhalle (z. B. Rakete, Roboterarm, Brücke). Jede abgeschlossene Lerneinheit der Klasse bringt sie ein Stück weiter. Gezeigt wird nur die Summe der Klasse, nie, wer wie viel beigetragen hat. Ist sie fertig, bekommen alle mit mindestens einer Lerneinheit eine Klassen-Lackierung oder ein Abzeichen. Am Ende der Saison gibt es ein Klassenfoto aller Roboter vor der Maschine.
- **Posen:** feste Haltungen (Arme verschränkt, winken, Daumen hoch, Werkzeug heben, sitzen). Drei sind kostenlos, weitere sind Kosmetik. Technisch gedrehte Arme an den Steckplätzen, ohne neue Zeichnungen. Die Pose erscheint in der Werkhalle, auf der Karte und in der Rangliste: die drei Besten der Saison ganz auf einem Podest, alle anderen als Porträt.
- **Meilensteine:** Aufstieg, Erfolg oder neue Werkzeug-Stufe erscheinen in der Werkhalle nur, wenn die Person das einschaltet (Opt-in), und nur mit Tag, ohne Uhrzeit.
- **Reaktionen:** nur auf Meilensteine und nur aus einer festen, positiven Auswahl (High Five, Applaus, „Starker Bau“). Kein freier Text, keine negativen Reaktionen, eine Reaktion je Meilenstein und Person. Wer eine bekommt, sieht nur die Anzahl („5 High Fives aus deiner Klasse“), keine Namen. Empfangen lässt sich abschalten.

**Auftritte.** Im Hub, auf dem Ergebnis der Lerneinheit (`celebrate`) und ab und zu im Quiz: eine kleine Reaktion auf eine richtige Antwort, nicht bei jeder Frage und nie über dem Inhalt.

**Freischalten, dann kaufen.** Jedes Teil und jede Animation hat eine Bedingung (Level, Erfolg, Fach-Level) und einen Preis in Brainpower. Kaufen kann man erst nach dem Freischalten. Die Grundausstattung ist kostenlos. Werkzeug-Stufen gibt es direkt, sie zeigen, was man gelernt hat. Prestige-Teile kosten Trophäen statt Brainpower (Ökonomie-Modell §4).

**Kein Zufall** (E-032). Jedes Teil hat eine sichtbare Quelle: Fach-Level, Erfolg, Trophäen oder der Shop, später Minispiele und eine Lackierung je Saison fürs Mitmachen, nicht für den Platz. Eine Bauplan-Ansicht zeigt alle Teile und wie man sie bekommt. Keine Lootboxen, keine Zufalls-Drops.

**Ökonomie.** Der Shop ist eine neue Brainpower-Senke neben Booster und Challenge. Die Preise kommen vor G5 als §3.4 ins Ökonomie-Modell.

**Stil.** Ein Schulprojekt aus der HTL-Werkstatt: sichtbare Schrauben, Klebeband, Kabelbinder, Aufkleber, eine Sechskantmutter auf der Antenne. Das Display ist eine LED-Punktmatrix. Flach, gleichmäßige dunkle Kontur, ohne Verlauf, Schatten und Leuchten. Warme Farben aus dem Design-System, der Akzent ist die Farbe der Abteilung, nie Patina (nur Prestige) und nie Grün (richtig).

**Wer zeichnet.** Patsche entwirft die Teile mit KI-Hilfe, Claude überträgt sie als SVG auf das Raster der Steckplätze. Bis dahin zeichnet Claude Platzhalter, damit Engine und Shop nicht warten (E-031). Der erste KI-Entwurf (`avatar-referenz-v1.webp`, Stand 2026-10-17) ist die vorläufige Referenz und austauschbar. Das endgültige Aussehen kommt ins Design-Artefakt (E-006).

## 8. Rangliste (G6)

Offen als O-006. Empfehlung:

- **EP pro Saison (Monat) in der eigenen Klasse**, daneben Ranglisten je Fach.
- **Nicht nach Brainpower:** Brainpower lädt sich täglich auf und sinkt beim Ausgeben. Wer im Shop kauft, fiele zurück, und „verdiente Brainpower“ zählt fast nur die Tage. EP messen das Lernen, die Tagesregel (E-015) bremst Farming.
- **Transparent:** Die Seite zeigt die Formel und die eigenen Punkte.
- **Datenschutz:** nur Gamer-Tags, nur die eigene Klasse (Projektplan R1). Die Liga ist zum Start fertig (E-035).

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
- Endgültiges Aussehen der Avatar-Teile (Design-Artefakt, E-006).
- Shop-Preise (Ökonomie-Modell §3.4).
- Abstimmung des Brainpower-Konzepts mit den Lehrkräften (M5).
