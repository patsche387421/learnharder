-- ============================================================
-- Migration 001 — LernHub Schema
-- Ausführen: Supabase Dashboard → SQL Editor → New query
-- ============================================================

-- Alte Tabellen sauber entfernen (idempotent)
DROP TABLE IF EXISTS quiz_results   CASCADE;
DROP TABLE IF EXISTS thema_progress CASCADE;
DROP TABLE IF EXISTS fach_stats     CASCADE;

-- ── fach_stats ───────────────────────────────────────────────
-- Aggregierte Statistik pro User und Fach.
CREATE TABLE fach_stats (
  id                 UUID        DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id            UUID        NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  fach_id            TEXT        NOT NULL,
  themen_bearbeitet  INT         DEFAULT 0,
  quiz_punkte_gesamt INT         DEFAULT 0,
  letzte_aktivitaet  TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE(user_id, fach_id)
);

-- ── thema_progress ───────────────────────────────────────────
-- Fortschritt pro User und Thema. Wird nach jedem Quiz-Versuch aktualisiert.
CREATE TABLE thema_progress (
  id            UUID        DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id       UUID        NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  thema_id      TEXT        NOT NULL,
  abgeschlossen BOOLEAN     DEFAULT FALSE,
  letzter_score INT,                        -- Prozentwert 0–100 des letzten Versuchs
  quiz_punkte   INT         DEFAULT 0,
  updated_at    TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE(user_id, thema_id)
);

-- ── quiz_results ──────────────────────────────────────────────
-- Vollständiger Verlauf aller Quiz-Versuche (kein UNIQUE — mehrere Versuche erlaubt).
CREATE TABLE quiz_results (
  id          UUID        DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id     UUID        NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  thema_id    TEXT        NOT NULL,
  richtig     INT         NOT NULL,
  gesamt      INT         NOT NULL,
  score       INT         NOT NULL,         -- Prozentwert 0–100
  erstellt_at TIMESTAMPTZ DEFAULT NOW()
);

-- ── Indizes für häufige Abfragen ──────────────────────────────
CREATE INDEX ON fach_stats     (user_id);
CREATE INDEX ON thema_progress (user_id);
CREATE INDEX ON thema_progress (thema_id);
CREATE INDEX ON quiz_results   (user_id, thema_id);

-- ── Row Level Security ────────────────────────────────────────
ALTER TABLE fach_stats     ENABLE ROW LEVEL SECURITY;
ALTER TABLE thema_progress ENABLE ROW LEVEL SECURITY;
ALTER TABLE quiz_results   ENABLE ROW LEVEL SECURITY;

-- Jeder User darf nur seine eigenen Zeilen lesen und schreiben
CREATE POLICY "eigene fach_stats"
  ON fach_stats USING (auth.uid() = user_id) WITH CHECK (auth.uid() = user_id);

CREATE POLICY "eigene thema_progress"
  ON thema_progress USING (auth.uid() = user_id) WITH CHECK (auth.uid() = user_id);

CREATE POLICY "eigene quiz_results"
  ON quiz_results USING (auth.uid() = user_id) WITH CHECK (auth.uid() = user_id);
