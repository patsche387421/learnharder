# Shop

Hier setzt man Brainpower ein: eine Seite mit Abschnitten, zuerst „EP-Booster“, später Roboter-Teile und Animationen.

**Weg hinein:** die ruhige Zeile „Shop“ im Hub (`lh-card--link` mit `icon-booster`) und als Abkürzung der Brainpower-Chip (`lh-chip--link`). Kein eigener Tab, die Tab-Leiste hat fünf Ziele.

**Aufbau:** `div.lh-shop` > `div.lh-shop__head` (Titel und Brainpower-Chip) · je Abschnitt `section.lh-shop__section` mit `h2.lh-shop__title`, `p.lh-shop__intro` und Angeboten `div.lh-offer` (Icon 40px · `lh-offer__body` mit Name und Dauer · `lh-offer__buy` mit Kosten im Button).

Regeln:
- **Vorteil und Kosmetik getrennt.** EP-Booster bringen EP und damit Liga-Punkte, Roboter-Teile sind nur Kosmetik. Sie stehen nie im selben Abschnitt.
- **Ehrlich vor dem Kauf.** Der Abschnitt EP-Booster sagt, dass der Faktor nur für Fragen gilt, die heute noch EP bringen. Bringt heute kein Thema mehr EP, steht das über den Angeboten: „Heute bringen deine Themen keine EP mehr. Morgen lohnt sich ein Booster wieder.“ Kaufen bleibt möglich.
- **Kein Bestätigungsdialog.** Der Button sagt, was passiert und was es kostet („Starten · 40“). Ein Tipp startet sofort, die App wartet auf den Server und zeigt dann die neue Brainpower.
- **Reicht die Brainpower nicht,** ist der Button `disabled` und darunter steht, wie viel fehlt (`lh-offer__why`: „Noch 30 Brainpower“). Keine graue Zeile ohne Grund.
- **Läuft ein Booster,** steht über den Angeboten `lh-shop__running` („2× EP läuft · noch 23 Min.“) und alle Buttons sind `disabled` mit „Läuft schon einer“. Höchstens einer gleichzeitig.
- **Ruhig.** Kein Countdown in Sekunden, kein Leuchten, keine Rabatte, keine Zeitfenster. Beim Kauf jubelt der Roboter einmal (`celebrate`, unter 1 s, entfällt bei `prefers-reduced-motion`).

**Laufender Booster außerhalb des Shops:**
- Hub: Marke `span.lh-tag.lh-tag--boost` auf „Weiterlernen“ („2× EP · 23 Min.“, in Minuten) und `lh-chip__boost` am Brainpower-Chip („2×“).
- Lerneinheit: nur „2×“ neben den EP, ohne Restzeit (LearnFlow, Fokus).
- Ist er abgelaufen, verschwinden die Marken ohne Hinweis.

**Die App liefert:** Brainpower (Stand, Maximum), Angebote mit Faktor, Dauer und Kosten, den laufenden Booster mit Ende, ob heute noch Fragen EP bringen.
