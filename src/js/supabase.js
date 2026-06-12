// Initialisiert den Supabase-Client aus den globalen Config-Konstanten.
// SUPABASE_URL und SUPABASE_ANON_KEY kommen aus config.js (nicht in Git).
// Noch keine Auth-Logik – nur Client-Bereitstellung für spätere Module.
const SupabaseClient = (() => {
  const client = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

  return { client };
})();
