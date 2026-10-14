# SubjectCard

Ein Fach in der Liste unter „Lernen“: illustriertes Fach-Icon, Kürzel, voller Name und Fortschritt.

**Aufbau:** `a.lh-subject` > `img.lh-subject__art` (`subject-<kürzel>.svg`, 48px) · `.lh-subject__title` · `.lh-subject__name` · `.lh-xp.lh-subject__progress`.

- Der Fortschritt verwendet die `XpBar`-Leiste in `accent`, mit Fach-Level und erledigten Themen in Space Mono.
- Ein noch nicht begonnenes Fach zeigt „Noch nicht begonnen“ statt „–“ oder „0 %“.
- Gesperrt (`.lh-subject--locked`, kein Link): das Icon entsättigt, `ui-locked` und ein Satz, warum es gesperrt ist.
- Am Handy eine Spalte. Ab Tablet ein Raster mit `minmax(280px, 1fr)`.
- Die Karte ist als Ganzes antippbar.

**Die App liefert:** Fach, Fortschritt und ob das Fach freigeschaltet ist.
