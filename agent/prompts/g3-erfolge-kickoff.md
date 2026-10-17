# Kickoff: G3 Erfolge und Herausforderung

Startprompt für eine neue Session. Alles zwischen den Linien in die neue Session kopieren.

---

Wir starten G3 (Erfolge und Herausforderung). Heute nur planen, noch nichts ändern.

**Lies zuerst:**
- `CLAUDE.md`, `agent/CLAUDE.md`, `backend/CLAUDE.md`, `backend/database/CLAUDE.md`, `frontend/CLAUDE.md`
- `docs/plans/README.md` (Meilenstein G3), `docs/AUFGABEN.md` (Abschnitt G3)
- `docs/plans/KONZEPT_SPIELWELT.md` §6 (Erfolge), §7 (Erfolge schalten Avatar-Teile frei), §4 (Profil)
- `docs/plans/GAMIFICATION_OEKONOMIE_MODELL.md` §3.3 (Herausforderung, 25 BP) und §4 (Trophäen)
- `docs/ENTSCHEIDUNGEN.md` E-006, E-015, E-030, E-032, E-036 bis E-039
- `docs/design/Sprache.md` (Glossar) und `docs/handbuch/testen.md`

**Was schon da ist:**
- Tabellen `trophies` (Katalog: `slug`, `title`, `description`, `icon_name`, `bp_reward`) und `user_trophies` (`unlocked_at`, eindeutig je Person). Kein Model, keine Logik.
- `user_stats.trophies` ist die Trophäen-Währung. Der Name stößt sich mit der Tabelle `trophies` (bekannte Schema-Lücke 1 in `backend/database/CLAUDE.md`).
- Icons im Design-Artefakt: `icon-achievement`, `icon-challenge`, `icon-trophy`. Das LearnFlow-Storyboard zeigt im Hub eine Zeile „Herausforderung“.
- Ereignisse, an denen der Server Erfolge prüfen kann: Lerneinheit abgeschlossen (`QuizAttempt::finish`), Aufstieg, Streak (`UserStat::recordStudyDay`), Fach-Level.
- Testpersonen und Playwright (`frontend/e2e/`) für das Prüfen von Hand und im Browser.

**Widersprüche, die zuerst geklärt werden müssen:**
- Das Glossar nennt **„Errungenschaft“** (nicht verwenden: Achievement, Badge), Konzept, Meilenstein und Aufgaben sagen **„Erfolge“**.
- Die Tabelle `trophies` ist eigentlich der Katalog der Erfolge. Umbenennen (neue Migration) oder so lassen? Bisher lief noch keine Migration auf einem Server.

**Gib mir einen Plan mit:**
1. den Entscheidungen, die vor dem Bauen fallen müssen, je mit Empfehlung. Mindestens: Begriff, Tabellenname, wie Bedingungen im CMS gepflegt werden (feste Typen mit Schwellwert oder im Code), welche ersten Erfolge es gibt (etwa 8 bis 12), geheime Erfolge, ob Erfolge Brainpower oder Trophäen geben.
2. den Regeln der Herausforderung: Fragen-Pool (nur sichtbare Fächer, nur schon gelernte Themen?), Anzahl, Kosten von 25 BP beim Start, was bei Abbruch passiert, EP je Frage und Bonus, Verhältnis zur Tagesregel E-015 und zu einem laufenden Booster, Trophäen, wie oft am Tag.
3. kleinen Schritten in der Reihenfolge des Projekts: Entscheidungen (E-040 ff.) → Design-Artefakt zuerst (Glossar, Raster im Profil, Zeile im Hub, Screens der Herausforderung, E-006) → Backend mit Tests → CMS → App mit Tests → Testpersonen und ein Playwright-Ablauf → Doku. Je Schritt ein Commit-Vorschlag.
4. was ausdrücklich nicht in G3 gehört (z. B. Trophäen tauschen, Avatar-Teile freischalten, Team-Herausforderung).

Frag mich eine Entscheidung nach der anderen. Arbeite auf `feat/g3-erfolge` von `dev`, Diff-Gate wie immer.

---

Entstanden am 2026-10-17 am Ende der Session „Schnellere Tests, Theme-Umschalter, Playwright, gemischte Antworten“. Vor dem Einsatz prüfen, ob der Stand oben noch stimmt.
