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

## fix/daily-challenge (eigene Session) — NÄCHSTE SESSION
Tagesquiz: Einmal-pro-Tag-Sperre und Energy-Recharge funktionieren nicht. Sperre soll
verhindern, dass das Tagesquiz mehrfach pro Tag gespielt wird; Energy-Recharge (Refill
1/Tag, Cap 5) muss greifen. Logik gehört in level.js als SSOT (kein Supabase-Call für
Energy außerhalb level.js). Vor Implementierung: Ist-Verhalten gegen daily_quiz_log
und user_stats prüfen.

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
