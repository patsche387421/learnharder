# LearnFlow

Der Kern-Flow von learnharder als Storyboard in acht Momenten: Hub (Alltag und Prüfung), Thema starten, Frage, Feedback, Merke-Karte, Ergebnis und Theorie-Modus.

## Grundidee

- **Learning by doing.** Eine Lerneinheit beginnt mit Fragen, nicht mit Text. Die Erklärung kommt im Feedback und in kurzen Merke-Karten.
- **Theorie bleibt erreichbar.** Jedes Thema lässt sich auch nur lesen (Theorie-Modus), z. B. vor einer Prüfung.
- **Alltag und Prüfung sind gleich wichtig.** Ohne anstehende Prüfung zeigt der Hub das tägliche Weiterlernen. Ist eine Prüfung eingetragen, rückt sie automatisch nach oben.

## Die acht Momente

1. **Hub im Alltag:** Der Streak zeigt, ob heute schon gelernt wurde. Darunter steht *ein* großer Button „Weiterlernen“ mit Thema und Dauer, danach Herausforderung, Liga und Shop als ruhige Zeilen. Läuft ein EP-Booster, trägt „Weiterlernen“ die Marke `lh-tag--boost` („2× EP · 23 Min.“), denn genau dort lohnt er sich.
2. **Hub mit Prüfung:** Die Prüfungskarte (`lh-exam`) steht oben. Sie zeigt Fach, Datum und Tage bis zur Prüfung, wie viel Prozent man vorbereitet ist (Leiste in `accent`) und welche Themen schwach oder sicher sind. Der große Button heißt „Für den Test üben“. Weiterlernen wird zur kleinen Zeile.
3. **Thema starten:** Was man lernt, wie viele Fragen, wie lange es dauert und wie viele EP es bringt. Dazu zwei Wege: **Los geht’s** (primär) und **Nur Theorie lesen** (sekundär).
4. **Frage:** Fokus-Modus ohne Tab-Leiste. Oben stehen Schließen und die Schritt-Leiste. Erst nach dem Auswählen wird „Prüfen“ aktiv. Es gibt keinen Zeitdruck.
5. **Feedback:** Ein Sheet von unten mit richtig/falsch, Icon und **Begründung**. Falsche Antworten kosten nichts und kommen am Ende der Einheit noch einmal („Kommt am Ende noch einmal“).
6. **Merke-Karte:** Nach einem neuen Konzept oder einem Fehler kommt ein Satz und ein Beispiel (`lh-note`).
7. **Ergebnis:** EP, Statistik (richtig, Streak, geübte Fehler) und die EP-Leiste. Hat ein EP-Booster mitgezählt, steht unter den EP „davon +4 durch Booster“. Beim Level-Up springt das große Badge auf. Der primäre Button führt direkt zum **nächsten Thema**, „Zum Hub“ ist der Nebenweg.
8. **Theorie-Modus:** Das Thema als kurzer Artikel mit Lesedauer, Code und Merke-Box. Am Ende steht ein Button direkt ins Quiz.

## Regeln

- **Jeder Screen beantwortet „Was mache ich jetzt?“** Es gibt genau einen Primär-Button, unten in Daumenreichweite.
- **Fortschritt nach jeder Aktion sichtbar machen**: Schritt-Leiste, EP, Streak, Vorbereitungs-Prozent.
- **Fokus**: In Lerneinheit und Quiz gibt es keine Tab-Leiste, keine Währungen und keine Liga. Ein laufender EP-Booster zeigt sich nur als ruhige Marke „2×“ neben den EP, ohne Restzeit: Die Uhr soll keinen Zeitdruck machen.
- **Fehler sind Lernmomente**: Es gibt keine Leben und keinen roten Vollbild-Screen. Stattdessen kommt eine Erklärung, und der Fehler wird am Ende wiederholt.
- **Abbrechen ist sicher**: Schließen führt zurück, der Fortschritt im Thema bleibt erhalten.
- Animationen sind kurz (≤ 0,5 s) und entfallen bei `prefers-reduced-motion`.

## Bausteine

`TabBar`, `LevelBadge`, `XpBar`, `Button`, `AnswerOption` sowie die Screen-Bausteine in `bundle.css`: `lh-screen`, `lh-lessonbar`, `lh-steps`, `lh-hubhead`, `lh-chip`, `lh-card`, `lh-exam`, `lh-tag`, `lh-note`, `lh-feedback`, `lh-result`, `lh-stats`.

**Die App liefert:** Vorname, Streak, nächstes Thema, Prüfungen (Fach, Datum, Themen mit Sicherheit), Fragen mit Erklärungen, EP, Level und Rang.

**Prüfungen trägt die Lehrkraft ein**, für die ganze Klasse. Alle Schüler:innen der Klasse sehen den Test dann automatisch im Hub. Dafür braucht es Lehrer-Rollen und Klassen.

**Noch offen:** wie „Prozent vorbereitet“ berechnet wird.
