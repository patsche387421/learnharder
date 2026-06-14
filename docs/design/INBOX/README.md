# Design Inbox

Hier werden rohe Exporte aus Claude Design abgelegt.
Dateinamen sind egal, Claude Code sortiert beim nächsten Lauf.

## Sortier-Regeln (für Claude Code)
- Enthält die Datei eine ganze Seite (header + main + footer)?
  → nach docs/design/pages/ verschieben
- Ist es eine einzelne Komponente (Card, Button, Modal, etc.)?
  → nach docs/design/components/ verschieben
- Beim Verschieben: aussagekräftigen Namen vergeben 
  (z.B. quiz-card-example.html)
- Daneben ein gleichnamiges .md erstellen mit:
  - Kurzbeschreibung der Komponente
  - Welche Tokens aus tokens.css verwendet werden
  - Welche Seiten die Komponente nutzen
  - Notizen / TODOs
