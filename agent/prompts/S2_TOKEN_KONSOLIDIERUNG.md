# Session-Prompt S2 — Token-Konsolidierung

> Wiederverwendbarer Prompt für die S2-Session laut `docs/ROADMAP.md`. Liegt in der
> Hauptlinie (`dev`), nicht im Feature-Branch.
>
> **Voraussetzung / Sequenzierung:** Dieser Prompt wurde als eigener Commit auf `dev`
> abgelegt. S2 startet daher AUF `dev` (Arbeitsbaum inkl. dieser Datei, sauber), prüft
> `git status`, und zweigt ERST DANN den Branch `chore/s2-token-konsolidierung` ab.
> Reihenfolge: Prompt-Commit auf dev → `git status` (jetzt inkl. Prompt) → Branch.

---

```text
LearnHarder — Session S2: Token-Konsolidierung (CODE, eigener Branch)

Code-Session laut docs/ROADMAP.md. NICHT direkt auf dev: zuerst Branch von dev
abzweigen (chore/s2-token-konsolidierung), Commits dort, kein Push, kein Merge.
Conventional Commits auf Deutsch, kein Co-Authored-By.
Diff-Gate pro Commit wie in S1: schreiben → unstaged diff zeigen → mein OK →
git add → cached diff → mein OK → commit.

Vorab (read-only): git status — dev sauber bestätigen; dann Branch anlegen.

Leitprinzip: Visuell ändert sich NICHTS ungewollt. Jede bewusste Wertänderung
wird im Diff je Stelle einzeln begründet.

──────────────────────────────────────────
SCOPE & BEFUNDE (read-only erhoben — in S2 kurz gegenprüfen)
──────────────────────────────────────────
Im Scope: src/css/tokens.css, src/css/style.css (+ btn-Markup in 6 Dateien).
NICHT im Scope: src/css/pos/datenstrukturen.css — eigenständiges POS-Modul mit
eigenen Hardcodes (--amber/--teal, rgba-Brandtöne, #101216) und den EINZIGEN
!important-Stellen im Projekt. Eigene Folge-Session.

tokens.css hat bereits: Radien --radius-sm(4)/md(8)/lg(12)/xl(16)/2xl(24)/full(999)
(+ Alias --radius=lg), Schatten --shadow-sm(0 2px 8px) / --shadow-lg(0 4px 24px),
Glows, --primary(=--purple-700, „Marken-Primärton"), --border. Core !important-frei.

Hardcodes in style.css:
- Schatten: 60 (.auth-card) box-shadow: 0 20px 40px rgba(0,0,0,0.3) — kein Token.
- Radius-px auf Skala: 94, 109, 562, 1321 (8px → --radius-md);
  939/947/959/969/1090/1098 (99px → --radius-full).
- Radius-Ausreißer: 38 & 744 (6px), 1430/1437 (5px), 1509/1516 (3px) → klein;
  409 & 616 (10px) → off-scale; 306 (0 2px 2px 0) & 811 (0) → keine Skala-Fälle.
- .btn: 6 Klassen ohne Basis — .btn-primary(113, zusätzl. an #login-form button:112),
  .btn-ghost(337), .btn-cta(402), .btn-arrow(421), .btn-ghost-sm(557), .btn-lg(1057).
  16 Aufrufstellen: dashboard.html, fach.html, profil.html, tagesquiz.html,
  tauschen.html, js/layout.js.

──────────────────────────────────────────
ENTSCHEIDUNGEN (festgezurrt)
──────────────────────────────────────────
E1 Schatten: neues Token --shadow-xl: 0 20px 40px #0000004D (token-konformer
   Alpha ≈0.30, Stil wie --shadow-sm/-lg). .auth-card nutzt es. Wird in S4
   („Premium-Look via Shadow-Token") ohnehin gebraucht. KEIN Herabstufen auf -lg.
E2 .btn: echte .btn-Basisklasse + Modifier; Markup auf class="btn btn--…"
   umstellen (16 Stellen/6 Dateien). Kein Gruppen-Selektor-Workaround.
   → C3 in CSS-Commit (Basis+Modifier steht) und Markup-Commit (zieht nach) splitten.
E3 Radien — je Diff einzeln begründen:
   - 8px (94/109/562/1321) → --radius-md (Wert identisch, reiner Token-Swap).
   - 99px (×6) → --radius-full (Pills > halbe Höhe → optisch identisch).
   - 3/5/6px (38/744/1430/1437/1509/1516) → --radius-sm(4) angleichen (Δ≤3px,
     kleine Elemente, unkritisch).
   - 10px (409/616) → NICHT auf --radius-lg(12) verbiegen; eigenes Token ergänzen
     (Arbeitsname --radius-10, finalen Namen beim Komponenten-Sichten in C2 fixieren).
   - 0 / 0 2px 2px 0 (306/811) → bewusste Einzelwerte, NICHT zwangstokenisieren.

──────────────────────────────────────────
UMSETZUNG — je EIGENER Commit, Diff-Gate
──────────────────────────────────────────
C1 tokens.css (additiv, kein visueller Effekt): --shadow-xl (im C1-Diff prüfen:
   nutzen bestehende --shadow-* rgba() oder Hex-Alpha-Notation? --shadow-xl
   demselben Stil folgen — der Wert #0000004D oben setzt Hex-Alpha voraus),
   Button-Token --btn-radius/--btn-border/--btn-shadow, ggf. --radius-10 (E3).
   --brand-Alias NUR einführen, wenn S3 (Level-Rang-Badge/Energie-Pill) den Namen
   referenziert — sonst weglassen; im C1-Diff kurz entscheiden.
   Commit: chore(tokens): Shadow-/Button-Token (+10px-Radius) für S2 ergänzt
C2 style.css Hardcodes → Token: box-shadow:60 → var(--shadow-xl); Radien gemäß E3.
   Visuell identisch bzw. bewusst minimal angeglichen, je Stelle im Diff vermerkt.
   Commit: refactor(css): Schatten-/Radius-Hardcodes auf Token umgestellt
C3a style.css .btn-Basis + Modifier: geteilte Basis (cursor, font, border, radius
   via --btn-*, transition, display) + .btn--primary/-ghost/-cta/-ghost-sm/-lg;
   #login-form button auf .btn-Basis mitziehen. Noch keine Markup-Änderung.
   Commit: refactor(css): zentrale .btn-Basis + Button-Token eingeführt
C3b Markup: 16 Aufrufstellen auf class="btn btn--…" umstellen (6 Dateien).
   Commit: refactor(ui): Button-Markup auf .btn-Basis + Modifier umgestellt

──────────────────────────────────────────
VERIFIKATION (vor Abschlussbericht)
──────────────────────────────────────────
Dev-Server starten; Login + Dashboard + Tagesquiz + Tauschen + Fach + Profil
laden. Dark/Light umschalten. Prüfen: alle 6 Button-Varianten, .auth-card-Schatten,
Karten-Radien unverändert; Konsole fehlerfrei. Vorher/Nachher-Abgleich der
betroffenen Stellen.

Abschluss: geänderte Dateien + Commit-Hashes. Kein Push, kein Merge nach dev.
```
