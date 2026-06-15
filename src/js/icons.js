// =============================================================================
// Icon-Helper — zentrale Quelle für alle SVG-Icons der UI
// -----------------------------------------------------------------------------
// Ersetzt die früheren Emojis durch projekt-eigene SVG-Icons
// (siehe docs/design/components/icons.html).
//
// Zwei Kategorien:
//   • "farbig"   – illustrative Gamification-Icons mit Farbverläufen
//                  (Trophäe, Energy, Stern, Streak). Behalten ihre Farben.
//                  Die internen Gradient-IDs werden pro Aufruf eindeutig
//                  gemacht, damit mehrfaches Einbetten auf einer Seite nicht
//                  kollidiert.
//   • Linien-Icons – monochrom im Lucide/Heroicons-Stil. Nutzen
//                  `currentColor`, passen sich also der umgebenden
//                  Token-Farbe und dem Theme-Toggle automatisch an.
//
// Nutzung:
//   • In JS:     element.innerHTML = Icons.render('energy');
//   • In HTML:   <span data-icon="lock"></span>  (wird beim Laden ersetzt)
//                optional: data-icon-size="24", data-icon-label="Gesperrt"
// =============================================================================

const Icons = (() => {
  // Zähler für eindeutige Gradient-IDs (verhindert Kollisionen, wenn dasselbe
  // farbige Icon mehrmals auf einer Seite gerendert wird).
  let instanzZaehler = 0;

  // ---------------------------------------------------------------------------
  // Icon-Definitionen
  //   viewBox – Koordinatensystem des Icons
  //   farbig  – true = behält eigene Farben (kein currentColor)
  //   markup  – Funktion, die das innere SVG-Markup liefert; bekommt eine
  //             eindeutige uid für Gradient-IDs (nur bei farbigen Icons nötig)
  // ---------------------------------------------------------------------------
  const ICONS = {

    // ----- Farbige Gamification-Icons (mit Farbverläufen) --------------------

    // Trophäe (Gold) – ersetzt 🏆
    trophy: {
      viewBox: '0 0 96 96',
      farbig: true,
      markup: (u) =>
        `<defs>` +
        `<linearGradient id="trophyGold-${u}" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#FDE68A"/><stop offset="50%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/></linearGradient>` +
        `<linearGradient id="trophyShine-${u}" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FEFCE8" stop-opacity="0.9"/><stop offset="100%" stop-color="#F59E0B" stop-opacity="0"/></linearGradient>` +
        `</defs>` +
        `<ellipse cx="48" cy="88" rx="20" ry="4" fill="#F59E0B" opacity="0.3"/>` +
        `<rect x="36" y="76" width="24" height="6" rx="3" fill="url(#trophyGold-${u})"/>` +
        `<rect x="32" y="80" width="32" height="5" rx="2.5" fill="#D97706"/>` +
        `<rect x="43" y="68" width="10" height="10" fill="#F59E0B"/>` +
        `<path d="M28 20 Q24 52 38 64 Q43 68 48 68 Q53 68 58 64 Q72 52 68 20 Z" fill="url(#trophyGold-${u})"/>` +
        `<path d="M35 22 Q31 44 38 58 Q34 42 36 24 Z" fill="url(#trophyShine-${u})" opacity="0.8"/>` +
        `<path d="M28 28 Q16 30 17 42 Q18 50 28 50" fill="none" stroke="#F59E0B" stroke-width="5" stroke-linecap="round"/>` +
        `<path d="M28 28 Q16 30 17 42 Q18 50 28 50" fill="none" stroke="#FDE68A" stroke-width="2" stroke-linecap="round"/>` +
        `<path d="M68 28 Q80 30 79 42 Q78 50 68 50" fill="none" stroke="#F59E0B" stroke-width="5" stroke-linecap="round"/>` +
        `<path d="M68 28 Q80 30 79 42 Q78 50 68 50" fill="none" stroke="#FDE68A" stroke-width="2" stroke-linecap="round"/>` +
        `<polygon points="48,32 50,38 56,38 51,42 53,48 48,44 43,48 45,42 40,38 46,38" fill="#FFFBEB" opacity="0.95"/>` +
        `<ellipse cx="48" cy="20" rx="20" ry="4" fill="#FDE68A"/>` +
        `<ellipse cx="48" cy="20" rx="20" ry="4" fill="none" stroke="#D97706" stroke-width="1"/>`,
    },

    // Energy-Drink-Dose – ersetzt 🥤
    energy: {
      viewBox: '0 0 96 96',
      farbig: true,
      markup: (u) =>
        `<defs>` +
        `<linearGradient id="canGrad-${u}" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#164E63"/><stop offset="40%" stop-color="#0891B2"/><stop offset="100%" stop-color="#06B6D4"/></linearGradient>` +
        `<linearGradient id="canTop-${u}" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#CBD5E1"/><stop offset="100%" stop-color="#94A3B8"/></linearGradient>` +
        `<linearGradient id="canShine-${u}" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="white" stop-opacity="0.3"/><stop offset="100%" stop-color="white" stop-opacity="0"/></linearGradient>` +
        `</defs>` +
        `<ellipse cx="48" cy="90" rx="18" ry="4" fill="#06B6D4" opacity="0.25"/>` +
        `<rect x="30" y="22" width="36" height="62" rx="6" fill="url(#canGrad-${u})"/>` +
        `<rect x="30" y="22" width="14" height="62" rx="6" fill="url(#canShine-${u})"/>` +
        `<ellipse cx="48" cy="22" rx="18" ry="5" fill="url(#canTop-${u})"/>` +
        `<ellipse cx="48" cy="22" rx="18" ry="5" fill="none" stroke="#94A3B8" stroke-width="1"/>` +
        `<ellipse cx="48" cy="84" rx="18" ry="5" fill="#0E7490"/>` +
        `<ellipse cx="48" cy="20" rx="6" ry="2" fill="#E2E8F0" stroke="#94A3B8" stroke-width="1"/>` +
        `<rect x="45" y="16" width="6" height="6" rx="1" fill="#CBD5E1"/>` +
        `<rect x="30" y="38" width="36" height="20" fill="#0E7490" opacity="0.5"/>` +
        `<polygon points="52,36 44,48 49,48 46,60 56,46 51,46" fill="#FDE68A"/>` +
        `<polygon points="52,36 44,48 49,48 46,60 56,46 51,46" fill="none" stroke="#F59E0B" stroke-width="0.5"/>`,
    },

    // XP-Stern – ersetzt ⭐
    star: {
      viewBox: '0 0 28 28',
      farbig: true,
      markup: (u) =>
        `<defs><linearGradient id="xpGrad-${u}" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FDE68A"/><stop offset="100%" stop-color="#F59E0B"/></linearGradient></defs>` +
        `<polygon points="14,2 17,10 26,10 19,16 22,24 14,19 6,24 9,16 2,10 11,10" fill="url(#xpGrad-${u})"/>` +
        `<polygon points="14,6 16,11 21,11 17,14 19,19 14,16 9,19 11,14 7,11 12,11" fill="#FFFBEB" opacity="0.5"/>`,
    },

    // Streak-Flamme – ersetzt 🔥
    streak: {
      viewBox: '0 0 28 28',
      farbig: true,
      markup: (u) =>
        `<defs><linearGradient id="fireGrad-${u}" x1="0.5" y1="0" x2="0.5" y2="1"><stop offset="0%" stop-color="#FDE68A"/><stop offset="50%" stop-color="#F97316"/><stop offset="100%" stop-color="#DC2626"/></linearGradient></defs>` +
        `<path d="M14 3 Q16 8 20 10 Q22 5 20 2 Q26 7 25 14 Q24 20 19 23 Q20 19 17 17 Q17 21 14 24 Q11 21 11 17 Q8 19 9 23 Q4 20 3 14 Q2 7 8 2 Q6 5 8 10 Q12 8 14 3Z" fill="url(#fireGrad-${u})"/>` +
        `<circle cx="14" cy="17" r="3" fill="#FDE68A" opacity="0.6"/>`,
    },

    // ----- Monochrome Linien-Icons (currentColor, Lucide-Stil) ---------------

    // Häkchen – ersetzt ✅ / ✓
    check: {
      viewBox: '0 0 24 24',
      markup: () => `<polyline points="20 6 9 17 4 12"/>`,
    },

    // Kreuz – ersetzt ❌
    xMark: {
      viewBox: '0 0 24 24',
      markup: () => `<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>`,
    },

    // Aufgeschlagenes Buch – ersetzt 📚 / 📘
    book: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>` +
        `<path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>`,
    },

    // Balkendiagramm – ersetzt 📈
    chart: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<line x1="18" y1="20" x2="18" y2="10"/>` +
        `<line x1="12" y1="20" x2="12" y2="4"/>` +
        `<line x1="6" y1="20" x2="6" y2="14"/>`,
    },

    // Schloss – ersetzt 🔐 / 🔒
    lock: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>` +
        `<path d="M7 11V7a5 5 0 0 1 10 0v4"/>`,
    },

    // Zielscheibe – ersetzt 🎯
    target: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<circle cx="12" cy="12" r="10"/>` +
        `<circle cx="12" cy="12" r="6"/>` +
        `<circle cx="12" cy="12" r="2"/>`,
    },

    // Herz – ersetzt ❤️ (Leben/Health)
    heart: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>`,
    },

    // Geschenk – ersetzt 🎁 (Bonus)
    gift: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<polyline points="20 12 20 22 4 22 4 12"/>` +
        `<rect x="2" y="7" width="20" height="5"/>` +
        `<line x1="12" y1="22" x2="12" y2="7"/>` +
        `<path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>` +
        `<path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>`,
    },

    // Zahnrad – ersetzt ⚙️ (Einstellungen)
    settings: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>` +
        `<circle cx="12" cy="12" r="3"/>`,
    },

    // Medaille – ersetzt 🏅 (Achievements)
    medal: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<circle cx="12" cy="8" r="6"/>` +
        `<path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>`,
    },

    // Warndreieck – ersetzt ⚠️ / 🚧 (Hinweis, orange via --warning)
    warning: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>` +
        `<line x1="12" y1="9" x2="12" y2="13"/>` +
        `<line x1="12" y1="17" x2="12.01" y2="17"/>`,
    },

    // Aktivität (Pulslinie) – ersetzt 🗂 (Letzte Aktivität)
    activity: {
      viewBox: '0 0 24 24',
      markup: () => `<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>`,
    },

    // Leerer Posteingang – ersetzt 📭 (Leerzustand)
    empty: {
      viewBox: '0 0 24 24',
      markup: () =>
        `<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>` +
        `<path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>`,
    },
  };

  // ---------------------------------------------------------------------------
  // render(name, opts) → SVG-String
  //   opts.size      – Kantenlänge in px (Default: farbig 22, Linie 20)
  //   opts.className – zusätzliche CSS-Klasse(n)
  //   opts.title     – wenn gesetzt: Icon ist semantisch (role/aria-label),
  //                    sonst dekorativ (aria-hidden)
  // Unbekannter Name: Warnung + leerer String, damit die App nicht bricht.
  // ---------------------------------------------------------------------------
  function render(name, opts = {}) {
    const def = ICONS[name];
    if (!def) {
      console.warn('Unknown icon: ' + name);
      return '';
    }

    const uid = 'i' + (++instanzZaehler);
    const groesse = opts.size || (def.farbig ? 22 : 20);
    const klasse =
      'icon' +
      (def.farbig ? ' icon--farbig' : '') +
      (opts.className ? ' ' + opts.className : '');

    // Barrierefreiheit: dekorativ ausblenden, oder mit Label versehen
    const a11y = opts.title
      ? ` role="img" aria-label="${opts.title}"`
      : ` aria-hidden="true"`;

    // Linien-Icons folgen der Textfarbe (currentColor); farbige Icons nicht
    const malAttrs = def.farbig
      ? ''
      : ` fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;

    return (
      `<svg class="${klasse}" width="${groesse}" height="${groesse}" ` +
      `viewBox="${def.viewBox}"${malAttrs}${a11y} xmlns="http://www.w3.org/2000/svg">` +
      def.markup(uid) +
      `</svg>`
    );
  }

  // ---------------------------------------------------------------------------
  // hydrate(wurzel) – ersetzt alle <… data-icon="name"> Platzhalter im
  // statischen HTML durch das gerenderte SVG. Läuft automatisch beim Laden;
  // kann für nachgeladene Inhalte erneut aufgerufen werden.
  // ---------------------------------------------------------------------------
  function hydrate(wurzel = document) {
    const platzhalter = wurzel.querySelectorAll('[data-icon]');
    platzhalter.forEach((el) => {
      const name = el.getAttribute('data-icon');
      const opts = {};
      if (el.hasAttribute('data-icon-size')) {
        opts.size = parseInt(el.getAttribute('data-icon-size'), 10);
      }
      if (el.hasAttribute('data-icon-label')) {
        opts.title = el.getAttribute('data-icon-label');
      }
      el.innerHTML = render(name, opts);
    });
  }

  // Auto-Hydration: sobald das DOM bereit ist (oder sofort, falls schon geladen)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => hydrate());
  } else {
    hydrate();
  }

  return { render, hydrate };
})();
