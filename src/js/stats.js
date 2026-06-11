// Stats-Modul: Platzhalter für die spätere Datenbankanbindung.
// Alle Funktionen liefern aktuell neutrale Leerwerte zurück.
// Zum Anschließen einer echten DB: fetch()-Aufrufe gemäß DB_Integration_Guide.md einsetzen.
const Stats = (() => {

  // Lädt Statistiken für ein gesamtes Fach (z.B. "pos").
  // TODO DB: const res = await fetch(`/api/v1/stats/fach/${fachId}`, { headers: Auth.headers() });
  async function ladeFachStats(fachId) {
    return {
      fortschritt:      0,   // Prozentwert Gesamtfortschritt (0–100)
      themenBearbeitet: 0,   // Anzahl vollständig abgeschlossener Themen
      quizPunkte:       0,   // Gesamtpunkte im Fach (Summe aller Themen)
      letzteAktivitaet: null // ISO-8601-Datum des letzten Lernvorgangs oder null
    };
  }

  // Lädt Statistiken für ein einzelnes Thema (z.B. "pos-datenstrukturen").
  // TODO DB: const res = await fetch(`/api/v1/stats/thema/${themaId}`, { headers: Auth.headers() });
  async function ladeThemaStats(themaId) {
    return {
      abgeschlossen: false, // Thema vollständig bearbeitet?
      quizPunkte:    0,     // Erreichte Punkte in diesem Thema
      letzterScore:  null   // Prozentwert des letzten Quiz-Versuchs (0–100) oder null
    };
  }

  // Speichert ein Quiz-Ergebnis nach dem Auswerten (wird vom Quiz-Modul aufgerufen).
  // TODO DB: await fetch('/api/v1/stats/quiz', { method: 'POST', headers: Auth.headers(),
  //            body: JSON.stringify({ themaId, richtig, gesamt, zeitstempel: new Date() }) });
  async function speichereQuizErgebnis(themaId, richtig, gesamt) {
    console.info("[Stats] Quiz-Ergebnis (noch nicht gespeichert):", {
      themaId,
      richtig,
      gesamt,
      score: Math.round((richtig / gesamt) * 100) + "%"
    });
  }

  return { ladeFachStats, ladeThemaStats, speichereQuizErgebnis };
})();
