// Stats-Modul: Lernstatistiken via Supabase (fach_stats, thema_progress, quiz_results).
// RLS stellt sicher, dass jeder User nur seine eigenen Daten liest/schreibt.
const Stats = (() => {
  const sb = SupabaseClient.client;

  async function ladeFachStats(fachId) {
    const user = Auth.currentUser();
    if (!user) return leerFachStats();

    const { data } = await sb
      .from('fach_stats')
      .select('themen_bearbeitet, quiz_punkte_gesamt, letzte_aktivitaet')
      .eq('user_id', user.id)
      .eq('fach_id', fachId)
      .maybeSingle();

    if (!data) return leerFachStats();
    return {
      fortschritt:      0,  // wird in renderFachSeite aus themenBearbeitet / total berechnet
      themenBearbeitet: data.themen_bearbeitet  ?? 0,
      quizPunkte:       data.quiz_punkte_gesamt ?? 0,
      letzteAktivitaet: data.letzte_aktivitaet  ?? null
    };
  }

  async function ladeThemaStats(themaId) {
    const user = Auth.currentUser();
    if (!user) return { abgeschlossen: false, quizPunkte: 0, letzterScore: null };

    const { data } = await sb
      .from('thema_progress')
      .select('abgeschlossen, quiz_punkte, letzter_score')
      .eq('user_id', user.id)
      .eq('thema_id', themaId)
      .maybeSingle();

    if (!data) return { abgeschlossen: false, quizPunkte: 0, letzterScore: null };
    return {
      abgeschlossen: data.abgeschlossen ?? false,
      quizPunkte:    data.quiz_punkte   ?? 0,
      letzterScore:  data.letzter_score ?? null
    };
  }

  async function speichereQuizErgebnis(themaId, richtig, gesamt) {
    const user = Auth.currentUser();
    if (!user) return;

    const score  = Math.round((richtig / gesamt) * 100);
    const fachId = themaId.split('-')[0];

    // 1. Quiz-Verlauf speichern
    await sb.from('quiz_results').insert({
      user_id: user.id, thema_id: themaId, richtig, gesamt, score
    });

    // 2. Thema-Fortschritt upsert
    await sb.from('thema_progress').upsert({
      user_id:       user.id,
      thema_id:      themaId,
      abgeschlossen: score >= 60,
      letzter_score: score,
      quiz_punkte:   richtig,
      updated_at:    new Date().toISOString()
    }, { onConflict: 'user_id,thema_id' });

    // 3. Fach-Gesamtstatistik aktualisieren
    const { data: alle } = await sb
      .from('thema_progress')
      .select('quiz_punkte, abgeschlossen')
      .eq('user_id', user.id)
      .like('thema_id', fachId + '-%');

    if (alle) {
      await sb.from('fach_stats').upsert({
        user_id:            user.id,
        fach_id:            fachId,
        themen_bearbeitet:  alle.filter(p => p.abgeschlossen).length,
        quiz_punkte_gesamt: alle.reduce((s, p) => s + (p.quiz_punkte || 0), 0),
        letzte_aktivitaet:  new Date().toISOString()
      }, { onConflict: 'user_id,fach_id' });
    }
  }

  function leerFachStats() {
    return { fortschritt: 0, themenBearbeitet: 0, quizPunkte: 0, letzteAktivitaet: null };
  }

  return { ladeFachStats, ladeThemaStats, speichereQuizErgebnis };
})();