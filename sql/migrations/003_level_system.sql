-- ============================================================
-- Migration 003 — Level-System (Gamification)
-- Ausführen: Supabase Dashboard → SQL Editor → New query
--
-- Legt die drei Backend-Tabellen des Gamification-Systems an:
--   user_stats      globale EP/Level/Energie/Trophäen pro User
--   subject_xp      EP/Level pro User und Fach
--   daily_quiz_log  Verlauf der Tages-Quiz-Versuche
--
-- Siehe docs/LEVEL_SYSTEM.md (Spezifikation).
-- RLS stellt sicher, dass jeder User nur seine eigenen Zeilen liest/schreibt.
--
-- Hinweis: Bestehende Testuser (Migration 002) erhalten ihre user_stats-Zeile
-- NICHT automatisch (der Trigger greift nur bei NEUEN auth.users). Daher nach
-- dem Ausführen einmalig nachziehen:
--   INSERT INTO user_stats (user_id) SELECT id FROM auth.users;
-- ============================================================

-- ── user_stats ───────────────────────────────────────────────
-- Globale Gamification-Werte pro User (genau eine Zeile je User).
DROP TABLE IF EXISTS user_stats CASCADE;

CREATE TABLE user_stats (
  user_id           UUID        PRIMARY KEY
                    REFERENCES auth.users(id) ON DELETE CASCADE,
  total_xp          INT         DEFAULT 0,    -- Gesamt-EP über alle Fächer
  level             INT         DEFAULT 1,    -- globales User-Level
  energy            INT         DEFAULT 5,    -- verbleibende Energydrinks heute
  energy_last_reset TIMESTAMPTZ DEFAULT NOW(),-- Zeitpunkt des letzten Energie-Resets
  trophies          INT         DEFAULT 0,    -- gesammelte Trophäen
  created_at        TIMESTAMPTZ DEFAULT NOW(),
  updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- Row Level Security: nur eigene Zeile lesbar/schreibbar
ALTER TABLE user_stats ENABLE ROW LEVEL SECURITY;

CREATE POLICY "User sieht nur eigene Stats"
  ON user_stats FOR ALL
  USING (auth.uid() = user_id)
  WITH CHECK (auth.uid() = user_id);

-- Auto-Insert einer user_stats-Zeile bei jedem neuen auth.users-Eintrag.
-- SECURITY DEFINER, damit der Insert die RLS umgeht (läuft mit Owner-Rechten).
CREATE OR REPLACE FUNCTION create_user_stats()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO user_stats (user_id)
  VALUES (NEW.id)
  ON CONFLICT (user_id) DO NOTHING;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE OR REPLACE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION create_user_stats();

-- ── subject_xp ───────────────────────────────────────────────
-- EP/Level pro User und Fach (eine Zeile je User+Fach).
DROP TABLE IF EXISTS subject_xp CASCADE;

CREATE TABLE subject_xp (
  user_id         UUID REFERENCES auth.users(id) ON DELETE CASCADE,
  subject_id      TEXT NOT NULL,           -- Fach-Präfix, z. B. "pos"
  xp              INT  DEFAULT 0,          -- EP in diesem Fach
  level           INT  DEFAULT 1,          -- Fach-Level
  correct_answers INT  DEFAULT 0,          -- richtige Antworten gesamt im Fach
  PRIMARY KEY (user_id, subject_id)
);

-- Row Level Security: nur eigene Fach-XP lesbar/schreibbar
ALTER TABLE subject_xp ENABLE ROW LEVEL SECURITY;

CREATE POLICY "User sieht nur eigene Fach-XP"
  ON subject_xp FOR ALL
  USING (auth.uid() = user_id)
  WITH CHECK (auth.uid() = user_id);

-- ── daily_quiz_log ───────────────────────────────────────────
-- Verlauf der Tages-Quiz-Versuche (mehrere Einträge je User erlaubt).
DROP TABLE IF EXISTS daily_quiz_log CASCADE;

CREATE TABLE daily_quiz_log (
  id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         UUID        REFERENCES auth.users(id) ON DELETE CASCADE,
  played_at       TIMESTAMPTZ DEFAULT NOW(),
  score           INT,                      -- Prozentwert 0–100
  xp_earned       INT         DEFAULT 0,
  trophies_earned INT         DEFAULT 0,
  success         BOOL        DEFAULT FALSE -- true = mit Leben > 0 % beendet
);

-- Row Level Security: nur eigene Quiz-Logs lesbar/schreibbar
ALTER TABLE daily_quiz_log ENABLE ROW LEVEL SECURITY;

CREATE POLICY "User sieht nur eigene Quiz-Logs"
  ON daily_quiz_log FOR ALL
  USING (auth.uid() = user_id)
  WITH CHECK (auth.uid() = user_id);
