# Phase 4: Frontend-Entkopplung & Bereinigung (Modulare Unterschritte)

## 1. Übersicht

In dieser Phase wird das JavaScript-Frontend schrittweise von Supabase auf die neuen PHP-Endpunkte umgestellt und anschließend komplett von veralteten Supabase-Skripten und Config-Dateien befreit. 

Die Unterschritte sind so strukturiert, dass zu keinem Zeitpunkt das Projekt in einen unvollständigen Zustand gerät.

---

## 2. Die 4 Unterschritte von Phase 4

```
┌─────────────────────────────────────────────────────────────┐
│ 4.1: Auth-Modul umbauen (src/js/auth.js)                   │
└──────────────────────────────┬──────────────────────────────┘
                               │
       ┌───────────────────────┴───────────────────────┐
       ▼                                               ▼
┌─────────────────────────────┐ ┌─────────────────────────────┐
│ 4.2: Stats- & Gamification  │ │ 4.3: Quiz-Controller       │
│ umbauen (stats.js, level.js)│ │ anpassen (app.js, etc.)     │
└─────────────────────────────┘ └─────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 4.4: HTML bereinigen & Supabase-Altlasten löschen           │
└─────────────────────────────────────────────────────────────┘
```

---

### Unterschritt 4.1: Auth-Modul modernisieren (`src/js/auth.js`)
* **Aufgabe:** Umstellung der internen Logik auf Session-basierte HTTP-Requests bei 100%iger Wahrung der bestehenden Funktionsaufrufe (`Auth.login()`, `Auth.logout()`, `Auth.requireLogin()`).
* **Vorteil:** Keine einzige HTML-Seite muss an ihrer Login-Logik etwas ändern.
* **Kernänderung:**
  ```javascript
  // Vorher:
  const { error } = await sb.auth.signInWithPassword({ email, password });
  // Nachher:
  const res = await fetch('/api/auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  ```

---

### Unterschritt 4.2: Stats- & Level-Logik umbauen (`src/js/stats.js`, `src/js/level.js`)
* **Aufgabe:** Ersetzen der direkten PostgreSQL-Tabellenabfragen durch Aufrufe an `/api/stats.php`.
* **Betroffene Funktionen:**
  1. `Stats.ladeFachStats(fachId)` $\to$ `fetch('/api/stats.php?action=fach_stats&fach=' + fachId)`
  2. `Level.getUserStats()` $\to$ `fetch('/api/stats.php?action=user_stats')`
  3. `Level.verbrauchEnergie()` $\to$ läuft serverseitig in der API
  4. Trophäen-Tausch in `tauschen.html` $\to$ `fetch('/api/level.php?action=trade_trophy', { method: 'POST' })`
* **Was unverändert bleibt:** Die Berechnung von Level-Kurven (Bronze/Silber/Gold/Platin-Tiers), SVG-Icons und Progress-Bars bleibt wie gewohnt erhalten.

---

### Unterschritt 4.3: Quiz-Auswertung entkoppeln (`src/js/app.js`, `src/tagesquiz.html`)
* **Aufgabe:** Statt Punkte im Browser zu berechnen und per `sb.from('quiz_results').insert()` zu speichern, senden die Quizzes nun die Roh-Antworten an `/api/quiz-submit.php`.
* **Vorteil:**
  * Das UI zeigt weiterhin die Auswertung und Medaillen an.
  * Das Ergebnis ist aber serverseitig verifiziert und fälschungssicher gespeichert.

---

### Unterschritt 4.4: HTML-Bereinigung & Löschen von Altlasten
Sobald die Skripte funktionieren, werden alle Reste von Supabase und Netlify sauber entfernt:

1. **HTML-Dateien bereinigen (17 Dateien):**
   * Entfernen des externen CDN-Tags: `<script src="https://cdn.jsdelivr.net/..."></script>`
   * Entfernen von `<script src="/js/config.js"></script>`
   * Entfernen von `<script src="/js/supabase.js"></script>`
2. **Dateien und Ordner löschen:**
   * `src/js/supabase.js`
   * `src/js/config.js`
   * `scripts/gen-config.js`
   * `netlify.toml`
   * Ordner `.netlify/`
3. **Ergebnis:** Das Projekt ist schlanker, besitzt keine überflüssigen Abhängigkeiten mehr und läuft vollständig autark auf World4You.

---

## 3. Checkliste für Phase 4

- [ ] 4.1: `src/js/auth.js` auf `/api/auth.php` umgestellt & Login/Logout im Browser getestet
- [ ] 4.2: `src/js/stats.js` & `src/js/level.js` auf `/api/stats.php` umgestellt
- [ ] 4.3: Quiz-Abschluss in `app.js` und `tagesquiz.html` auf Server-Validierung umgestellt
- [ ] 4.4: 17 HTML-Dateien bereinigt (Supabase-Tags entfernt)
- [ ] 4.4: Überflüssige Dateien (`config.js`, `supabase.js`, `gen-config.js`, `netlify.toml`) gelöscht
