# Testlauf 2026-07-09

**Branch/Deploy:** main / Netlify Live
**Getestet:** T-001, T-002, T-003, T-004, T-005

| ID | Status | Befund |
|----|--------|--------|
| T-001 | ✅ | Ergebniszeile korrekt: EP = Trophäen = richtige Antworten. Energie unverändert. |
| T-002 | ✅ | Energie nach Start exakt −1. |
| T-003 | ✅ | „Nochmal" startet sofort, Energie −1, kein „schon gespielt"-Block. |
| T-004 | ⚠️ | Seiten-Load mit Energie 0 → `#screen-gesperrt` erscheint korrekt. Auf dem Ergebnis-Screen nach dem letzten Versuch ist der „Nochmal"-Button per `disabled` inaktiv (Klick ohne Wirkung) und der Hinweis „Keine Energie mehr — morgen wieder." wird eingeblendet — der Button behält aber das volle `btn--primary`-Styling (voll lila) und sieht klickbar aus. Fehlender sichtbarer Deaktiviert-Zustand → BUG-015. |
| T-005 | ✅ | Hinweistext „Deine Energie ist bereits voll (5 / 5) — kein Tausch nötig." korrekt angezeigt. Button deaktiviert. |

## Screenshots
- `screenshots/T-001_themen-quiz-ergebnis.png`
- `screenshots/T-002_energie-abzug-vorher.png`
- `screenshots/T-002_energie-abzug-nachher.png`
- `screenshots/T-003_nochmal-start.png`
- `screenshots/T-004_sperr-screen.png` (zeigt den Ergebnis-Screen bei Energie 0 mit inaktivem „Nochmal"-Button + Hinweis)
- `screenshots/T-005_energie-voll-hinweis.png`

## Offene Punkte
- T-004 ⚠️: Deaktivierter „Nochmal"-Button zeigt keinen sichtbaren Deaktiviert-Zustand — der Hinweis „Keine Energie mehr — morgen wieder." erscheint und der Button ist funktional inaktiv, aber es fehlt eine `.btn:disabled`-CSS-Regel (Button bleibt voll-lila). Als BUG-015 in docs/BUGS.md aufgenommen.
