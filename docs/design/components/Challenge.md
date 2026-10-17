# Challenge

Die Herausforderung: 10 zufällige Fragen aus Themen, die man schon gelernt hat, für 25 Brainpower. Zeile im Hub, Start, Frage und Ergebnis.

**Hub-Zeile** unter „Weiterlernen“, vor Liga und Shop, mit `icon-challenge` 40px. Vier Zustände:
- **Bereit:** `a.lh-card.lh-card--link`, „Herausforderung“ · „10 Fragen aus deinen Themen · noch 2 heute“.
- **Offen:** dazu `lh-card--featured`, „Herausforderung“ · „Weitermachen · 4 von 10 · bis Mitternacht“. Führt direkt zur nächsten Frage.
- **Für heute geschafft:** Link zum Start, „Morgen wieder“.
- **Gesperrt:** `div.lh-card.lh-card--row`, kein Link, „Schließe zuerst ein paar Themen ab.“

**Start** (Fokus ohne Tab-Leiste, `lh-screen`): Icon 64px, Titel, ein Satz, `lh-stats` (10 Fragen · 5 EP je Frage · bis +50 Bonus), `lh-note` „So funktioniert’s“ mit den Regeln. Unten der Button „Starten · 25“, darunter in `ink-muted` „Noch 2 heute · Zeit bis Mitternacht“. Reicht die Brainpower nicht, ist der Button `disabled` und darunter steht „Noch 10 Brainpower. Morgen lädt sie sich wieder auf.“ Am Tageslimit heißt er „Morgen wieder“ und ist `disabled`.

**Frage:** wie im `LearnFlow` (Momente 4 und 5). Die Eyebrow zeigt „Frage 3 / 10“, rechts daneben `lh-tag` mit dem Fach („DBI“), weil die Fragen aus mehreren Fächern kommen. Jede Frage kommt einmal: Das Feedback hat kein „Kommt am Ende noch einmal“. Kein „2×“, ein EP-Booster wirkt hier nicht.

**Ergebnis:** `lh-result` mit „8 von 10 richtig“, `lh-result__gain` „+60 EP“, darunter in `ink-muted` „davon +20 Bonus“. `lh-stats`: richtig · Tage Streak · Trophäen. Neue Erfolge als Karten (`Achievement`). Beim Aufstieg springt das Badge auf wie im `LearnFlow`. Primärer Button „Zum Hub“.

Regeln:
- **Ehrlich vor dem Start.** Kosten, Anzahl, Bonus und „bis Mitternacht“ stehen vor dem Tipp. Kein Bestätigungsdialog, der Button sagt, was es kostet (wie im `Shop`).
- **Kein Zeitdruck.** „Bis Mitternacht“ steht nur am Start und in der Hub-Zeile, nie als Uhr in der Frage.
- **Abbrechen ist sicher.** Schließen führt zum Hub, die Herausforderung bleibt offen und lässt sich bis Mitternacht fortsetzen. Die Brainpower ist beim Start ausgegeben, das sagt der Start.
- Zahlen in Space Mono.

**Die App liefert:** Kosten, Brainpower, Starts heute übrig, ob eine offen ist (Stand), ob sie gesperrt ist und warum, Fragen mit Fach, im Ergebnis EP, Bonus, Trophäe und neue Erfolge.
