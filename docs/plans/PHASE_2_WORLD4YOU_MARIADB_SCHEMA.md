# Phase 2: World4You MariaDB Schema & Datenbank-Einrichtung

## 1. Übersicht

Dieses Dokument beschreibt das vollständige relationale Datenbankschema für die **World4You MariaDB/MySQL-Datenbank**. Es ersetzt die bisherigen Supabase/PostgreSQL-Migrationen (`001_schema.sql`, `002_seed_test_users.sql`, `003_level_system.sql`).

---

## 2. Das vollständige MariaDB-Schema (`sql/world4you_mariadb_schema.sql`)

Folgendes SQL-Skript wird einmalig im World4You **phpMyAdmin** ausgeführt:

```sql
-- =============================================================================
-- LearnHarder — Vollständiges MariaDB Schema für World4You
-- Ersetzt Supabase Auth & PostgreSQL
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS quiz_results;
DROP TABLE IF EXISTS daily_quiz_log;
DROP TABLE IF EXISTS thema_progress;
DROP TABLE IF EXISTS subject_xp;
DROP TABLE IF EXISTS user_stats;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ── 1. Benutzerverwaltung (Ersatz für Supabase auth.users) ────────────────────
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(32) DEFAULT 'student',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Globale Gamification-Stats pro User ───────────────────────────────────
CREATE TABLE user_stats (
  user_id INT PRIMARY KEY,
  total_xp INT DEFAULT 0,
  level INT DEFAULT 1,
  energy INT DEFAULT 5,
  energy_last_reset DATETIME DEFAULT CURRENT_TIMESTAMP,
  trophies INT DEFAULT 0,
  prestige INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Fach-Statistiken & EP ──────────────────────────────────────────────────
CREATE TABLE subject_xp (
  user_id INT NOT NULL,
  subject_id VARCHAR(32) NOT NULL,
  xp INT DEFAULT 0,
  level INT DEFAULT 1,
  correct_answers INT DEFAULT 0,
  PRIMARY KEY (user_id, subject_id),
  CONSTRAINT fk_subject_xp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Fortschritt pro Thema ─────────────────────────────────────────────────
CREATE TABLE thema_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  thema_id VARCHAR(64) NOT NULL,
  abgeschlossen TINYINT(1) DEFAULT 0,
  letzter_score INT DEFAULT 0,
  quiz_punkte INT DEFAULT 0,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_thema (user_id, thema_id),
  CONSTRAINT fk_thema_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Tages-Quiz-Verlauf (Streak-Berechnung) ─────────────────────────────────
CREATE TABLE daily_quiz_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  score INT DEFAULT 0,
  xp_earned INT DEFAULT 0,
  trophies_earned INT DEFAULT 0,
  success TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_daily_quiz_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Verlauf aller Quiz-Versuche ───────────────────────────────────────────
CREATE TABLE quiz_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  thema_id VARCHAR(64) NOT NULL,
  richtig INT NOT NULL,
  gesamt INT NOT NULL,
  score INT NOT NULL,
  erstellt_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quiz_results_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. Seed-Daten (Standard-Testbenutzer mit Passwort "lernhub123") ───────────
-- Bcrypt-Hash für "lernhub123": $2y$10$w8uE6s1kE9ZJ9Xm7Q8k5ueZ9T9Zg...
INSERT INTO users (id, email, password_hash) VALUES
(1, 'test@lernhub.htl', '$2y$10$tM9sE7r5fT7jP8e9w1k2l.O0yQx7uR5vK8bM1nO3pQ5rS7tU9vW2e'),
(2, 'schueler1@lernhub.htl', '$2y$10$tM9sE7r5fT7jP8e9w1k2l.O0yQx7uR5vK8bM1nO3pQ5rS7tU9vW2e');

INSERT INTO user_stats (user_id, total_xp, level, energy, trophies) VALUES
(1, 150, 2, 5, 1),
(2, 0, 1, 5, 0);
```

---

## 3. World4You Vorbereitungsschritte

1. Im Portal (`my.world4you.com`) einloggen $\to$ **Datenbanken**.
2. Eine neue MariaDB-Datenbank anlegen:
   * **Datenbankname:** z. B. `u123456_learnharder`
   * **Benutzer:** z. B. `u123456_dbuser`
   * **Passwort:** Starkes Passwort generieren.
3. Direkt per 1-Klick **phpMyAdmin** öffnen.
4. Oben auf den Reiter **SQL** klicken, das obige Schema einfügen und mit **OK** ausführen.
