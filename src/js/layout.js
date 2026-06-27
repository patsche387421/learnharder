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

  // Leitet 1–2 Initialen aus einem Anzeigenamen oder einer E-Mail ab.
  // "patsche.kroeger@…" → "PK", "max@…" → "MA".
  function topbarInitialen(roh) {
    const basis = (roh || '').split('@')[0];
    const teile = basis.split(/[.\s_-]+/).filter(Boolean);
    const buchstaben = teile.length >= 2
      ? teile[0][0] + teile[1][0]
      : basis.slice(0, 2);
    return buchstaben.toUpperCase();
  }

  // Bestimmt anhand des Pfads den aktiven Nav-Eintrag (Active-State).
  // Fächerübersicht, alle Fach-Seiten und der Themen-Inhalt zählen zu "Fächer".
  function aktiverNav(pfad) {
    const datei = pfad.split('/').pop() || '';
    if (datei === 'dashboard.html') return 'dashboard';
    if (datei === 'tagesquiz.html') return 'tagesquiz';
    const faecherSeiten = ['faecher.html', 'fach.html', 'pos.html', 'dbi.html',
      'nsvs.html', 'medt.html', 'syp.html', 'wir.html', 'tinf.html'];
    if (faecherSeiten.includes(datei)) return 'faecher';
    return '';
  }

  // Baut Logo + Nav-Links – das immer sichtbare Grundgerüst (auch ausgeloggt).
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
      '<a class="topbar-logo" href="/dashboard.html" aria-label="learnharder – Dashboard">' +
        '<img class="topbar-logo-mark" src="/assets/logo.svg" width="36" height="36" alt="" />' +
        '<span class="topbar-wordmark">learn<span class="topbar-wordmark-accent">harder</span></span>' +
      '</a>' +
      '<nav class="topbar-nav" id="topbar-nav">' +
        navLink('dashboard', '/dashboard.html', 'Dashboard') +
        navLink('faecher',   '/faecher.html',   'Fächer') +
        navLink('tagesquiz', '/tagesquiz.html', 'Herausforderung') +
        navPlatzhalter('Team') +
        navPlatzhalter('Rangliste') +
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

  // Erzeugt die komplette Topbar in <header id="topbar"></header>:
  //   Logo · Nav · Stats (Energie/Trophäen/XP-Pills + Level-Badge + Avatar) · XP-Bar.
  // Prüft die Session intern — funktioniert auch auf öffentlichen Seiten ohne requireLogin().
  // stats: optional; wenn übergeben wird kein zweiter Level.getUserStats()-Aufruf gemacht.
  async function renderTopbar(stats) {
    const bar = document.getElementById('topbar');
    if (!bar) return;

    const { data: sessionData } = await sb.auth.getSession();
    const user = sessionData.session?.user ?? null;

    let markup = topbarGrundgeruest();

    if (!user) {
      // Nicht eingeloggt (öffentliche Seiten): keine Stats, nur Anmelden-Link
      markup +=
        '<div class="topbar-stats">' +
          '<a href="/index.html" class="btn-ghost-sm">Anmelden</a>' +
        '</div>';
      bar.innerHTML = markup;
      verdrahteHamburger(bar);
      return;
    }

    if (!stats) stats = await Level.getUserStats();

    const f         = Level.berechneFortschritt(stats.totalXp);
    const initialen = topbarInitialen(user.email);

    markup +=
      '<div class="topbar-stats">' +
        '<span class="topbar-pill topbar-pill--energy" title="Energie">' + Icons.render('energy', { size: 18 }) + '<span>' + stats.energy + '</span></span>' +
        '<span class="topbar-pill topbar-pill--trophies" title="Trophäen">' + Icons.render('trophy', { size: 18 }) + '<span>' + stats.trophies + '</span></span>' +
        '<span class="topbar-pill topbar-pill--xp" title="Erfahrungspunkte">' + Icons.render('star', { size: 18 }) + '<span>' + stats.totalXp + ' XP</span></span>' +
        '<span class="topbar-level-badge" title="Level ' + f.level + '">' + f.level + '</span>' +
        '<a class="topbar-avatar" href="/profil.html" title="Profil">' + initialen + '</a>' +
      '</div>' +
      '<div class="topbar-progress" title="' + f.epText + '">' +
        '<div class="topbar-progress-fill" style="width: ' + f.prozent + '%"></div>' +
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
