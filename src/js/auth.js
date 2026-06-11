// Einfacher sessionStorage-basierter Login (Demo).
// ACHTUNG: Nur für lokale Demozwecke – keine echte Sicherheit.
const Auth = (() => {
  const KEY = "lernhub_user";

  // Testbenutzer
  const USERS = {
    test: "test123",
  };

  function login(user, pass) {
    if (USERS[user] && USERS[user] === pass) {
      sessionStorage.setItem(KEY, user);
      return true;
    }
    return false;
  }

  function logout() {
    sessionStorage.removeItem(KEY);
    location.href = "/index.html";
  }

  function isLoggedIn() {
    return sessionStorage.getItem(KEY) !== null;
  }

  function currentUser() {
    return sessionStorage.getItem(KEY);
  }

  // Schützt eine Seite: leitet zum Login um, wenn nicht eingeloggt.
  function requireLogin() {
    if (!isLoggedIn()) {
      location.href = "/index.html";
    }
  }

  return { login, logout, isLoggedIn, currentUser, requireLogin };
})();
