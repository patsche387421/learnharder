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
      ep = richtig * 5;
      if      (score === 100) bonus = 50;
      else if (score > 90)    bonus = 35;
      else if (score > 70)    bonus = 20;
      else if (score > 50)    bonus = 10;
      ep += bonus;
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

  // Befüllt alle Topbar-Elemente: Energie, Level-Mitte (Balken + EP), Trophäen, Name, Logout.
  // Prüft die Session intern — funktioniert auch auf öffentlichen Seiten ohne requireLogin().
  // stats: optional; wenn übergeben wird kein zweiter getUserStats()-Aufruf gemacht.
  async function renderTopbar(stats) {
    const { data: sessionData } = await sb.auth.getSession();
    const user = sessionData.session?.user ?? null;

    // onclick statt addEventListener → kein Doppel-Listener bei Mehrfachaufruf
    const logoutBtn = document.getElementById('logout');
    if (logoutBtn) logoutBtn.onclick = () => Auth.logout();

    if (!user) {
      // Nicht eingeloggt (öffentliche Seiten): Stats ausblenden, Anmelden-Link zeigen
      const mitte = document.querySelector('.topbar-level-mitte');
      if (mitte) mitte.hidden = true;
      const energyEl = document.getElementById('topbar-energy');
      if (energyEl) energyEl.hidden = true;
      const trophiesEl = document.getElementById('topbar-trophies');
      if (trophiesEl) trophiesEl.hidden = true;
      const welcomeEl = document.getElementById('welcome-name');
      if (welcomeEl) welcomeEl.innerHTML = '<a href="/index.html" class="btn-ghost-sm">Anmelden</a>';
      return;
    }

    // Name setzen
    const welcomeEl = document.getElementById('welcome-name');
    if (welcomeEl) welcomeEl.textContent = user.email.split('@')[0];

    // Stats laden wenn nicht übergeben
    if (!stats) stats = await getUserStats();

    const energyEl = document.getElementById('topbar-energy');
    if (energyEl) energyEl.textContent = '🥤 ' + stats.energy;

    const trophiesCountEl = document.getElementById('topbar-trophies-count');
    if (trophiesCountEl) trophiesCountEl.textContent = stats.trophies;

    const levelZahl = document.getElementById('topbar-level-zahl');
    if (levelZahl) levelZahl.textContent = stats.level;

    const naechste = LEVEL_THRESHOLDS[stats.level] ?? null;
    const prozent  = naechste ? Math.min(stats.totalXp / naechste * 100, 100) : 100;

    const levelFill = document.getElementById('topbar-level-fill');
    if (levelFill) levelFill.style.width = prozent + '%';

    const levelEp = document.getElementById('topbar-level-ep');
    if (levelEp) levelEp.textContent = stats.totalXp + ' / ' + (naechste ?? '–') + ' EP';
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
