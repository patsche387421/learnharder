# Checkbox

Ein Häkchen für eine Zustimmung oder Bestätigung, z. B. „Ich bin mindestens 14 Jahre alt“ bei der Registrierung.

**Aufbau:** `div.lh-check` > `label.lh-check__row` > `input.lh-check__input[type=checkbox]` · `.lh-check__label`, optional `.lh-check__hint` unter der Zeile.

- Die ganze Zeile ist antippbar und mindestens 48px hoch, das Kästchen selbst 24px.
- Der Text steht rechts vom Kästchen und sagt in einem Satz, was man bestätigt. Links im Text (`a`) sind unterstrichen, z. B. zur Datenschutzerklärung.
- Angehakt: Fläche `ink`, Haken `ink-inverse`. Leer: Rahmen `line-strong` (Kontrast ab 3:1 in beiden Themes).
- Fokus: 2px-Rahmen in `ink` um das Kästchen.
- Fehler: `.lh-check--error` plus `aria-invalid="true"`. Der Hinweis mit `ui-wrong`-Icon sagt, warum das Häkchen nötig ist („Bestätige, dass du mindestens 14 bist.“). Erst beim Absenden zeigen.
- Nie vorab angehakt, wenn es um eine Einwilligung geht.

**Die App liefert:** ob angehakt, den Text, einen Fehlertext.
