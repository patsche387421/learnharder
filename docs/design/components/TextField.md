# TextField

Ein beschriftetes Eingabefeld für Login, Profil und Einstellungen, mindestens 48px hoch und mit 16px Schrift.

**Aufbau:** `label.lh-field` > `.lh-field__label` · `input.lh-field__input` · optional `.lh-field__hint`.

- Das Label steht **immer sichtbar über dem Feld**. Ein Platzhalter ersetzt kein Label, er zeigt nur ein Beispiel.
- Die Schrift ist 16px groß, damit iOS beim Antippen nicht hineinzoomt. Passenden `type` und `autocomplete` setzen (E-Mail, Passwort).
- Fokus: 2,5px-Rahmen in `ink`.
- Fehler: `.lh-field--error` plus `aria-invalid="true"`. Der Hinweis mit `ui-wrong`-Icon sagt, **wie es richtig geht** („Da fehlt noch etwas, z. B. lena@htl.at“), nicht nur „Ungültig“. Fehler erst beim Verlassen des Felds oder beim Absenden zeigen, nicht während des Tippens.
- Deaktiviert (`disabled`): Fläche `surface-sunken`, und ein Hinweis sagt, warum das Feld gesperrt ist.

**Die App liefert:** Wert, Fehlertext, ob das Feld deaktiviert ist.
