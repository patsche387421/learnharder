# Button

Buttons für Aktionen: Primär (`ink`), Sekundär (Rahmen), Ghost und Gefahr, alle mindestens 48px hoch.

- **Primär** (`.lh-btn--primary`): die *eine* Hauptaktion pro Screen, z. B. Weiter, Prüfen, Starten. Neutral in `ink`, nie in der Rangfarbe, damit Handeln nicht mit Fortschritt konkurriert.
- **Sekundär** (`.lh-btn--secondary`): Alternativen.
- **Ghost** (`.lh-btn--ghost`): Nebensächliches wie Überspringen oder Abbrechen.
- **Gefahr** (`.lh-btn--danger`): nur für Aktionen, die sich nicht rückgängig machen lassen, z. B. „Konto löschen“. Fläche `wrong`. Steht nie allein: daneben ein Ghost-Button zum Abbrechen, davor ein Satz, was verloren geht.
- Am Handy volle Breite (`.lh-btn--block`), unten am Screen, in Daumenreichweite.
- Deaktiviert: `disabled`, Fläche `surface-sunken`. Statt einen Button grundlos zu deaktivieren, lieber sagen, was fehlt.
- Die Schrift ist immer die Text-Schrift (`button`-Stil), nie die System-Schrift.
- Fokus: 2px-Rahmen in `ink`.
