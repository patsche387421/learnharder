// =============================================================================
// Layout-Modul — globale Seiten-Chrome: Topbar (Header) und Footer.
// -----------------------------------------------------------------------------
// Eine einzige Quelle für Header UND Footer, per JS auf jeder Seite injiziert
// (kein Build-Step). renderTopbar + Helfer wurden aus level.js hierher
// verschoben (Logik unverändert). Daten-/Logik-Funktionen bleiben in level.js
// und werden hier via Level.* konsumiert (Level.getUserStats /
// Level.berechneFortschritt).
//
// Nutzung pro Seite (Lade-Reihenfolge: … → level.js → layout.js):
//   <header class="topbar" id="topbar"></header>           → Layout.renderTopbar(stats)
//   <footer class="site-footer" id="site-footer"></footer> → Layout.renderFooter()
// =============================================================================

const Layout = (() => {
  const sb = SupabaseClient.client;

  // Merkt, ob die globalen Hamburger-Listener (document/keydown/resize) schon
  // verdrahtet sind. renderTopbar kann mehrfach laufen → nur einmal anhängen.
  let globaleHamburgerListenerAktiv = false;

  // Zähler für eindeutige Gradient-IDs pro Level-Badge (renderTopbar kann mehrfach
  // laufen → sonst kollidieren gleichnamige <linearGradient>/<radialGradient>-IDs).
  let badgeZaehler = 0;

  // Bestimmt anhand des Pfads den aktiven Nav-Eintrag (Active-State).
  // Fächerübersicht, alle Fach-Seiten und der Themen-Inhalt zählen zu "Fächer".
  function aktiverNav(pfad) {
    const datei = pfad.split('/').pop() || '';
    if (datei === 'dashboard.html') return 'dashboard';
    if (datei === 'tagesquiz.html') return 'tagesquiz';
    if (datei === 'profil.html') return 'profil';
    const faecherSeiten = ['faecher.html', 'fach.html', 'pos.html', 'dbi.html',
      'nsvs.html', 'medt.html', 'syp.html', 'wir.html', 'tinf.html'];
    if (faecherSeiten.includes(datei)) return 'faecher';
    return '';
  }

  // Logo + Wortmarke — immer sichtbar, auch abgemeldet. Klickbar → Dashboard.
  function topbarLogo() {
    return (
      '<a class="topbar-logo" href="/dashboard.html" aria-label="learnharder – Dashboard">' +
        '<img class="topbar-logo-mark" src="/assets/logo.svg" width="36" height="36" alt="" />' +
        '<span class="topbar-wordmark">learn<span class="topbar-wordmark-accent">harder</span></span>' +
      '</a>'
    );
  }

  // Baut Hamburger + Logo + Nav-Links – das Grundgerüst für angemeldete Nutzer.
  function topbarGrundgeruest() {
    const aktiv = aktiverNav(location.pathname);
    const navLink = (key, href, label) =>
      '<a class="topbar-nav-link' + (aktiv === key ? ' active' : '') + '" href="' + href + '">' + label + '</a>';
    // Deaktivierte Platzhalter für noch nicht gebaute Bereiche (soziale Ebene, siehe teams.html-Mockup)
    const navPlatzhalter = (label) =>
      '<span class="topbar-nav-link topbar-nav-link--disabled" aria-disabled="true" title="Kommt bald">' + label + '</span>';
    return (
      '<button class="topbar-hamburger" type="button" aria-expanded="false" aria-controls="topbar-nav" aria-label="Menü öffnen">' +
        '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
          '<line x1="4" y1="7" x2="20" y2="7" />' +
          '<line x1="4" y1="12" x2="20" y2="12" />' +
          '<line x1="4" y1="17" x2="20" y2="17" />' +
        '</svg>' +
      '</button>' +
      topbarLogo() +
      '<nav class="topbar-nav" id="topbar-nav">' +
        navLink('dashboard', '/dashboard.html', 'Dashboard') +
        navLink('faecher',   '/faecher.html',   'Fächer') +
        navLink('tagesquiz', '/tagesquiz.html', 'Herausforderung') +
        navPlatzhalter('Team') +
        navPlatzhalter('Rangliste') +
        navLink('profil',    '/profil.html',    'Profil') +
      '</nav>'
    );
  }

  // Schließt das mobile Nav-Panel und setzt den Hamburger-Button zurück.
  function schliesseNav(bar) {
    if (!bar.classList.contains('topbar--nav-open')) return;
    bar.classList.remove('topbar--nav-open');
    const btn = bar.querySelector('.topbar-hamburger');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Menü öffnen');
    }
  }

  // Verdrahtet den Hamburger-Button: Toggle des Panels + Schließen bei
  // Link-Klick, Klick außerhalb, Escape und Wechsel zurück auf Desktop-Breite.
  function verdrahteHamburger(bar) {
    const btn = bar.querySelector('.topbar-hamburger');
    const nav = bar.querySelector('#topbar-nav');
    if (!btn || !nav) return;

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const offen = bar.classList.toggle('topbar--nav-open');
      btn.setAttribute('aria-expanded', offen ? 'true' : 'false');
      btn.setAttribute('aria-label', offen ? 'Menü schließen' : 'Menü öffnen');
    });

    nav.addEventListener('click', function (e) {
      if (e.target.closest('a')) schliesseNav(bar);
    });

    // Globale Listener nur EINMAL verdrahten (renderTopbar kann mehrfach laufen).
    // Bar wird pro Event frisch per ID geholt -> überlebt Re-Renders ohne Stacking.
    if (globaleHamburgerListenerAktiv) return;
    globaleHamburgerListenerAktiv = true;

    document.addEventListener('click', function (e) {
      const b = document.getElementById('topbar');
      if (b && !b.contains(e.target)) schliesseNav(b);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        const b = document.getElementById('topbar');
        if (b) schliesseNav(b);
      }
    });
    // Breakpoint 768 gespiegelt zur Media-Query in style.css (bei Änderung beide anpassen)
    window.addEventListener('resize', function () {
      if (window.innerWidth > 768) {
        const b = document.getElementById('topbar');
        if (b) schliesseNav(b);
      }
    });
  }

  // Baut den Level-Badge als eigenständiges Inline-SVG (bewusst NICHT über
  // icons.js: der Inhalt ist dynamisch – Level-Zahl, Tier-Gradient, Facetten und
  // optionaler Prestige-Kreis). Hexagon in Tier-Farbe mit zentrierter Level-Zahl;
  // ab Prestige 1 zusätzlich ein oranger Kreis mit der Prestige-Zahl unten.
  function renderLevelBadge(level, tier, prestige) {
    const u = 'b' + (++badgeZaehler);
    const hatPrestige = prestige > 0;
    const fs = level >= 100 ? (hatPrestige ? 22 : 26) : (hatPrestige ? 34 : 38);
    const ty = hatPrestige ? '53' : '60';

    const rimPath  = hatPrestige
      ? 'M48 2 L82 21 L82 65 L48 84 L14 65 L14 21 Z'
      : 'M48 4 L84 24 L84 72 L48 92 L12 72 L12 24 Z';
    const facePath = hatPrestige
      ? 'M48 8 L77 24 L77 62 L48 78 L19 62 L19 24 Z'
      : 'M48 10 L79 27 L79 69 L48 86 L17 69 L17 27 Z';
    const hiPath   = hatPrestige
      ? 'M48 8 L19 24 L19 62 L23 59 L23 27 L48 13 Z'
      : 'M48 10 L17 27 L17 69 L21 66 L21 30 L48 15 Z';
    const shPath   = hatPrestige
      ? 'M48 8 L77 24 L72 27 L48 14 Z'
      : 'M48 10 L79 27 L74 30 L48 16 Z';

    let defs, halo = '', hiColor, shColor, hiOp, shOp, textAttr;

    if (tier === 'bronze') {
      defs =
        `<linearGradient id="rim-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#F5C77E"/><stop offset="100%" stop-color="#7A3B10"/>` +
        `</linearGradient>` +
        `<linearGradient id="hex-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#E8A857"/><stop offset="50%" stop-color="#CD7F32"/>` +
          `<stop offset="100%" stop-color="#8B4513"/>` +
        `</linearGradient>`;
      hiColor = '#FBDBA0'; shColor = '#FBDBA0'; hiOp = '0.5'; shOp = '0.35';
      textAttr = `fill="#FFFFFF" stroke="#7A3B10" stroke-width="1" paint-order="stroke"`;

    } else if (tier === 'silber') {
      defs =
        `<linearGradient id="rim-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#FFFFFF"/><stop offset="100%" stop-color="#6B6B6B"/>` +
        `</linearGradient>` +
        `<linearGradient id="hex-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#F0F0F0"/><stop offset="50%" stop-color="#C0C0C0"/>` +
          `<stop offset="100%" stop-color="#808080"/>` +
        `</linearGradient>`;
      hiColor = '#FFFFFF'; shColor = '#FFFFFF'; hiOp = '0.55'; shOp = '0.4';
      textAttr = `fill="#1a1a1a"`;

    } else if (tier === 'gold') {
      defs =
        `<linearGradient id="rim-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#FFF3B0"/><stop offset="100%" stop-color="#A66A00"/>` +
        `</linearGradient>` +
        `<linearGradient id="hex-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#FFE566"/><stop offset="50%" stop-color="#FFD700"/>` +
          `<stop offset="100%" stop-color="#CC8800"/>` +
        `</linearGradient>` +
        `<radialGradient id="glow-${u}" cx="48" cy="48" r="46" gradientUnits="userSpaceOnUse">` +
          `<stop offset="0%" stop-color="#FFD700" stop-opacity="0.55"/>` +
          `<stop offset="55%" stop-color="#FFB300" stop-opacity="0.22"/>` +
          `<stop offset="100%" stop-color="#FFB300" stop-opacity="0"/>` +
        `</radialGradient>`;
      halo = `<circle cx="48" cy="48" r="46" fill="url(#glow-${u})"/>`;
      hiColor = '#FFF3B0'; shColor = '#FFF9D6'; hiOp = '0.6'; shOp = '0.45';
      textAttr = `fill="#3D1F00"`;

    } else {
      defs =
        `<linearGradient id="rim-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#DDF8FF"/><stop offset="100%" stop-color="#5A1E93"/>` +
        `</linearGradient>` +
        `<linearGradient id="hex-${u}" x1="0" y1="0" x2="1" y2="1">` +
          `<stop offset="0%" stop-color="#A8EDFF"/><stop offset="50%" stop-color="#00BFFF"/>` +
          `<stop offset="100%" stop-color="#7B2FBE"/>` +
        `</linearGradient>` +
        `<radialGradient id="glow-${u}" cx="48" cy="48" r="46" gradientUnits="userSpaceOnUse">` +
          `<stop offset="0%" stop-color="#00BFFF" stop-opacity="0.55"/>` +
          `<stop offset="50%" stop-color="#7B2FBE" stop-opacity="0.3"/>` +
          `<stop offset="100%" stop-color="#7B2FBE" stop-opacity="0"/>` +
        `</radialGradient>`;
      halo = `<circle cx="48" cy="48" r="46" fill="url(#glow-${u})"/>`;
      hiColor = '#DDF8FF'; shColor = '#FFFFFF'; hiOp = '0.55'; shOp = '0.4';
      textAttr = `fill="#FFFFFF" stroke="#5A1E93" stroke-width="1" paint-order="stroke"`;
    }

    const prestigeBadge = hatPrestige
      ? `<circle cx="48" cy="80" r="10" fill="#EA580C" stroke="#FFFFFF" stroke-width="2"/>` +
        `<text x="48" y="84.5" font-family="'Space Grotesk',Arial,sans-serif"` +
        ` font-weight="bold" font-size="12" fill="#FFFFFF" text-anchor="middle">${prestige}</text>`
      : '';

    return (
      `<svg class="topbar-level-badge" width="36" height="36" viewBox="0 0 96 96"` +
      ` xmlns="http://www.w3.org/2000/svg">` +
      `<defs>${defs}</defs>` +
      halo +
      `<path d="${rimPath}" fill="url(#rim-${u})"/>` +
      `<path d="${facePath}" fill="url(#hex-${u})"/>` +
      `<path d="${hiPath}" fill="${hiColor}" opacity="${hiOp}"/>` +
      `<path d="${shPath}" fill="${shColor}" opacity="${shOp}"/>` +
      `<text x="48" y="${ty}" font-family="'Space Grotesk',Arial,sans-serif"` +
      ` font-weight="bold" font-size="${fs}" ${textAttr} text-anchor="middle">${level}</text>` +
      prestigeBadge +
      `</svg>`
    );
  }

  // Erzeugt die komplette Topbar in <header id="topbar"></header>:
  //   Logo · Nav · Stats (Energie/Trophäen-Pills + Level-Badge) · EP-Leiste mit Zahl.
  // Prüft die Session intern — funktioniert auch auf öffentlichen Seiten ohne requireLogin().
  // Abgemeldet: NUR Logo + Branding + Anmelden; angemeldet: voller Header.
  // stats: optional; wenn übergeben wird kein zweiter Level.getUserStats()-Aufruf gemacht.
  async function renderTopbar(stats) {
    const bar = document.getElementById('topbar');
    if (!bar) return;

    const { data: sessionData } = await sb.auth.getSession();
    const user = sessionData.session?.user ?? null;

    if (!user) {
      // Abgemeldet (öffentliche Seiten): NUR Logo + Branding + Anmelden-Link.
      // Kein Hamburger, keine Nav, keine Stats/Leiste → nichts zu verdrahten.
      bar.innerHTML =
        topbarLogo() +
        '<div class="topbar-stats">' +
          '<a href="/index.html" class="btn btn--ghost-sm">Anmelden</a>' +
        '</div>';
      return;
    }

    let markup = topbarGrundgeruest();

    if (!stats) stats = await Level.getUserStats();

    const f = Level.berechneFortschritt(stats.totalXp, stats.prestige);

    markup +=
      '<div class="topbar-stats">' +
        '<span class="topbar-pill topbar-pill--energy" title="Energie">' + Icons.render('energy', { size: 18 }) + '<span>' + stats.energy + '</span></span>' +
        '<span class="topbar-pill topbar-pill--trophies" title="Trophäen">' + Icons.render('trophy', { size: 18 }) + '<span>' + stats.trophies + '</span></span>' +
        renderLevelBadge(f.level, f.tier, f.prestige) +
      '</div>' +
      '<div class="topbar-progress" title="' + f.epText + '">' +
        '<div class="topbar-progress-fill" style="width: ' + f.prozent + '%"></div>' +
        '<span class="topbar-progress-text">' + f.epText + '</span>' +
      '</div>';

    bar.innerHTML = markup;
    verdrahteHamburger(bar);
  }

  // Rendert den globalen Footer in <footer id="site-footer"></footer>.
  // Eine Quelle für alle Seiten — bei Änderung nur hier anpassen.
  function renderFooter() {
    const fuss = document.getElementById('site-footer');
    if (!fuss) return;
    fuss.innerHTML =
      '<div class="footer-inner">' +
        '<span>© LearnHarder 2026</span>' +
        '<nav>' +
          '<a href="/impressum.html">Impressum</a>' +
          '<a href="/datenschutz.html">Datenschutz</a>' +
        '</nav>' +
      '</div>';
  }

  // Auto-Run: Footer injizieren, sobald das DOM bereit ist (oder sofort, falls
  // schon geladen) — gleiches Muster wie die Auto-Hydration in icons.js.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => renderFooter());
  } else {
    renderFooter();
  }

  return { renderTopbar, renderFooter };
})();
