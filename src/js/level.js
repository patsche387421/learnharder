// Level-System-Modul: Energie, Trophäen, EP, Level und Tages-Quiz-Verwaltung.
// Nutzt die Tabellen user_stats, subject_xp und daily_quiz_log (Migration 003).
//
// ══ DB-MIGRATION S3b (manuell in Supabase ausführen, vor Prestige-Feature) ══════
// ALTER TABLE user_stats ADD COLUMN IF NOT EXISTS prestige INTEGER NOT NULL DEFAULT 0;
// ════════════════════════════════════════════════════════════════════════════════

// Kurven-Balance (S3b): einzige Stellschrauben der 100-Level-Progression.
// Zum Nachjustieren NUR diese zwei Zahlen ändern → Level 100 = 100^EXPONENT · KOEFFIZIENT.
const LEVEL_EXPONENT    = 1.5;
const LEVEL_KOEFFIZIENT = 8;
const Level = (() => {
  const sb = SupabaseClient.client;

  // EP-Schwellen pro Level-Stufe: Index = Level-Nummer (1–10)
  // LEVEL_THRESHOLDS[n] = Gesamt-EP, die zum Erreichen von Level n benötigt werden.
  // Level 10 ist Maximum → LEVEL_THRESHOLDS[10] = undefined (zeigt "–" im UI).
  // (Weiterhin genutzt für die gespeicherte user_stats.level-Spalte + Level-Up-Toast.)
  const LEVEL_THRESHOLDS = [0, 100, 250, 500, 900, 1400, 2000, 2700, 3500, 4500];

  // 100-Level-Kurve (S3b): SCHWELLEN[n] = EP-Summe im aktuellen Prestige-Zyklus, die
  // zum Abschluss von Level n nötig ist. Kurve: (n)^LEVEL_EXPONENT · LEVEL_KOEFFIZIENT.
  const LEVEL_SCHWELLEN = [0, ...Array.from({ length: 100 },
    (_, i) => Math.round((i + 1) ** LEVEL_EXPONENT * LEVEL_KOEFFIZIENT))];
  // [0, 8, 23, 42, 64, 89, 118, 148, 181, 216, 253, ...]
  // Level 100 = 8000 EP gesamt = ein Prestige-Zyklus
  const EP_PRO_ZYKLUS = LEVEL_SCHWELLEN[100]; // 8000

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
  // Nimmt EP (+ optional prestige) entgegen → quelle-agnostisch (User-, Fach-,
  // später Team-/Saison-EP). 100-Level-Kurve mit Prestige-Zyklus (S3b): die EP
  // werden per Modulo auf den aktuellen Zyklus abgebildet, Level 1–100. Der
  // prestige-Zähler kommt aus der DB (nicht hier berechnet) und wird nur
  // durchgereicht, damit die UI Tier + Prestige aus einer Quelle bekommt.
  function berechneFortschritt(gesamtEp, prestige = 0) {
    const epImZyklus = gesamtEp % EP_PRO_ZYKLUS;
    // Anzahl bestandener Schwellen zählen; Level ist 1-basiert (0 bestanden = Level 1,
    // Schwelle SCHWELLEN[k] bestanden = Level k+1). Deckel bei 100.
    let bestanden = 0;
    while (bestanden < 100 && LEVEL_SCHWELLEN[bestanden + 1] <= epImZyklus) bestanden++;
    const level   = Math.min(100, bestanden + 1);
    const epVon   = LEVEL_SCHWELLEN[level - 1];
    const epBis   = LEVEL_SCHWELLEN[level];
    const prozent = Math.min(100, Math.round((epImZyklus - epVon) / (epBis - epVon) * 100));
    const epText = level < 100
      ? (LEVEL_SCHWELLEN[level] - epImZyklus) + ' EP bis Level ' + (level + 1)
      : 'Prestige erreicht!';
    const tier = level < 25 ? 'bronze'
               : level < 50 ? 'silber'
               : level < 75 ? 'gold'
               : 'platin';
    return { level, prozent, epText, tier, prestige };
  }

  // Reine SSOT-Funktion: Streak = aufeinanderfolgende UTC-Kalendertage mit jeweils
  // ≥1 Herausforderungs-Versuch (LEVEL_SYSTEM §12). Nimmt die played_at-Zeitstempel
  // aller daily_quiz_log-Zeilen entgegen (ohne success-Filter — §12 zählt Zeilen, nicht
  // bestandene Quiz) und zählt rückwärts über DISTINCT UTC-Tage. Kulanz: ist heute (noch)
  // leer, aber gestern vorhanden, läuft die Streak ab gestern weiter; sind gestern UND
  // heute leer → 0. Mehrere Zeilen am selben Tag zählen einmal (Set über UTC-Tage).
  // jetzt ist injizierbar (Test/Referenzzeit), Default = aktuelle Zeit.
  function berechneStreak(zeitstempel, jetzt = new Date()) {
    const tage = new Set((zeitstempel || [])
      .map(ts => new Date(ts).toISOString().split('T')[0]));   // 'YYYY-MM-DD' (UTC)
    if (tage.size === 0) return 0;

    const MS_PRO_TAG = 86_400_000;
    const heuteMs = Date.parse(jetzt.toISOString().split('T')[0]); // heute 00:00 UTC
    const alsTag  = ms => new Date(ms).toISOString().split('T')[0];

    // Startpunkt: heute, falls belegt; sonst gestern (Kulanz); sonst 0.
    let cursorMs;
    if      (tage.has(alsTag(heuteMs)))               cursorMs = heuteMs;
    else if (tage.has(alsTag(heuteMs - MS_PRO_TAG)))  cursorMs = heuteMs - MS_PRO_TAG;
    else return 0;

    let streak = 0;
    while (tage.has(alsTag(cursorMs))) { streak++; cursorMs -= MS_PRO_TAG; }
    return streak;
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
    if (!user) return { energy: 5, level: 1, trophies: 0, totalXp: 0, prestige: 0 };

    const { data } = await sb
      .from('user_stats')
      .select('total_xp, level, energy, trophies, energy_last_reset, prestige')
      .eq('user_id', user.id)
      .maybeSingle();

    if (!data) return { energy: 5, level: 1, trophies: 0, totalXp: 0, prestige: 0 };
    return {
      energy:   await rechargeEnergie(user, data),
      level:    data.level    ?? 1,
      trophies: data.trophies ?? 0,
      totalXp:  data.total_xp ?? 0,
      prestige: data.prestige ?? 0
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

  // Liest alle played_at-Zeitstempel des Users (nur diese Spalte, alle Zeilen — kein
  // Row-Limit, sonst würden viele Versuche an einem Tag ältere Tage verdecken und die
  // Streak abschneiden) und leitet daraus die aktuelle Streak ab. 0 bei fehlendem Login,
  // Fehler oder ohne Zeilen. Delegiert die Rechnung an die reine berechneStreak (SSOT).
  async function getStreak() {
    const user = Auth.currentUser();
    if (!user) return 0;

    const { data, error } = await sb
      .from('daily_quiz_log')
      .select('played_at')
      .eq('user_id', user.id)
      .order('played_at', { ascending: false });

    if (error || !data) {
      console.error('[Level] Streak-Abfrage Fehler:', error);
      return 0;
    }
    return berechneStreak(data.map(r => r.played_at));
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

    // user_stats aktualisieren (EP, Level, Trophäen, Prestige)
    const { data: aktStats } = await sb
      .from('user_stats')
      .select('total_xp, level, trophies, prestige')
      .eq('user_id', user.id)
      .maybeSingle();

    const neueXp      = (aktStats?.total_xp  ?? 0) + ep;
    const neueTrophies = (aktStats?.trophies  ?? 0) + trophien;
    const neuesLevel  = berechneFortschritt(neueXp).level;
    // levelUp aus der 100er-Kurve: neues vs. altes Level (aus den EP VOR diesem Quiz),
    // unabhängig vom gespeicherten Cache-Wert (der noch alte 10er-Zahlen enthalten kann).
    const levelUp     = neuesLevel > berechneFortschritt(aktStats?.total_xp ?? 0).level;

    // Prestige = abgeschlossene 100-Level-Zyklen. Rein aus total_xp ableitbar und
    // monoton steigend → wird bei jedem Upsert mitgeschrieben (kein Reset möglich).
    const altePrestige = aktStats?.prestige ?? 0;
    const neuePrestige = Math.floor(neueXp / EP_PRO_ZYKLUS);
    const prestigeUp   = neuePrestige > altePrestige;

    const { error: statsErr } = await sb.from('user_stats').upsert({
      user_id:    user.id,
      total_xp:   neueXp,
      level:      neuesLevel,
      trophies:   neueTrophies,
      prestige:   neuePrestige,
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
      const neuesFachLevel = berechneFortschritt(neueFachXp).level;
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

    return { ep, trophien, bonus, levelUp, prestigeUp };
  }

  // Tauscht Trophäen gegen Energie (50 Trophäen = 1 Energydrink).
  // Blockt VOR dem Trophäen-Abzug, wenn die Energie schon am Deckel (5) ist —
  // sonst würden die Trophäen verpuffen. Die Gutschrift wird zusätzlich bei 5
  // gecappt (Math.min), analog zu rechargeEnergie.
  async function tauscheTrophäen(anzahlEnergie) {
    const user = Auth.currentUser();
    if (!user) return { erfolg: false, fehler: 'Nicht eingeloggt' };

    const kostet = anzahlEnergie * 50;
    const stats  = await getUserStats();

    if (stats.energy >= 5) {
      return { erfolg: false, fehler: 'Energie ist schon voll' };
    }

    if (stats.trophies < kostet) {
      return { erfolg: false, fehler: `Nicht genug Trophäen (${kostet} benötigt, du hast ${stats.trophies})` };
    }

    const { error } = await sb.from('user_stats').update({
      trophies:   stats.trophies - kostet,
      energy:     Math.min(5, stats.energy + anzahlEnergie),
      updated_at: new Date().toISOString()
    }).eq('user_id', user.id);

    if (error) return { erfolg: false, fehler: error.message };
    return { erfolg: true };
  }

  return {
    LEVEL_THRESHOLDS,
    berechneLevel,
    berechneFortschritt,
    berechneStreak,
    getUserStats,
    getStreak,
    verbrauchEnergie,
    starteTagesQuiz,
    hatHeuteTagesQuizGespielt,
    vergibBelohnungen,
    tauscheTrophäen
  };
})();
