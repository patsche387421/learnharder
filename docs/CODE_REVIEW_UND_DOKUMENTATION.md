# LearnHarder — Umfassendes Code-Review & Technische Dokumentation

## 1. Architektur-Überblick & Systemdesign

LearnHarder ist als **modulare, frameworkfreie Webapplikation** konzipiert. Sie verzichtet bewusst auf Build-Pipelines wie Webpack oder Vite und setzt auf **Vanilla JavaScript (ES6 IIFE-Pattern)**, **semantisches HTML5** und **CSS-Custom-Properties (Design Tokens)**.

### Architektur-Schichten:
```
┌────────────────────────────────────────────────────────┐
│ UI-Schicht: HTML-Views (17 Seiten)                    │
│ dashboard.html, faecher.html, pos.html, tagesquiz.html │
└──────────────────────────┬─────────────────────────────┘
                           │
┌──────────────────────────▼─────────────────────────────┐
│ Präsentations- & Komponenten-Module                    │
│ • layout.js        (Topbar, Footer, Theme-Toggle)      │
│ • icons.js         (SVG-Linien- & Gamification-Icons)  │
│ • content-icons.js (Fach- & Themen-Grafiken / Fallback)│
└──────────────────────────┬─────────────────────────────┘
                           │
┌──────────────────────────▼─────────────────────────────┐
│ Fachlogik- & Content-Module                            │
│ • app.js           (JSON-Laden, Seiten-Renderer)       │
│ • level.js         (100-Level-Kurve, Streaks, Energie) │
│ • pos/datenstrukturen.js (Interaktive Lern-Module)     │
└──────────────────────────┬─────────────────────────────┘
                           │
┌──────────────────────────▼─────────────────────────────┐
│ Daten- & Auth-Schicht (Aktuell Supabase → Ziel: PHP)   │
│ • auth.js          (Session-Guard, Login/Logout)       │
│ • stats.js         (Fach- & Themen-Statistiken, XP)    │
│ • supabase.js      (Client-Initialisierung)            │
│ • config.js        (Keys, aktuell gitignored)          │
└────────────────────────────────────────────────────────┘
```

---

## 2. Modul-Reviews im Detail (Stärken, Schwächen, Empfehlungen)

### 2.1 `src/js/auth.js` (Authentifizierungs-Modul)
* **Verantwortlichkeit:** Kapselt Login, Logout, Session-Caching und den Navigations-Guard `requireLogin()`.
* **Code-Analyse:**
  * **Stärken:**
    * Saubere Kapselung als IIFE (`const Auth = (() => { ... })()`).
    * Session-Listener `sb.auth.onAuthStateChange()` fängt Tab-Fokus und Session-Ablauf ab.
    * Gut durchdachte Heuristik in `displayName()`, die E-Mail-Adressen (z. B. `max.mustermann@...`) in saubere Vornamen (`Max`) umwandelt.
  * **Schwachstellen / Risiken:**
    * Direkte Abhängigkeit zum globalen Objekt `SupabaseClient`.
    * Client-Side Auth: Nicht autorisierte Nutzer werden erst per JavaScript weitergeleitet (`location.href = "/index.html"`). Bis zum JS-Lauf könnte der DOM kurz sichtbar sein (FOUC).
  * **Empfehlung für PHP-Migration:**
    * Umstellung der Aufrufe auf `/api/auth.php`.
    * HTTP-Weiterleitung serverseitig bei geschützten PHP-Seiten.

### 2.2 `src/js/layout.js` (Topbar, Navigation & Footer)
* **Verantwortlichkeit:** Globales Chrome-Rendering (Header, User-Status, Navigation, Dark/Light-Theme, Footer).
* **Code-Analyse:**
  * **Stärken:**
    * Verhindert doppelten HTML-Code über 17 Seiten hinweg.
    * Reagiert auf System-Präferenzen (`prefers-color-scheme`) und merkt sich den Theme-Zustand in `localStorage`.
    * Saubere Barrierefreiheit: Korrekte ARIA-Attribute (`aria-expanded`, `aria-controls`, `aria-label`) am Hamburger-Menü.
    * Gradient-IDs werden mit Instanzzählern versehen, um SVG-ID-Kollisionen zu verhindern.
  * **Schwachstellen:**
    * Reines DOM-String-Building (`innerHTML += '...'`). Bei großen DOM-Bäumen könnte `document.createElement` oder `<template>`-Tags noch sauberer sein.
  * **Empfehlung:** Beibehalten – das Modul ist stabil, flexibel und theme-unabhängig.

### 2.3 `src/js/level.js` (Gamification & Progression)
* **Verantwortlichkeit:** 100-Level-Kurve, Prestige-Zyklen, Energie-Haushalt, Streaks und Tages-Quiz-Verwaltung.
* **Code-Analyse:**
  * **Mathematisches Modell:**
    * Kurve: $Schwellenwert(n) = \text{round}(n^{1.5} \cdot 8)$.
    * Level 100 entspricht genau 8.000 XP (ein Prestige-Zyklus).
    * Level-Bänder (Tiers): Bronze (1–24), Silber (25–49), Gold (50–74), Platin (75–100).
  * **Stärken:**
    * `berechneFortschritt()` und `berechneStreak()` sind **reine Funktionen (Pure Functions)** ohne Seiteneffekte und hervorragend testbar.
    * Streak-Berechnung berücksichtigt Zeitzonen (UTC) und bietet eine 1-Tages-Kulanz.
  * **Schwachstellen:**
    * `verbrauchEnergie()` und `rechargeEnergie()` schreiben direkt per Supabase-Client in `user_stats`. Schüler könnten in der Konsole `sb.from('user_stats').update({ energy: 999 })` aufrufen.
  * **Empfehlung:** Die Berechnungsmathematik unverändert beibehalten, aber das Persistieren der Energie in die PHP-API verlegen.

### 2.4 `src/js/stats.js` (Lernfortschritt & Quiz-Historie)
* **Verantwortlichkeit:** Speichern und Abfragen von Fach-Statistiken, Quiz-Punkten und Themen-Status.
* **Code-Analyse:**
  * **Stärken:**
    * Saubere Aggregation: Fachstatistiken werden aus Einzelthemen berechnet und via `upsert` synchronisiert.
  * **Schwachstellen (Manipulationsgefahr):**
    * In `speichereLernfortschritt(themaId, richtig, gesamt)` übergibt der Browser die Anzahl der richtigen Antworten. Hier setzt der geplante PHP-Schummel-Schutz an.
  * **Empfehlung:** Schrittweise Umstellung auf `/api/quiz-submit.php`.

### 2.5 `src/js/icons.js` & `src/js/content-icons.js` (Design-System & SVG)
* **Verantwortlichkeit:** Bereitstellung von Vektor-Icons ohne Icon-Fonts oder externe CDN-Libraries.
* **Code-Analyse:**
  * **Stärken:**
    * Zweigeteiltes System: Monochrome Linien-Icons nutzen `currentColor` und passen sich automatisch dem Theme an; Gamification-Icons besitzen eigene, optimierte Farbverläufe.
    * `content-icons.js` verfügt über einen eleganten Buchstaben-Badge-Fallback für fehlende Grafiken.
    * Null externe Abhängigkeiten – 100% DSGVO-fest.

### 2.6 `src/js/app.js` (App-Controller & Content-Lader)
* **Verantwortlichkeit:** Lädt JSON-Dateien (`manifest.json`, Fach-Theorie, Fragen) und rendert die Ansichten.
* **Code-Analyse:**
  * **Stärken:**
    * Fehlerbehandlung bei `loadJSON` mit klaren Statusmeldungen.
    * Paralleles Laden von Daten via `Promise.all([Stats.ladeFachStats(...), Stats.ladeFachThemenProgress(...)])` sorgt für kurze Ladezeiten.

---

## 3. CSS- & Design-System-Review

* **`src/css/tokens.css`:**
  * Vorbildliche Single Source of Truth (SSOT).
  * Enthält ausschließlich Variablen (Colors, Radii, Shadows, Spacing).
  * Dunkles Farbschema (#0F0C1B) als Standard, Light Mode über `[data-theme="light"]`.
* **`src/css/fonts.css`:**
  * Lokale Schriften (`Space Grotesk`, `Space Mono`) im modernen WOFF2-Format mit `font-display: swap`.
  * Absolut DSGVO-konform ohne Google-Fonts-Verbindung.

---

## 4. Sicherheits- und Refactoring-Roadmap

1. **Priorität 1 (DSGVO):**
   * Lokales Hosten der Vendor-Bibliotheken (Schritt 1 der Migration).
2. **Priorität 2 (Integrität & Cheat-Schutz):**
   * Verlagerung der Quiz-Auswertung (`stats.js` $\to$ PHP-Backend).
3. **Priorität 3 (Performance & Caching):**
   * Beibehaltung der `.htaccess`-Cache-Befehle für JSON-Lerninhalte (`Cache-Control: no-cache, must-revalidate`).
