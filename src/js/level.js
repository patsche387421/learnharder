// Level-System-Modul: Energie, Trophäen, EP, Level und Tages-Quiz-Verwaltung.
// Nutzt die Tabellen user_stats, subject_xp und daily_quiz_log (Migration 003).
const Level = (() => {
  const sb = SupabaseClient.client;

  // EP-Schwellen pro Level-Stufe: Index = Level-Nummer (1–10)
  // LEVEL_THRESHOLDS[n] = Gesamt-EP, die zum Erreichen von Level n benötigt werden.
  // Level 10 ist Maximum → LEVEL_THRESHOLDS[10] = undefined (zeigt "–" im UI).
  const LEVEL_THRESHOLDS = [0, 100, 250, 500, 900, 1400, 2000, 2700, 3500, 4500];

  // Berechnet Level aus Gesamt-EP (Level 1–10).
  function berechneLevel(totalXp) {
    let level = 1;
    for (let i = 1; i < LEVEL_THRESHOLDS.length; i++) {
      if (totalXp >= LEVEL_THRESHOLDS[i]) level = i + 1;
      else break;
    }
    return Math.min(level, 10);
  }

  // Gibt globale Gamification-Werte des eingeloggten Users zurück.
  // Liefert Standardwerte wenn kein Datenbankeintrag vorhanden.
  async function getUserStats() {
    const user = Auth.currentUser();
    if (!user) return { energy: 5, level: 1, trophies: 0, totalXp: 0 };

    const { data } = await sb
      .from('user_stats')
      .select('total_xp, level, energy, trophies')
      .eq('user_id', user.id)
      .maybeSingle();

    if (!data) return { energy: 5, level: 1, trophies: 0, totalXp: 0 };
    return {
      energy:   data.energy   ?? 5,
      level:    data.level    ?? 1,
      trophies: data.trophies ?? 0,
      totalXp:  data.total_xp ?? 0
    };
  }

  // Verbraucht 1 Energydrink. Gibt true zurück wenn erfolgreich, false bei 0 Energie.
  async function verbrauchEnergie() {
    const user = Auth.currentUser();
    if (!user) return false;

    const stats = await getUserStats();
    if (stats.energy <= 0) return false;

    const { error } = await sb
      .from('user_stats')
      .update({ energy: stats.energy - 1, updated_at: new Date().toISOString() })
      .eq('user_id', user.id);

    return !error;
  }

  // Prüft ob der User das Tages-Quiz heute bereits gespielt hat (UTC-Tag).
  async function hatHeuteTagesQuizGespielt() {
    const user = Auth.currentUser();
    if (!user) return false;

    const heute = new Date().toISOString().split('T')[0];
    const { data } = await sb
      .from('daily_quiz_log')
      .select('id')
      .eq('user_id', user.id)
      .gte('played_at', heute + 'T00:00:00+00:00')
      .limit(1)
      .maybeSingle();

    return !!data;
  }

  // Bucht das Quiz-Ergebnis: EP, Trophäen, Level-Update.
  // lebenProzent: verbleibende Leben nach Quiz (0 = keine EP/Bonus, aber Trophäen)
  async function buecheQuizErgebnis({ richtig, gesamt, fachId, istTagesQuiz = false, lebenProzent }) {
    console.log('[Level] buecheQuizErgebnis:', { richtig, gesamt, fachId, istTagesQuiz, lebenProzent });
    const user = Auth.currentUser();
    if (!user) return { ep: 0, trophien: 0, bonus: 0, levelUp: false };

    const score    = gesamt > 0 ? Math.round((richtig / gesamt) * 100) : 0;
    const trophien = richtig;
    const hatLeben = lebenProzent > 0;

    let ep    = 0;
    let bonus = 0;

    if (hatLeben) {
      ep = istTagesQuiz ? richtig * 5 : richtig * 1;
      if (istTagesQuiz) {
        if      (score === 100) bonus = 50;
        else if (score > 90)    bonus = 35;
        else if (score > 70)    bonus = 20;
        else if (score > 50)    bonus = 10;
        ep += bonus;
      }
    }

    // user_stats aktualisieren (EP, Level, Trophäen)
    const { data: aktStats } = await sb
      .from('user_stats')
      .select('total_xp, level, trophies')
      .eq('user_id', user.id)
      .maybeSingle();

    const neueXp      = (aktStats?.total_xp  ?? 0) + ep;
    const neueTrophies = (aktStats?.trophies  ?? 0) + trophien;
    const neuesLevel  = berechneLevel(neueXp);
    const levelUp     = neuesLevel > (aktStats?.level ?? 1);

    const { error: statsErr } = await sb.from('user_stats').upsert({
      user_id:    user.id,
      total_xp:   neueXp,
      level:      neuesLevel,
      trophies:   neueTrophies,
      updated_at: new Date().toISOString()
    }, { onConflict: 'user_id' });
    if (statsErr) console.error('[Level] user_stats upsert Fehler:', statsErr);

    // subject_xp für das Fach aktualisieren (nur wenn Fach bekannt und EP verdient)
    if (fachId && ep > 0) {
      const { data: fachXp } = await sb
        .from('subject_xp')
        .select('xp, level, correct_answers')
        .eq('user_id', user.id)
        .eq('subject_id', fachId)
        .maybeSingle();

      const neueFachXp     = (fachXp?.xp              ?? 0) + ep;
      const neuesFachLevel = berechneLevel(neueFachXp);
      const neueCorrectAns = (fachXp?.correct_answers  ?? 0) + richtig;

      const { error: fachErr } = await sb.from('subject_xp').upsert({
        user_id:         user.id,
        subject_id:      fachId,
        xp:              neueFachXp,
        level:           neuesFachLevel,
        correct_answers: neueCorrectAns
      }, { onConflict: 'user_id,subject_id' });
      if (fachErr) console.error('[Level] subject_xp upsert Fehler:', fachErr);
    }

    // Tages-Quiz-Ergebnis in daily_quiz_log festhalten
    if (istTagesQuiz) {
      const { error: logErr } = await sb.from('daily_quiz_log').insert({
        user_id:         user.id,
        score,
        xp_earned:       ep,
        trophies_earned: trophien,
        success:         hatLeben
      });
      if (logErr) console.error('[Level] daily_quiz_log insert Fehler:', logErr);
    }

    return { ep, trophien, bonus, levelUp };
  }

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
    return (
      '<a class="topbar-logo" href="/dashboard.html" aria-label="learnharder – Dashboard">' +
        '<img class="topbar-logo-mark" src="/assets/logo.svg" width="36" height="36" alt="" />' +
        '<span class="topbar-wordmark">learn<span class="topbar-wordmark-accent">harder</span></span>' +
      '</a>' +
      '<nav class="topbar-nav">' +
        navLink('dashboard', '/dashboard.html', 'Dashboard') +
        navLink('faecher',   '/faecher.html',   'Fächer') +
        navLink('tagesquiz', '/tagesquiz.html', 'Tagesquiz') +
      '</nav>'
    );
  }

  // Erzeugt die komplette Topbar in <header id="topbar"></header>:
  //   Logo · Nav · Stats (Energie/Trophäen/XP-Pills + Level-Badge + Avatar) · XP-Bar.
  // Prüft die Session intern — funktioniert auch auf öffentlichen Seiten ohne requireLogin().
  // stats: optional; wenn übergeben wird kein zweiter getUserStats()-Aufruf gemacht.
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
      return;
    }

    if (!stats) stats = await getUserStats();

    const naechste  = LEVEL_THRESHOLDS[stats.level] ?? null;
    const prozent   = naechste ? Math.min(stats.totalXp / naechste * 100, 100) : 100;
    const epText    = stats.totalXp + ' / ' + (naechste ?? '–') + ' EP';
    const initialen = topbarInitialen(user.email);

    markup +=
      '<div class="topbar-stats">' +
        '<span class="topbar-pill topbar-pill--energy" title="Energie">🥤 <span>' + stats.energy + '</span></span>' +
        '<span class="topbar-pill topbar-pill--trophies" title="Trophäen">🏆 <span>' + stats.trophies + '</span></span>' +
        '<span class="topbar-pill topbar-pill--xp" title="Erfahrungspunkte">⭐ <span>' + stats.totalXp + ' XP</span></span>' +
        '<span class="topbar-level-badge" title="Level ' + stats.level + '">' + stats.level + '</span>' +
        '<a class="topbar-avatar" href="/profil.html" title="Profil">' + initialen + '</a>' +
      '</div>' +
      '<div class="topbar-progress" title="' + epText + '">' +
        '<div class="topbar-progress-fill" style="width: ' + prozent + '%"></div>' +
      '</div>';

    bar.innerHTML = markup;
  }

  // Tauscht Trophäen gegen Energie (50 Trophäen = 1 Energydrink).
  async function tauscheTrophäen(anzahlEnergie) {
    const user = Auth.currentUser();
    if (!user) return { erfolg: false, fehler: 'Nicht eingeloggt' };

    const kostet = anzahlEnergie * 50;
    const stats  = await getUserStats();

    if (stats.trophies < kostet) {
      return { erfolg: false, fehler: `Nicht genug Trophäen (${kostet} 🏆 benötigt, du hast ${stats.trophies})` };
    }

    const { error } = await sb.from('user_stats').update({
      trophies:   stats.trophies - kostet,
      energy:     stats.energy   + anzahlEnergie,
      updated_at: new Date().toISOString()
    }).eq('user_id', user.id);

    if (error) return { erfolg: false, fehler: error.message };
    return { erfolg: true };
  }

  return {
    LEVEL_THRESHOLDS,
    getUserStats,
    verbrauchEnergie,
    hatHeuteTagesQuizGespielt,
    buecheQuizErgebnis,
    tauscheTrophäen,
    renderTopbar
  };
})();
