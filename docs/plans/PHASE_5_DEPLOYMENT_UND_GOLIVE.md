# Phase 5: CI/CD Deployment, Testing & Go-Live auf World4You

## 1. Ziel von Phase 5

Die fertige, vollkommen autarke PHP/MariaDB-Applikation wird per GitHub Actions automatisiert auf World4You bereitgestellt, ausgiebig getestet und die alte Netlify- und Supabase-Infrastruktur endgültig deaktiviert.

---

## 2. World4You Vorbereitung (Kundenportal)

### 2.1 Webspace & Domain konfigurieren
1. Im Kundenbereich unter [my.world4you.com](https://my.world4you.com) einloggen.
2. Paket **Webhosting Go** wählen.
3. Domain zuweisen (z. B. `learnharder.at`).
4. **Let's Encrypt SSL-Zertifikat** mit 1 Klick aktivieren.

### 2.2 SFTP/FTPS-Benutzer anlegen
1. Im Portal zu *Webhosting → FTP-Benutzer* navigieren.
2. Neuen Benutzer anlegen (z. B. `u123456_deploy`).
3. Startverzeichnis auf `/html` (oder Domain-Unterordner) setzen.

---

## 3. GitHub Actions Pipeline (`.github/workflows/deploy.yml`)

### 3.1 GitHub Secrets hinterlegen
Unter **GitHub Repository → Settings → Secrets and variables → Actions**:
* `W4Y_FTP_SERVER`: z. B. `ftp.deinedomain.at`
* `W4Y_FTP_USERNAME`: Der angelegte FTP-Nutzername
* `W4Y_FTP_PASSWORD`: Das generierte FTP-Passwort

*(Hinweis: Supabase-Secrets werden hier nicht mehr benötigt!)*

### 3.2 Die Workflow-Datei (`.github/workflows/deploy.yml`)

```yaml
name: Deploy to World4You

on:
  push:
    branches:
      - main

jobs:
  deploy:
    name: Sync to World4You
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Sync files via FTPS to World4You
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.W4Y_FTP_SERVER }}
          username: ${{ secrets.W4Y_FTP_USERNAME }}
          password: ${{ secrets.W4Y_FTP_PASSWORD }}
          server-dir: /html/
          local-dir: ./src/
          protocol: ftps
          dangerous-clean-slate: false
```

---

## 4. End-to-End-Testing auf World4You

Vor der endgültigen Freigabe werden folgende Punkte geprüft:

1. **Datenschutz & Netzwerk:**
   * Browser F12 Network-Tab: Es dürfen **ausschließlich** Verbindungen zur eigenen Domain (`deinedomain.at`) bestehen. Null externe CDNs oder Tracker.
2. **Authentifizierung:**
   * Login mit `test@lernhub.htl` und Passwort `lernhub123`.
   * Prüfen des Session-Cookies: Flag `HttpOnly` und `Secure` müssen aktiv sein.
3. **Lernfortschritt & Quiz:**
   * Quiz absolvieren $\to$ Score und XP müssen in MariaDB ankommen.
   * Manipulationstest: Versuch, Punkte in der Konsole zu fälschen, muss fehlschlagen (da serverseitig in PHP validiert).
4. **Wartungsmodus:**
   * `.htaccess`-Regel aktivieren $\to$ Besucher sehen HTTP 503 Wartungsseite.

---

## 5. Abschaltung von Altlasten (Netlify & Supabase)

Sobald World4You 48 Stunden fehlerfrei produktiv läuft:
1. **Netlify:**
   * Build stoppen und Site im Netlify Dashboard löschen.
2. **Supabase:**
   * Datenbank-Dump zur Archivierung herunterladen.
   * Projekt im Supabase Dashboard pausieren bzw. löschen.

---

## 6. Checkliste für Phase 5

- [ ] FTPS-Zugangsdaten in GitHub Secrets hinterlegt
- [ ] Workflow `.github/workflows/deploy.yml` getestet (grüner Haken)
- [ ] End-to-End-Test aller Quizzes und Logins auf World4You erfolgreich
- [ ] Netlify-Site und Supabase-Projekt endgültig deaktiviert
