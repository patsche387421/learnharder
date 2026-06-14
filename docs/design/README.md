# LearnHarder Design-System

Dieser Ordner enthält Design-Referenzen, Mockups und 
Komponenten-Spezifikationen.

## Struktur
- `INBOX/` - Hier werden neue Claude-Design-Exporte abgelegt.
  Claude Code sortiert sie beim nächsten Lauf automatisch ein.
- `components/` - Pro UI-Komponente ein Markdown + ggf. HTML-Beispiel.
  Beispiel: topbar.md + topbar-example.html
- `pages/` - Mockups ganzer Seiten.

## Workflow für neue Designs
1. HTML/CSS-Export aus Claude Design in `INBOX/` ablegen
   (Dateiname egal, z.B. `quiz-card-v2.html`)
2. Beim nächsten Claude Code Lauf: 
   "Sortiere die Dateien in docs/design/INBOX/"
3. Claude Code analysiert, benennt um, verschiebt nach 
   components/ oder pages/ und erstellt ein passendes .md daneben

## Regel für Claude Code
Vor jeder UI-Änderung: prüfe docs/design/components/ und 
docs/design/pages/ ob ein Mockup existiert. Falls ja: 
halte dich daran (Tokens aus src/css/tokens.css verwenden).
