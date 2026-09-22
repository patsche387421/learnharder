# Umsetzungs- & Detailplan: Schritt 1 (DSGVO-Härtung & Wartungsmodus)

## 1. Ziel von Schritt 1

Bevor die Domain umgezogen wird oder das eigentliche World4You-Hosting aktiv geht, bereiten wir zwei Dinge vor:
1. **Eine professionelle, DSGVO-konforme Wartungs-/Platzhalterseite (`maintenance.html`),** die Besuchern signalisiert: *„LearnHarder wird aktualisiert / Umzug auf österreichische Server läuft“*.
2. **Die lokale Bereitstellung aller externen Ressourcen** (insbesondere `@supabase/supabase-js`), damit keinerlei IP-Adressen an externe CDNs fließen.
3. **Eine Umschalt-Strategie per `.htaccess`**, um den Wartungsmodus jederzeit per Knopfdruck aktivieren und deaktivieren zu können.

---

## 2. Die Wartungsseite (`src/maintenance.html`)

### 2.1 Design- & UI-Vorgaben (Passend zum LearnHarder Brand)
* **Dark Mode Standard:** Nutzt das Farbschema aus `tokens.css` (Hintergrund `#0F0C1B`, Primär-Lila `#7C3AED`, Akzent `#F59E0B`).
* **Typografie:** Nutzt die bereits lokal eingebundenen Fonts (`Space Grotesk` & `Space Mono`).
* **Zero External Dependencies:** Absolut keine externen Aufrufe (kein Google Fonts, keine CDN-Skripte, kein Tracking).
* **Inhaltliche Botschaft:**
  * **Logo & Titel:** LearnHarder (HTL Lernplattform).
  * **Status-Badge:** „Wartungsarbeiten & Serverumzug“.
  * **Erklärung:** Transparenter Hinweis auf den Wechsel zu österreichischen Servern zur Verbesserung des Datenschutzes und der Systemstabilität.
  * **Status-Ampel / Info-Box:** Wann die Plattform voraussichtlich wieder erreichbar ist.
  * **Kontakt/Impressum:** E-Mail-Kontakt für dringende Fragen.

---

## 3. Die Umschalt-Mechanik (Wartungsmodus an/aus)

Auf World4You (Apache-Webserver) steuert eine einzige Regel in der `.htaccess` den Wartungsmodus:

### 3.1 Wie es funktioniert:
Wenn eine Wartungsdatei (`maintenance.enable` oder entsprechende `.htaccess`-Regel) aktiv ist:
* Werden alle Besucher mit HTTP-Status `503 Service Unavailable` auf `/maintenance.html` weitergeleitet.
  * *Hinweis: HTTP 503 ist wichtig für Suchmaschinen, damit bestehende Rankings nicht verloren gehen.*
* **Admin-Ausnahme:** Deine eigene IP-Adresse kann von der Weiterleitung ausgenommen werden, sodass du auf World4You testen kannst, während Schüler die Wartungsseite sehen.

### 3.2 Die `.htaccess`-Konfiguration (Muster)

```apache
# --- WARTUNGSMODUS (Aktivieren durch Auskommentierung entfernen) ---
# RewriteEngine On
# RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.000$       # Deine eigene IP (Freigabe für Tests)
# RewriteCond %{REQUEST_URI} !^/maintenance\.html$
# RewriteCond %{REQUEST_URI} !^/assets/
# RewriteCond %{REQUEST_URI} !^/css/
# RewriteRule ^(.*)$ /maintenance.html [R=503,L]
# ErrorDocument 503 /maintenance.html
```

---

## 4. Lokalisierung der Supabase-Bibliothek (DSGVO-Schritt)

### 4.1 Problem:
Aktuell laden 17 HTML-Seiten:
```html
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
```

### 4.2 Lösung in Schritt 1:
1. Herunterladen der Datei `supabase-js-v2.js` in das Verzeichnis:
   `src/assets/vendor/supabase.js`
2. Ersetzen der Einbindung in den HTML-Seiten durch:
   ```html
   <script src="/assets/vendor/supabase.js"></script>
   ```
3. **Ergebnis:** Die App läuft zu 100 % autark ohne jegliche CDN-Verbindung.

---

## 5. Vorbereitungs- & Prüfschritte (Checkliste für Schritt 1)

| Task | Aktion | Status |
| :--- | :--- | :--- |
| **1.1** | `src/maintenance.html` erstellen (Dark-Mode, HTL-Branding, DSGVO-konform) | Geplant |
| **1.2** | Ordner `src/assets/vendor/` anlegen & `@supabase/supabase-js` lokal speichern | Geplant |
| **1.3** | Script-Tags in HTML-Dateien auf lokale Vendor-Datei umstellen | Geplant |
| **1.4** | `.htaccess` mit Wartungsmodus-Regeln (vorbereitet, standardmäßig inaktiv) ausstatten | Geplant |
| **1.5** | Lokaler Test: Wartungsmodus aktivieren, Funktion verifizieren, wieder deaktivieren | Geplant |

---

## 6. Nächster Schritt nach Fertigstellung

Sobald die Wartungsseite und die lokalen Bibliotheken bereitliegen, kann das World4You-Hosting gebucht bzw. konfiguriert werden (Schritt 2 der Roadmap). Die Wartungsseite wird das Erste sein, was bei der DNS-Umstellung auf World4You live zu sehen ist.
