# DESIGN_GUIDELINES.md — Design-Richtlinien & CSS-Fundament

Dieses Dokument beschreibt das Design-System des Projekts. Es enthält **das
Fundament** (Tokens, Schrift, Theme-System, Reset) als kommentierte Referenz –
**keine fertigen Komponenten-Styles**.

> Die hier dokumentierten Custom Properties sind **1:1 identisch** mit
> [`css/tokens.css`](../src/css/tokens.css); die Schrift-Einbindung mit
> [`css/fonts.css`](../src/css/fonts.css). Diese Datei ist die Erklärung, die
> CSS-Dateien sind die ausführbare Quelle. Werden Tokens geändert, **beide**
> synchron halten. Werte stammen aus `docs/design/components/brand.html`.

---

## 1. Design-Tokens (`css/tokens.css`)

Alle Werte des Designs laufen über Custom Properties. Komponenten verwenden
**nur** diese Tokens, niemals fest verdrahtete Farben/Größen.

### 1.1 Farbpaletten (theme-unabhängig)

```css
/* Purple — Primärpalette (★ --purple-700 ist der Marken-Primärton) */
--purple-100: #F3E8FF;  --purple-200: #E9D5FF;  --purple-300: #D8B4FE;
--purple-400: #C084FC;  --purple-500: #A855F7;  --purple-600: #9333EA;
--purple-700: #7C3AED;  --purple-800: #5B21B6;  --purple-900: #3B0764;

/* Semantische Farben (mit sprechenden Aliasen) */
--gold:    #F59E0B;   --xp:     var(--gold);     /* XP / Belohnung   */
--success: #10B981;                              /* Richtig / Abschluss */
--error:   #EF4444;                              /* Falsch / Fehler  */
--warning: #F97316;   --timer:  var(--warning);  /* Timer / Achtung  */
--info:    #06B6D4;   --energy: var(--info);     /* Energie / Info   */
--indigo:  #4F46E5;                              /* Sekundär-Akzent (Verläufe) */

/* Fachfarben */
--fach-pos:  #7C3AED;  --fach-dbi:  #0EA5E9;  --fach-nsvs: #10B981;
--fach-medt: #F97316;  --fach-syp:  #EF4444;  --fach-wir:  #F59E0B;
--fach-tinf: #EC4899;
```

### 1.2 Typografie

```css
--font-display: 'Space Grotesk', system-ui, -apple-system, sans-serif;
--font-mono:    'Space Mono', ui-monospace, 'SFMono-Regular', monospace;

--fs-display: 40px;  --fs-h1:      28px;  --fs-h2:    22px;  --fs-h3:   18px;
--fs-body-lg: 16px;  --fs-body:    14px;  --fs-caption: 12px; --fs-label: 11px;

/* Space Grotesk hat KEIN Gewicht 800 (max. 700). Display/H1 nutzen --fw-bold. */
--fw-regular: 400;  --fw-medium: 500;  --fw-semibold: 600;  --fw-bold: 700;
```

### 1.3 Abstände

```css
--space-xs:  4px;  --space-sm:  8px;  --space-md:   12px;  --space-base: 16px;
--space-lg: 24px;  --space-xl: 32px;  --space-2xl:  48px;  --space-3xl:  64px;
```

### 1.4 Radien

```css
--radius-sm:  4px;  --radius-md:  8px;  --radius-lg:   12px;
--radius-xl: 16px;  --radius-2xl: 24px; --radius-full: 999px;
```

### 1.5 Schatten & Glows

```css
--shadow-sm:    0 2px 8px #00000066;
--shadow-lg:    0 4px 24px #00000088;
--glow-brand:   0 0 20px #7C3AED44;
--glow-gold:    0 0 20px #F59E0B55;
--glow-success: 0 0 20px #10B98155;
--glow-error:   0 0 20px #EF444455;
```

### 1.6 Komponenten-Tokens

```css
--gradient-brand:      linear-gradient(135deg, var(--purple-700), var(--indigo));
--gradient-gold:       linear-gradient(135deg, var(--gold), var(--warning));
--gradient-success:    linear-gradient(135deg, var(--success), #059669);
--gradient-text-brand: linear-gradient(135deg, var(--purple-500), #818CF8);
--text-on-primary:     #FFFFFF;   /* Text auf farbigen Buttons/Flächen */
```

### 1.7 Aliase (Abwärtskompatibilität)

Alte Token-Namen aus der Zeit vor dem Design-Umbau zeigen auf die neuen Tokens,
damit bestehende Komponenten-Styles in `style.css` weiter funktionieren:

```css
--bg:            var(--bg-base);
--primary:       var(--purple-700);
--primary-hover: var(--indigo);
--radius:        var(--radius-lg);
/* --surface, --surface-2, --text, --text-muted, --success, --error
   behalten ihren Namen und bekommen nur neue Werte (siehe 3.). */
```

---

## 2. Schriftarten (`css/fonts.css`)

Die Schriften sind **lokal gehostet** (keine externen CDNs). WOFF2-Dateien liegen
unter `src/assets/fonts/`, eingebunden über `@font-face` in `css/fonts.css` mit
`font-display: swap`.

| Familie | Gewichte | Dateien |
|---------|----------|---------|
| Space Grotesk | 400, 500, 600, 700 | `SpaceGrotesk-{Regular,Medium,SemiBold,Bold}.woff2` |
| Space Mono | 400, 700 | `SpaceMono-{Regular,Bold}.woff2` |

Es sind vollständige (nicht subgesetzte) Fonts → kein `unicode-range`; deutsche
Sonderzeichen (ä ö ü ß) sind enthalten.

`fonts.css` wird als **erstes** Stylesheet eingebunden (vor `tokens.css`).

---

## 3. Theme-System (Dark/Light)

**Dark Mode ist Standard.** Das aktive Theme wird über das Attribut `data-theme`
auf `<html>` gesteuert. Farbpaletten (1.1), Typografie, Abstände, Radien, Schatten
und Komponenten-Tokens sind **theme-unabhängig** – nur Oberflächen- und Textfarben
wechseln.

```css
/* Standard – Dark (Default, auch ohne Attribut aktiv) */
:root,
[data-theme="dark"] {
  --bg-base:     #0D0D1A;   --surface:     #131320;   --surface-2:   #1A1A2E;
  --border:      #1E1E3F;   --brand-muted: #2D1B69;
  --text:        #F1F5F9;   --text-muted:  #94A3B8;   --text-subtle: #64748B;
}

/* Light – überschreibt nur Oberflächen-/Textfarben */
:root[data-theme="light"] {
  --bg-base:     #F1F5F9;   --surface:     #FFFFFF;   --surface-2:   #E2E8F0;
  --border:      #CBD5E1;   --brand-muted: #EDE9FE;
  --text:        #0F172A;   --text-muted:  #475569;   --text-subtle: #64748B;
}
```

Theme per JS umschalten:

```js
document.documentElement.setAttribute("data-theme", "light"); // → Light
document.documentElement.setAttribute("data-theme", "dark");  // → Dark
```

---

## 4. `@view-transition`

Global gesetzt in `style.css` für sanfte Seitenübergänge bei Navigationen:

```css
@view-transition {
  navigation: auto;
}
```

**Browser-Hinweis:** Aktuell **Chromium-only**. In Firefox/älteren Browsern greift
graceful degradation – Navigation funktioniert normal, nur ohne Animation.
Funktionalität niemals von View Transitions abhängig machen.

---

## 5. Basis-Reset

Minimaler Ausgangszustand in `style.css`:

```css
* {
  box-sizing: border-box;   /* Größen inklusive Padding/Border rechnen */
}

body {
  margin: 0;
  font-family: var(--font-display);
  background: var(--bg);
  color: var(--text);
}
```

---

## 6. Was gehört wohin?

| Datei                  | Inhalt                                                        |
|------------------------|---------------------------------------------------------------|
| `css/fonts.css`        | **Nur** `@font-face`-Blöcke für die lokalen Schriften.        |
| `css/tokens.css`       | **Nur** Custom Properties (Paletten, Theme-Blöcke, Aliase). Sonst nichts. |
| `css/style.css`        | Reset, `@view-transition`, Layout und **konkrete Komponenten-Styles** – nutzen ausschließlich Tokens. |
| `css/<thema-id>.css`   | Tool-spezifische Styles, **scoped** unter eigenem Klassen-Selektor (z. B. `.dst { … }`). |
| `assets/fonts/`        | Lokale WOFF2-Schriftdateien.                                  |
| `assets/logo.svg`      | Logomark „Bolt Brain" (Marke).                                |
| `DESIGN_GUIDELINES.md` | Dieses Dokument: Erklärung & Fundament-Referenz.             |

**Regeln:**

- Komponenten-Styles leben in `css/style.css` und greifen **nur** auf Tokens zu
  (`var(--purple-700)`, `var(--space-lg)` …) – keine hartkodierten Farben/Größen.
- Einbinde-Reihenfolge: **`fonts.css` → `tokens.css` → `style.css`** (siehe `CLAUDE.md`).
- Kein `!important` (siehe `CLAUDE.md`).
- Schriften sind **lokal** gehostet – keine externen Font-CDNs (`@import`/`<link>`
  auf Drittanbieter).
