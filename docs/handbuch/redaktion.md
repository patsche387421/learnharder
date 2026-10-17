# Handbuch für die Redaktion (Stand 2026-10-17)

So pflegst du Fächer, Themen, Theorie und Fragen im CMS von learnharder.
Für Autor:innen, Tester:innen und Admins. Konten und Schulstruktur stehen im Betriebshandbuch (`betrieb.md`).

## Anmelden

- Adresse: lokal `http://localhost/admin`, im Betrieb `https://api.learnharder.app/admin` (ab M4, E-019).
- Anmelden dürfen Admins, Autor:innen und Tester:innen. Lehrkräfte und Schüler:innen nutzen nur die App.
- Inhalte gibt es vorerst nur auf Deutsch (E-012).

## Wer darf was

| | Autor:in | Tester:in | Admin |
|---|:---:|:---:|:---:|
| Inhalte ansehen | ✓ | ✓ | ✓ |
| Anlegen und bearbeiten | ✓ | – | ✓ |
| Veröffentlichen | – | – | ✓ |
| Löschen | – | – | ✓ |

In der App sehen alle drei Rollen jedes veröffentlichte Fach, auch ohne Klasse (E-024). So kannst du prüfen, wie ein Inhalt bei Schüler:innen aussieht.
Entwürfe und Inhalte in Prüfung zeigt nur das CMS.

## Wie die Inhalte zusammenhängen

```
Fach (z. B. POS)
└── Thema (z. B. Schleifen)
    ├── Theorie: Abschnitte mit Text
    └── Fragen: je 2 bis 6 Antworten
```

Schüler:innen sehen einen Inhalt nur, wenn alles davon zutrifft:

1. Das Fach ist **aktiv** und hat mindestens ein veröffentlichtes Thema.
2. Das Fach steht im **Lehrplan ihrer Klasse**. Den pflegt ein Admin unter „Schule“.
3. Das Thema ist **veröffentlicht**. Liegt das eigene Level unter „Ab Level“, ist die Lerneinheit gesperrt. Die Theorie bleibt lesbar, Lernen wird nie gesperrt.
4. In der Lerneinheit erscheinen nur **veröffentlichte Fragen**.

## Vom Entwurf zur App

Themen und Fragen haben einen Status. Fächer haben keinen, dort schaltet „Aktiv“ sie ein und aus.

```
Entwurf  →  In Prüfung  →  Veröffentlicht
(du schreibst)  (fertig, bitte prüfen)  (Admin gibt frei, steht in der App)
```

- Neue Themen und Fragen starten als **Entwurf**.
- Bist du fertig, stell den Status auf **In Prüfung**.
- **Veröffentlichen** dürfen nur Admins (E-014). Sie markieren in der Liste mehrere Einträge und wählen die Mehrfachaktion „Veröffentlichen“.
- Änderst du etwas Veröffentlichtes, geht es beim Speichern zurück auf **In Prüfung** und verschwindet aus der App, bis ein Admin es wieder freigibt.
  Das gilt auch für jede Änderung an der Theorie eines veröffentlichten Themas. Grund: Schüler:innen sind minderjährig, eine falsche Antwort soll nicht ungeprüft live gehen.

## Fach anlegen

„Inhalte“ → „Fächer“ → „Erstellen“.

| Feld | Bedeutung |
|---|---|
| URL-Kürzel | Kurzer Name für die Adresse, z. B. `pos`. Nur Kleinbuchstaben, Ziffern und Bindestriche, einmalig. Die App zeigt es groß als Fachkürzel |
| Name, Beschreibung | So heißt das Fach in der App |
| Icon | Pflichtfeld, die App nutzt es derzeit nicht |
| Reihenfolge | Kleinere Zahlen stehen in der Fächerliste weiter oben |
| Aktiv | Ausgeschaltet erscheint das Fach nirgends in der App |

Das Bild auf der Fachkarte hängt am URL-Kürzel. Bilder gibt es für `dbi`, `medt`, `nsvs`, `pos`, `syp`, `tinf` und `wir`.
Für ein neues Fach mit anderem Kürzel braucht es erst ein Bild im Design-System. Frag dafür einen Admin.

## Thema anlegen

„Inhalte“ → „Themen“ → „Erstellen“.

| Feld | Bedeutung |
|---|---|
| Fach | Zu welchem Fach das Thema gehört |
| URL-Kürzel | Wie beim Fach, aber nur innerhalb des Fachs einmalig |
| Titel, Beschreibung | So heißt das Thema in der App |
| Ab Level | Ab diesem Level ist das Thema freigeschaltet. `1` heißt: sofort offen |
| Reihenfolge | Position im Fach |
| Status | Siehe „Vom Entwurf zur App“ |

## Theorie schreiben

Die Theorie pflegst du im Thema selbst: Thema öffnen, unten im Bereich „Theorie“ auf „Erstellen“.
Ein Thema hat mehrere **Abschnitte**, jeder mit Titel, Reihenfolge und Text.

- Kurz halten, ein Gedanke pro Absatz.
- Der Text ist Markdown. Die Leiste über dem Feld hilft bei **fett**, *kursiv*, Links, Listen und Codeblöcken.
- Bilder lassen sich noch nicht hochladen.
- Abschnitte löschen dürfen nur Admins.

## Fragen schreiben

„Inhalte“ → „Fragen“ → „Erstellen“. Zuerst das Fach wählen, dann das Thema.

| Feld | Bedeutung |
|---|---|
| Frage | Der Fragetext. Zeilenumbrüche bleiben erhalten, z. B. für Code. Kein Markdown |
| Erklärung | Erscheint nach dem Antworten und begründet die richtige Lösung |
| Typ | „Eine richtige Antwort“ oder „Mehrere richtige Antworten“ |
| Schwierigkeit | 1 (sehr leicht) bis 5 (sehr schwer) |
| Antworten | 2 bis 6. Bei einer richtigen Antwort genau eine markieren, bei mehreren mindestens eine. Die App mischt die Antworten in jeder Lerneinheit neu, die Reihenfolge hier gilt nur im CMS. Deshalb keine Antworten wie „Alle oben genannten“ oder „A und B“ |
| EP | Erfahrungspunkte für eine richtige Antwort, Standard `2` |
| Reihenfolge | Position im Thema |

Gut zu wissen: Pro Frage gibt es die EP nur einmal pro Tag (E-015). Wer dieselbe Frage am selben Tag wiederholt, übt weiter, bekommt aber 0 EP.

## Löschen

Löschen dürfen nur Admins. Auch sie können Inhalte nicht löschen, mit denen schon gelernt wurde, weil sonst der Lernverlauf der Schüler:innen verloren ginge.
Das CMS meldet dann „Nicht gelöscht“ und sagt, was stattdessen geht:

| Inhalt | Statt löschen |
|---|---|
| Fach mit Lernverlauf oder im Lehrplan | „Aktiv“ ausschalten |
| Thema, mit dem schon gelernt wurde | Status auf Entwurf |
| Frage, die schon beantwortet wurde | Status auf Entwurf |

## Meldungen im CMS

| Meldung | Was sie heißt |
|---|---|
| „Gespeichert. Der Status ist jetzt „In Prüfung“.“ | Du hast etwas Veröffentlichtes geändert. Ein Admin muss es wieder freigeben |
| „Das Thema ist jetzt „In Prüfung“.“ | Du hast die Theorie eines veröffentlichten Themas geändert |
| „Markiere genau eine richtige Antwort.“ | Beim Typ „Eine richtige Antwort“ sind keine oder mehrere markiert |
| „Markiere mindestens eine richtige Antwort.“ | Beim Typ „Mehrere richtige Antworten“ ist keine markiert |
| „Nur Kleinbuchstaben, Ziffern und Bindestriche …“ | Das URL-Kürzel hat Großbuchstaben, Leerzeichen oder Umlaute |
| „Nicht gelöscht“ | Siehe „Löschen“ |

## Vor „In Prüfung“ kurz prüfen

- Stimmt die richtige Antwort, und ist die Erklärung verständlich ohne Theorie daneben?
- Sind die falschen Antworten plausibel, aber eindeutig falsch?
- Versteht man jede Antwort für sich, ohne zu wissen, an welcher Stelle sie steht?
- Passen Schwierigkeit und EP zu ähnlichen Fragen im Thema?
- Hast du die Begriffe aus dem Glossar verwendet (`docs/design/Sprache.md`)?
