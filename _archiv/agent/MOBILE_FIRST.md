# MOBILE_FIRST.md — Verbindliche Mobile-First-Richtlinien

Gilt ab sofort für ALLE UI-Arbeiten in diesem Projekt (inkl. laufender Sessions).

## Grundprinzip
Zuerst für schmale Viewports gestalten; Basis-Styles (ohne Media-Query) = Mobile-Styles.
Erweiterung nach oben per `min-width`-Media-Queries. Keine `max-width`-Abwärtskorrekturen
als Default-Werkzeug.

> Bestand: `style.css` nutzt teils `max-width: 768px` (Desktop-first). Neue Regeln
> mobile-first; Breakpoint 768 bleibt gemeinsame Grenze (auch in `layout.js` gespiegelt).

## Basis-Breakpoints
- Mobile:  < 768px   (Basis, keine Media-Query)
- Tablet:  ≥ 768px   (`@media (min-width: 768px)`)
- Desktop: ≥ 1024px  (`@media (min-width: 1024px)`)

## Touch-Targets
Interaktive Elemente mind. 44×44px Trefferfläche.

## Einheiten
`rem` statt fixer `px` für Schrift/Spacing wo sinnvoll (Tokens: `--space-*`, `--fs-*`).
Feste px nur für Hairlines/Borders/Logos.

## Layout
- Keine horizontalen Overflows; Inhalte umbrechen/stapeln.
- Flex/Grid mobil 1-spaltig, ab Breakpoint mehrspaltig.

## Geltung
Verbindlich für jede künftige UI-Session und für diese (Bereich-3-Header).
