// Auth-Modul: Login/Logout via Supabase Auth.
// Session wird gecacht; requireLogin() wartet asynchron auf Supabase-Antwort.
const Auth = (() => {
  const sb = SupabaseClient.client;
  let _session = null;

  // Session bei Statusänderung (Login, Logout, Tab-Fokus) aktualisieren
  sb.auth.onAuthStateChange((_event, session) => {
    _session = session;
  });

  // Einmalig beim Laden synchronisieren
  sb.auth.getSession().then(({ data }) => { _session = data.session; });

  async function login(email, password) {
    const { error } = await sb.auth.signInWithPassword({ email, password });
    return !error;
  }

  async function logout() {
    // scope:'local' löscht die Session aus localStorage auch ohne Serverantwort
    await sb.auth.signOut({ scope: 'local' });
    window.location.replace("/index.html");
  }

  // Sync-Guard: nutzt gecachte Session (ausreichend nach requireLogin())
  function isLoggedIn() {
    return _session !== null;
  }

  // Gibt das Supabase-User-Objekt zurück oder null
  function currentUser() {
    return _session?.user ?? null;
  }

  // Anzeigename aus dem E-Mail-Lokalteil ableiten und säubern:
  // erstes „echtes" Namenssegment (getrennt durch . / _ / -, mind. 2 Zeichen),
  // angehängte Ziffern entfernt, Erstbuchstabe groß.
  // "max.mustermann@…" → "Max", "schueler1@…" → "Schueler", "j_doe@…" → "Doe".
  function displayName() {
    const email     = _session?.user?.email ?? "";
    const lokalteil = email.split("@")[0];
    // Segmente säubern (Endziffern weg), erstes mit ≥ 2 Zeichen als Name wählen
    const segmente  = lokalteil.split(/[._-]/).map((teil) => teil.replace(/\d+$/, ""));
    const name      = segmente.find((teil) => teil.length >= 2) || lokalteil;
    if (!name) return email;
    return name.charAt(0).toUpperCase() + name.slice(1);
  }

  // Schützt eine Seite: wartet auf Supabase-Session und leitet um wenn nicht eingeloggt
  async function requireLogin() {
    const { data } = await sb.auth.getSession();
    _session = data.session;
    if (!_session) location.href = "/index.html";
  }

  return { login, logout, isLoggedIn, currentUser, displayName, requireLogin };
})();