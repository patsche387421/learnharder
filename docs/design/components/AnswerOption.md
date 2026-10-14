# AnswerOption

Eine Antwortmöglichkeit in einer Quizfrage, mit den Zuständen ausgewählt, richtig und falsch.

**Aufbau:** `button.lh-answer` > `.lh-answer__key` (A, B, C …) · `.lh-answer__text`.

- Ausgewählt: `aria-pressed="true"`, der Rahmen wird `ink`. Erst danach wird der Prüfen-Button aktiv.
- Nach dem Prüfen: Die richtige Antwort bekommt `.lh-answer--correct`, die falsch gewählte `.lh-answer--wrong`. Beide bekommen hinten das Wort als `.lh-answer__result` und das Icon (`.lh-icon`). Alle Optionen sind dann `disabled`.
- Richtig/falsch wird **nie nur über die Farbe** gezeigt: Es gibt immer Icon (`ui-correct` / `ui-wrong`) und Wort („Richtig“ / „Falsch“).
- Die Antworten stehen am Handy untereinander, nie in zwei Spalten. Der Text darf umbrechen.
- Die Frage darüber verwendet den Text-Stil `question` (18px). Code steht als `<code>` darin und bekommt automatisch den Text-Stil `code` (Space Mono) auf `surface-sunken`, ebenso in `.lh-feedback__text` und `.lh-note`.

**Die App liefert:** Antworttexte, welche Antwort richtig ist und das Ergebnis.
