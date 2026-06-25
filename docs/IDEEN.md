# Ideen & Backlog — LearnHarder

Notizen für spätere, eigene Sessions. Kein aktiver Arbeitsstand — Bugs stehen in
`docs/BUGS.md`, ausgearbeitete Pläne in den einzelnen `docs/KONZEPT_*.md`.

---

## Doku-Index in CLAUDE.md ergänzen (eigene Session, kein Code)
CLAUDE.md bleibt schlank und enthält nur Regeln (wie bisher). Neu: ein Abschnitt
"Dokumenten-Index" — Tabelle aller `docs/*.md`-Dateien mit Zweck (ein Satz) und
Status (aktuell / veraltet-ignorieren / nur Konzept). Ziel: jede neue Session sieht
sofort, welche Detail-Datei relevant ist, ohne dass CLAUDE.md selbst die Inhalte aller
Dokumente trägt. Trennung von Regeln (CLAUDE.md) und Stand/Plänen (einzelne
`docs/*.md`) bleibt bestehen — nur die Übersicht wird zentralisiert.

## fix/daily-challenge (eigene Session) — ✅ ERLEDIGT
Tagesquiz: Einmal-pro-Tag-Sperre und Energy-Recharge funktionierten nicht.
- **Recharge:** `rechargeEnergie()` in level.js (SSOT, via getUserStats) — +1/Tag
  pro UTC-Kalendertag, Cap 5, reduziert nie. Reset-Modus = Kalendertag UTC.
- **Sperre:** `starteTagesQuiz()` legt die daily_quiz_log-Zeile schon beim Start an
  (Abbruch-Lücke geschlossen); `vergibBelohnungen({ logId })` trägt das Ergebnis nach.
- Offen geblieben: Trophäen-Tausch ohne Energie-Cap → BUG-011 (gehört zu fix/trophy-shop).

## fix/trophy-shop (eigene Session)
Trophy-Shop wieder erreichbar machen (Energydrink-Kauf: 50 Trophäen = 1 Drink). Scope
auf Erreichbarkeit + Kauf-Flow begrenzt. Einlöse-Logik bleibt in level.js (SSOT).

## Public-Page-Topbar (eigene Session)
Topbar-Darstellung für ausgeloggte Nutzer auf impressum.html / datenschutz.html.
Hinweis: Der Hamburger ist auf diesen Seiten bereits funktional (fix/topbar-behavior,
beide renderTopbar-Zweige verdrahtet) — diese Session betrifft die übrige
Topbar-Darstellung im ausgeloggten Zustand, nicht den Hamburger.

## nav.md-Doku aufräumen (klein, docs-only)
In docs/design/components/nav.md sind die TODOs Z. 22-24 (zentrale JS-Injektion via
renderTopbar + URL-basierter Active-State via aktiverNav) durch fix/topbar-behavior
faktisch erledigt → als erledigt markieren oder entfernen. Reiner docs-Commit, kein Code.

## Nav-Breite 'Herausforderung' prüfen
Nach UI-Rename ist das Nav-Label 'Herausforderung' (15 Z.) deutlich länger als
vorher 'Tagesquiz' (9 Z.). Beim nächsten echten Deploy (config.js + Login) auf
Desktop-Breite prüfen, ob im Topbar-Nav neben Dashboard/Fächer/Team/Rangliste
nichts umbricht. Falls doch: separater CSS-/Wording-Folge-Fix (Nav-Spacing oder
Label-Kürzung), eigener Commit.

Status: offen, kein Blocker.
