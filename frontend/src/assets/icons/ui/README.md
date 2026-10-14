# UI

Einfarbige UI-Icons für Navigation und Aktionen. viewBox 24×24, 2px Strich, `stroke="currentColor"`. Sie übernehmen also die Textfarbe (`ink` oder `ink-muted`) und funktionieren automatisch in Hell und Dunkel.

**Navigation (Tab-Leiste):** `ui-learn`, `ui-team`, `ui-league`, `ui-profile`, jeweils mit einer `-active`-Variante (gefüllt). Inaktiv: Linie in `ink-muted`. Aktiv: gefüllt in `ink`, mit fettem Label. Der Hub in der Mitte hat kein UI-Icon, sondern das Level-Badge.

**Aktionen:** `ui-back`, `ui-next`, `ui-close`, `ui-correct`, `ui-wrong`, `ui-info`, `ui-locked`, `ui-search`, `ui-share`, `ui-settings`, `ui-language`, `ui-theme`.

Regeln:
- Inline einbinden (oder als Sprite), damit `currentColor` greift. Als `<img>` bleibt das Icon schwarz.
- Die Trefffläche ist immer mindestens `tap-target` (48px), auch wenn das Icon 24px groß ist.
- `ui-correct` und `ui-wrong` stehen immer zusammen mit `correct`/`wrong`-Farbe und Text.
