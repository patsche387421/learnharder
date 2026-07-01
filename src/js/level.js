// Level-System-Modul: Energie, Trophäen, EP, Level und Tages-Quiz-Verwaltung.
// Nutzt die Tabellen user_stats, subject_xp und daily_quiz_log (Migration 003).
//
// ══ DB-MIGRATION S3b (manuell in Supabase ausführen, vor Prestige-Feature) ══════
// ALTER TABLE user_stats ADD COLUMN IF NOT EXISTS prestige INTEGER NOT NULL DEFAULT 0;
// ════════════════════════════════════════════════════════════════════════════════
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

  // Reine SSOT-Funktion: Fortschritt im AKTUELLEN Level-Band aus Gesamt-EP.
  // Nimmt nur EP entgegen → quelle-agnostisch (User-, Fach-, später Team-/Saison-EP).
  // prozent ist auf 0–100 gekappt; bei Max-Level (10) immer 100 %, kein nächstes Band.
  function berechneFortschritt(gesamtEp) {
    const level       = berechneLevel(gesamtEp);
    const istMax      = level === 10;
    const untere      = LEVEL_THRESHOLDS[level - 1] ?? 0;
    const obere       = LEVEL_THRESHOLDS[level];
    const epImBand    = gesamtEp - untere;
    const bandGroesse = istMax ? 0 : obere - untere;
    const prozent     = istMax ? 100 : Math.min((gesamtEp - untere) / (obere - untere) * 100, 100);
    const naechsteSchwelle = istMax ? null : obere;
    const epText      = istMax ? `${gesamtEp} EP (Max)` : `${epImBand} / ${bandGroesse} EP`;
    return { level, prozent, epImBand, bandGroesse, naechsteSchwelle, istMax, epText };
  }

  // Füllt Energie automatisch auf: +1 je vergangenem UTC-Kalendertag seit
  // energy_last_reset, gedeckelt bei 5. Reduziert NIE (Trophäen-Tausch kann
  // Energie > 5 erzeugen). Schreibt höchstens einmal pro UTC-Tag und User.
  // Gibt die effektive Energie zurück. SSOT: einzige Stelle, die Energie auflädt.
  async function rechargeEnergie(user, data) {
    const aktuelleEnergie = data.energy ?? 5;
    if (!data.energy_last_reset) return aktuelleEnergie;

    const heuteStr  = new Date().toISOString().split('T')[0];               // UTC-Tag
    const resetStr  = new Date(data.energy_last_reset).toISOString().split('T')[0];
    const tageOffen = Math.floor((Date.parse(heuteStr) - Date.parse(resetStr)) / 86_400_000);
    if (tageOffen < 1) return aktuelleEnergie;

    const neueEnergie = aktuelleEnergie < 5
      ? Math.min(5, aktuelleEnergie + tageOffen)
      : aktuelleEnergie;

    const { error } = await sb
      .from('user_stats')
      .update({
        energy:            neueEnergie,
        energy_last_reset: heuteStr + 'T00:00:00.000Z',
        updated_at:        new Date().toISOString()
      })
      .eq('user_id', user.id);

    if (error) {
      console.error('[Level] Energie-Recharge Fehler:', error);
      return aktuelleEnergie;
    }
    return neueEnergie;
  }

  // Gibt globale Gamification-Werte des eingeloggten Users zurück.
  // Liefert Standardwerte wenn kein Datenbankeintrag vorhanden.
  async function getUserStats() {
    const user = Auth.currentUser();
    if (!user) return { energy: 5, level: 1, trophies: 0, totalXp: 0 };

    const { data } = await sb
      .from('user_stats')
      .select('total_xp, level, energy, trophies, energy_last_reset')
      .eq('user_id', user.id)
      .maybeSingle();

    if (!data) return { energy: 5, level: 1, trophies: 0, totalXp: 0 };
    return {
      energy:   await rechargeEnergie(user, data),
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

  // Startet einen Tagesquiz-Versuch: verbraucht 1 Energie und legt sofort die
  // Sperrzeile in daily_quiz_log an (success=false, score=null). Dadurch greift
  // die Tagessperre auch bei Abbruch mitten im Quiz. Gibt { ok, logId } zurück;
  // logId dient zum Nachtragen des Ergebnisses in vergibBelohnungen.
  async function starteTagesQuiz() {
    const user = Auth.currentUser();
    if (!user) return { ok: false, logId: null };

    const verbraucht = await verbrauchEnergie();
    if (!verbraucht) return { ok: false, logId: null };

    const { data, error } = await sb
      .from('daily_quiz_log')
      .insert({
        user_id:         user.id,
        score:           null,
        xp_earned:       0,
        trophies_earned: 0,
        success:         false
      })
      .select('id')
      .single();

    if (error) {
      // Energie ist bereits verbraucht → Quiz trotzdem starten (best effort),
      // aber ohne Sperrzeile. logId fehlt → vergibBelohnungen fällt auf Insert zurück.
      console.error('[Level] daily_quiz_log Startzeile Fehler:', error);
      return { ok: true, logId: null };
    }
    return { ok: true, logId: data.id };
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

  // Vergibt die Belohnungen eines Quiz: EP, Trophäen, Level-Update.
  // lebenProzent: verbleibende Leben nach Quiz (0 = keine EP/Bonus, aber Trophäen)
  async function vergibBelohnungen({ richtig, gesamt, fachId, istTagesQuiz = false, lebenProzent, logId = null }) {
    console.log('[Level] vergibBelohnungen:', { richtig, gesamt, fachId, istTagesQuiz, lebenProzent, logId });
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

    // Tages-Quiz-Ergebnis in daily_quiz_log festhalten. Die Sperrzeile wurde
    // bereits beim Start angelegt (starteTagesQuiz) → per logId aktualisieren.
    // Fallback-Insert nur, falls keine Startzeile existiert (logId == null).
    if (istTagesQuiz) {
      if (logId) {
        const { error: logErr } = await sb.from('daily_quiz_log')
          .update({ score, xp_earned: ep, trophies_earned: trophien, success: hatLeben })
          .eq('id', logId);
        if (logErr) console.error('[Level] daily_quiz_log update Fehler:', logErr);
      } else {
        const { error: logErr } = await sb.from('daily_quiz_log').insert({
          user_id:         user.id,
          score,
          xp_earned:       ep,
          trophies_earned: trophien,
          success:         hatLeben
        });
        if (logErr) console.error('[Level] daily_quiz_log insert Fehler:', logErr);
      }
    }

    return { ep, trophien, bonus, levelUp };
  }

  // Tauscht Trophäen gegen Energie (50 Trophäen = 1 Energydrink).
  async function tauscheTrophäen(anzahlEnergie) {
    const user = Auth.currentUser();
    if (!user) return { erfolg: false, fehler: 'Nicht eingeloggt' };

    const kostet = anzahlEnergie * 50;
    const stats  = await getUserStats();

    if (stats.trophies < kostet) {
      return { erfolg: false, fehler: `Nicht genug Trophäen (${kostet} benötigt, du hast ${stats.trophies})` };
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
    berechneLevel,
    berechneFortschritt,
    getUserStats,
    verbrauchEnergie,
    starteTagesQuiz,
    hatHeuteTagesQuizGespielt,
    vergibBelohnungen,
    tauscheTrophäen
  };
})();
