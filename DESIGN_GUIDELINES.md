# DESIGN_GUIDELINES.md — Design-Richtlinien & CSS-Fundament

Dieses Dokument beschreibt das Design-System des Projekts. Es enthält **das
Fundament** (Tokens, Theme-System, Reset, Typografie) als kommentierte
Code-Blöcke – **keine fertigen Komponenten-Styles**.

> Die hier dokumentierten Custom Properties sind **1:1 identisch** mit
> [`css/tokens.css`](css/tokens.css). Diese Datei ist die Erklärung, `tokens.css`
> die ausführbare Quelle. Werden Tokens geändert, **beide** synchron halten.

---

## 1. CSS Custom Properties (Design-Tokens)

Alle Werte des Designs laufen über Custom Properties. Komponenten verwenden
**nur** diese Tokens, niemals fest verdrahtete Farben/Größen.

### Akzentfarbe

```css
--color-accent:        #ff8c42;                 /* Basis: Buttons, Links, Highlights (Hellorange) */
--color-accent-hover:  #ff7a26;                 /* Hover/aktiv – etwas kräftiger */
--color-accent-muted:  rgba(255,140,66,0.15);   /* Transparente Fläche/Glow */
```

### Hintergrund- und Flächen-Ebenen (Dark Mode Default)

```css
--color-bg:         #121212;   /* Seitenhintergrund (tiefste Ebene) */
--color-surface:    #1e1e1e;   /* Card-Ebene 1 (Karten, Panels) */
--color-surface-2:  #2a2a2a;   /* Card-Ebene 2 (hervorgehoben, Inputs) */
--color-border:     #2a2a2a;   /* Rahmen und Trennlinien */
```

### Textfarben

```css
--color-text:        #e8e8e8;  /* Haupttext */
--color-text-muted:  #a0a0a0;  /* Sekundärtext, Hinweise, Labels */
```

### Abstände (4px-Basis-Skala)

```css
--spacing-xs: 4px;    /* enge Abstände, Icon-Text-Lücken */
--spacing-sm: 8px;    /* kleine Innenabstände */
--spacing-md: 16px;   /* Standard-Abstand */
--spacing-lg: 24px;   /* Sektionsabstände */
--spacing-xl: 48px;   /* große Layout-Abstände */
```

### Eckenradien

```css
--radius-sm: 6px;     /* Inputs, kleine Buttons */
--radius-md: 12px;    /* Karten, Panels */
--radius-lg: 20px;    /* große Container/Hero */
```

### Schrift

```css
/* System-Fonts – keine externen CDN-Fonts */
--font-family-base: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
--font-family-mono: ui-monospace, "Cascadia Code", "Courier New", monospace;

--font-weight-normal: 400;
--font-weight-bold:   700;
```

### Typografie-Skala

```css
--font-size-xs: 0.75rem;   /* 12px – Hinweise, Fußnoten        | line-height 1.4  */
--font-size-sm: 0.875rem;  /* 14px – Sekundärtext, Labels      | line-height 1.45 */
--font-size-md: 1rem;      /* 16px – Fließtext (Basis)         | line-height 1.5  */
--font-size-lg: 1.25rem;   /* 20px – Unterüberschriften        | line-height 1.3  */
--font-size-xl: 1.75rem;   /* 28px – Seitenüberschriften       | line-height 1.2  */
```

Empfehlung:
- Fließtext: `--font-size-md` + `--font-weight-normal`
- Überschriften: `--font-size-lg` / `--font-size-xl` + `--font-weight-bold`

### Sonstiges

```css
--transition-speed: 0.15s;   /* einheitliche Dauer für Hover/Transitions */
```

---

## 2. Theme-System (Dark/Light)

**Dark Mode ist Standard.** Das aktive Theme wird über das Attribut
`data-theme` auf dem `<html>`-Element gesteuert.

- Kein Attribut **oder** `data-theme="dark"` → Dark Mode (Standardwerte)
- `data-theme="light"` → Light Mode (überschreibt nur die Farb-Tokens)

```css
/* Standard – Dark Mode (Default, auch ohne Attribut aktiv) */
:root,
[data-theme="dark"] {
  --color-bg:            #121212;
  --color-surface:       #1e1e1e;
  --color-surface-2:     #2a2a2a;
  --color-border:        #2a2a2a;
  --color-text:          #e8e8e8;
  --color-text-muted:    #a0a0a0;
  --color-accent:        #ff8c42;
  --color-accent-hover:  #ff7a26;
  --color-accent-muted:  rgba(255,140,66,0.15);
}

/* Light Mode – nur Farb-Tokens werden überschrieben */
[data-theme="light"] {
  --color-bg:            #f5f5f5;
  --color-surface:       #ffffff;
  --color-surface-2:     #ececec;
  --color-border:        #dddddd;
  --color-text:          #1a1a1a;
  --color-text-muted:    #5a5a5a;
  --color-accent-hover:  #f2741f;
}

/* Theme-unabhängige Tokens (Abstände, Radien, Schrift) – in separatem :root-Block */
:root {
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 48px;

  --radius-sm: 6px;
  --radius-md: 12px;
  --radius-lg: 20px;

  --font-family-base: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --font-family-mono: ui-monospace, "Cascadia Code", "Courier New", monospace;
  --font-weight-normal: 400;
  --font-weight-bold:   700;

  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-md: 1rem;
  --font-size-lg: 1.25rem;
  --font-size-xl: 1.75rem;

  --transition-speed: 0.15s;
}
```

Theme per JS umschalten (Beispiel):

```js
// Dark → Light
document.documentElement.setAttribute("data-theme", "light");
// Light → Dark
document.documentElement.setAttribute("data-theme", "dark");
```

> Nicht-farbliche Tokens (Abstände, Radien, Schrift) sind theme-unabhängig und
> müssen nie überschrieben werden.

---

## 3. `@view-transition`

Global gesetzt für sanfte Seitenübergänge bei Navigationen:

```css
@view-transition {
  navigation: auto;
}
```

**Browser-Hinweis:** Aktuell **Chromium-only**. In Firefox/älteren Browsern
greift graceful degradation – Navigation funktioniert normal, nur ohne Animation.
Funktionalität niemals von View Transitions abhängig machen.

---

## 4. Basis-Reset

Minimaler, vorhersehbarer Ausgangszustand:

```css
*,
*::before,
*::after {
  box-sizing: border-box;   /* Größen inklusive Padding/Border rechnen */
}

* {
  margin: 0;                /* Browser-Standardabstände entfernen */
  padding: 0;
}
```

---

## 5. Was gehört wohin?

| Datei                  | Inhalt                                                        |
|------------------------|---------------------------------------------------------------|
| `css/tokens.css`       | **Nur** Custom Properties (`:root` + Theme-Blöcke). Sonst nichts. |
| `css/style.css`        | Reset, Layout und **konkrete Komponenten-Styles** – nutzen ausschließlich die Tokens aus `tokens.css`. |
| `css/<thema-id>.css`   | Tool-spezifische Styles, **scoped** unter einem eigenen Klassen-Selektor (z. B. `.dst { … }`). |
| `DESIGN_GUIDELINES.md` | Dieses Dokument: Erklärung & Fundament-Referenz.              |

**Regeln:**

- Komponenten-Styles leben in `css/style.css` und greifen **nur** auf Tokens zu
  (`var(--color-…)`, `var(--spacing-…)` …) – keine hartkodierte Farben/Größen.
- `tokens.css` wird **immer vor** `style.css` eingebunden (siehe `CLAUDE.md`).
- Kein `!important` (siehe `CLAUDE.md`).
- Keine externen Fonts per `@import url(…)` oder `<link>` — System-Fonts reichen.
