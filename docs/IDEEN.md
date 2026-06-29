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

## Tagessperre entfernen (eigene Session, Code)
`#screen-gespielt` aus dem Block-Pfad in tagesquiz.html lösen; Herausforderung nur
noch über Energie begrenzen (BUG-012). `hatHeuteTagesQuizGespielt()` bleibt, wird
aber nur noch für die Streak-Berechnung genutzt.
Status: offen.

## Streak implementieren (eigene Session, Code)
`berechneStreak()` in level.js als reine Funktion (rückwärts aus
`daily_quiz_log.played_at`, Definition siehe LEVEL_SYSTEM §12). Anzeige in
dashboard.html `#stat-streak`. Keine Migration. Abhängig von / sinnvoll nach
"Tagessperre entfernen".
Status: offen.

## Dashboard-Anzeigefehler fixen (eigene Session, Code)
"+5 morgen" dynamisch ableiten oder entfernen (BUG-013); Begrüßungs-Copy +
Anzeigename überarbeiten (BUG-014).
Status: offen.

## Account-Löschung (eigene Session, DSGVO)
User-Auth-Delete + alle zugehörigen Daten-Rows (`user_stats`, `quiz_results`,
`daily_quiz_log`, `subject_xp`/`fach_stats`, `thema_progress`) plus Bestätigungs-Flow
im Profil. Setzt DSGVO Art. 17 (Recht auf Löschung) um. Sauber planen, kein Quick-Fix —
betrifft mehrere Tabellen und den Auth-Layer.
Status: offen.

## Level-Rang-Badge pro Band (Teil S3)
Das Topbar-Level-Badge (Zahl) existiert bereits; zusätzlich ein Rang-Symbol pro
Level-Band einführen. Reines client-seitiges Mapping aus
`Level.berechneFortschritt().level` (1–10) auf ein Symbol — keine DB-Logik nötig.
Status: offen.

## Button-System zentralisieren (Teil S2)
Es gibt keine zentrale `.btn`-Basis (stattdessen verstreute `.btn-*`-Klassen) und keine
Button-Token. S2 baut eine `.btn`-Basis + Modifier sowie `--btn-radius`/`--btn-border`/
`--btn-shadow` in `tokens.css`.
Status: offen.

## Icon-Restarbeiten (Teil S3)
Quiz-Punkte und Herausforderung nutzen beide das `target`-Icon → differenzieren.
Verbliebene UI-Emojis: ☀️/🌙 Theme-Toggle (sun/moon neu im Icon-Modul bauen) sowie
🎉/😊/💪 im Tagesquiz-Ergebnis (Entscheid noch offen, ob ersetzen oder behalten).
Status: offen.
