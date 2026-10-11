# LearnHarder V2 — Gamification-Ökonomie & Simulationsmodell

> **Status:** Arbeitsmodell vor Sprint 1  
> **Gültig ab:** Version 2.0 (MVP)  
> **Autoren/Verantwortlich:** Lead-Entwickler & Didaktik-Team  
> **Zweck:** Mathematische Ausbalancierung von XP-Kurven (Level 1–100), Fokus/Brainpower-Zyklen, Streaks und Trophäen-Wechselkursen.

---

## 1. Übersicht & Spielökonomie-Leitsätze

Die Ökonomie von LearnHarder V2 basiert auf drei didaktisch-psychologischen Grundsätzen:
1. **Lernen wird niemals bestraft oder gesperrt:** Standardlernen (Theorie lesen, Standardaufgaben) kostet **0 Brainpower**.
2. **Anstrengung & Beständigkeit schlagen Grind:** Regelmäßige tägliche Streaks bringen verlässliche Vorteile; schnelles Klick-Farming ohne Fehlerfreiheit wird durch abflachende XP-Kurven unattraktiv.
3. **Cheat-Sicherheit durch Server-Autorität:** Sämtliche BP- und XP-Gutschriften werden in Millisekunden serverseitig in Laravel kalkuliert und validiert.

---

## 2. XP-Kurve & Level 1 bis 100

### 2.1 Mathematische Formel
Das Level eines Nutzers berechnet sich aus seinen Gesamt-Erfahrungspunkten (XP) im aktuellen Prestige-Zyklus:

$$\text{Schwellenwert}(n) = \text{round}(n^{1.5} \times 8)$$

* $n$ = Ziel-Level (1 bis 100)
* $\text{Exponent} = 1.5$ (progressive Progression: Einstieg schnell, Endgame anspruchsvoll)
* $\text{Koeffizient} = 8$ (Skalierungsfaktor)
* Maximal-XP für Level 100: **8.000 XP** pro Prestige-Zyklus.

### 2.2 Referenz-Schwellenwerte

| Level | Benötigte XP (Kumulativ) | XP zum nächsten Level | Didaktische Einordnung / Meilenstein |
| :---: | :---: | :---: | :--- |
| **1** | **0 XP** | 8 XP | Tutorial / Registrierung abgeschlossen |
| **2** | **8 XP** | 15 XP | Erste Theorielektion absolviert |
| **5** | **89 XP** | 35 XP | Erste Themenreihe gemeistert (ca. Tag 2) |
| **10** | **253 XP** | 68 XP | Solides Grundwissen; erstes Fach-Abzeichen |
| **25** | **1.000 XP** | 170 XP | Fortgeschrittener Schüler (ca. Woche 3–4) |
| **50** | **2.828 XP** | 295 XP | Halber Weg; Klassen-Top-Performer |
| **75** | **5.196 XP** | 430 XP | Experten-Status; Mentoren-Reife |
| **100** | **8.000 XP** | — | **Prestige-Meister** (Prestige-Rang steigt um +1, Reset auf Lvl 1 mit permanentem Meister-Badge) |

---

## 3. Brainpower (BP) System-Balancierung

### 3.1 Kapazität & Level-Skalierung
* **Startkapazität:** $100\text{ BP}$ (Level 1)
* **Wachstum:** Alle 5 Spielerlevel erhöht sich die maximale Kapazität um $+5\text{ BP}$:

$$\text{BP}_{\text{max}}(L) = 100 + \left\lfloor \frac{L}{5} \right\rfloor \times 5$$

* **Level 1–4:** $100\text{ BP}$
* **Level 25:** $125\text{ BP}$
* **Level 50:** $150\text{ BP}$
* **Level 100:** $200\text{ BP}$ (Maximaler Deckel)

---

### 3.2 Tägliche Regeneration & Streak-Multiplikator
Jeden Kalendertag um **00:00 UTC** lädt sich Brainpower automatisch auf (bis zum jeweiligen $\text{BP}_{\text{max}}$).

$$\text{Tägliche Regeneration} = \text{Basis (50 BP)} + \min(\text{Streak-Tage} \times 2, 30\text{ BP})$$

| Streak-Tage | Tägliche Regeneration | Zeit bis 100 % Aufladung (von 0 BP) |
| :---: | :---: | :---: |
| **0 Tage (kein Streak)** | $50\text{ BP}$ | 2 Tage (bei $100\text{ BP}_\text{max}$) |
| **3 Tage** | $56\text{ BP}$ | ca. 1,8 Tage |
| **7 Tage (1 Woche)** | $64\text{ BP}$ | ca. 1,5 Tage |
| **15+ Tage (Max-Bonus)** | **$80\text{ BP}$** | **1,25 Tage** |

---

### 3.3 Verbrauchs-Ökonomie (Sinks)

| Aktion / Aktivität | BP-Kosten | Belohnung / Ertrag | Didaktischer Nutzen |
| :--- | :---: | :--- | :--- |
| **Theorie lesen & Basisaufgaben** | **0 BP** | $2\text{ XP}$ pro richtiger Antwort | Hürdenfreies Lernen |
| **Prüfungs-Challenge (Schwer)** | **25 BP** | $5\text{ XP}$/Frage + bis zu $+50\text{ XP}$ Bonus + Trophäen | Klausurvorbereitung |
| **2x XP Booster (30 Min)** | **40 BP** | $2\times$ XP auf alle Aufgaben für 30 Minuten | Gezielte Lern-Sessions |
| **1v1 Duell (Phase 2)** | **15 BP** | Sieger: $+30\text{ XP}$ + Ranglistenpunkte; Verlierer: $+10\text{ XP}$ | Synchroner Wettbewerb |

---

## 4. Trophäen-Konvertierung (Tausch-Ökonomie)

Trophäen belohnen dauerhafte Exzellenz (fehlerfreie Quizdurchläufe):
* **Erhalt:** $+1\text{ Trophäe}$ pro fehlerfreier Aufgabenserie (mind. 5 Fragen).
* **Tauschkurs in Brainpower:**
  $$50\text{ Trophäen} \longrightarrow 25\text{ BP}$$
* **Sicherheits-Deckel:** Maximal 2 Tauschvorgänge pro Kalendertag ($50\text{ BP}$ maximaler Zukauf pro Tag), um Infinite-Loop-Exploits durch Vorrats-Grinding auszuschließen.
* **Prestige-Kosmetik (Phase 3):** Ab Phase 3 können Trophäen alternativ gespart werden, um exklusive Avatar-Skins oder Titel freizuschalten.

---

## 5. Lerner-Personas & Simulationsszenarien

### Szenario A: Gelegenheits-Lerner („Casual")
* **Verhalten:** 2–3 Sessions pro Woche, je 10 Minuten. Kein Streak-Fokus.
* **Tages-XP:** ca. $30\text{ XP}$.
* **Fortschritt:**
  * Level 5 nach ca. 1 Woche.
  * Level 10 nach ca. 4 Wochen.
* **Ergebnis:** Kein Frust; BP läuft nie Gefahr auszugehen, da Basisübungen kostenlos sind.

### Szenario B: Ambitionierter Schüler („Consistent Daily")
* **Verhalten:** Täglich 15 Minuten zur Hausaufgaben- oder Busfahrtzeit. Aktiver Streak (10+ Tage).
* **Tages-XP:** 1x Challenge ($25\text{ BP}$) + Standard-Übungen = ca. $100\text{ XP}$/Tag.
* **Fortschritt:**
  * Level 10 nach ca. 3 Tagen.
  * Level 25 nach ca. 10 Tagen.
  * Level 50 nach ca. 1 Monat.
* **Ergebnis:** Maximale Ausnutzung des Streak-Bonus ($70\text{ BP}$/Tag Regeneration). Perfekter Motivations-Flow.

### Szenario C: Prüfungsphase / Power-Gamer („Exam Prep")
* **Verhalten:** 60 Minuten intensiv vor einer Schulaufgabe / Schularbeit.
* **Ablauf:** Aktiviert 2x XP Booster ($40\text{ BP}$), absolviert 2 Challenges ($50\text{ BP}$).
* **Tages-XP:** Bis zu $350\text{ XP}$ an einem Tag.
* **BP-Bilanz:** Verbraucht $90\text{ BP}$. Nutzt $50\text{ Trophäen}$ für $+25\text{ BP}$ Notreserve.
* **Ergebnis:** Hochgradig belohnend für Klausurvorbereitung ohne Erschöpfung des Kontos vor dem Prüfungstag.
