-- ============================================================
-- Migration 002 — 5 temporäre Testuser
-- Ausführen: Supabase Dashboard → SQL Editor → New query
-- Passwort für alle: lernhub123
--
-- ACHTUNG: Nur für Demo/Entwicklung.
-- Vor Produktions-Deployment alle Testuser löschen:
--   DELETE FROM auth.users WHERE email LIKE '%@lernhub.htl';
-- ============================================================

DO $$
DECLARE
  uid1 UUID := gen_random_uuid();
  uid2 UUID := gen_random_uuid();
  uid3 UUID := gen_random_uuid();
  uid4 UUID := gen_random_uuid();
  uid5 UUID := gen_random_uuid();
BEGIN

  -- ── User 1: schueler1 ──────────────────────────────────────
  INSERT INTO auth.users (
    instance_id, id, aud, role, email, encrypted_password,
    email_confirmed_at, created_at, updated_at,
    raw_app_meta_data, raw_user_meta_data, is_super_admin,
    confirmation_token, recovery_token, email_change_token_new, email_change
  ) VALUES (
    '00000000-0000-0000-0000-000000000000', uid1,
    'authenticated', 'authenticated',
    'schueler1@lernhub.htl',
    crypt('lernhub123', gen_salt('bf')),
    NOW(), NOW(), NOW(),
    '{"provider":"email","providers":["email"]}', '{}',
    FALSE, '', '', '', ''
  );
  INSERT INTO auth.identities (id, user_id, provider_id, identity_data, provider, last_sign_in_at, created_at, updated_at)
  VALUES (gen_random_uuid(), uid1, 'schueler1@lernhub.htl',
    jsonb_build_object('sub', uid1::text, 'email', 'schueler1@lernhub.htl'),
    'email', NOW(), NOW(), NOW());

  -- ── User 2: schueler2 ──────────────────────────────────────
  INSERT INTO auth.users (
    instance_id, id, aud, role, email, encrypted_password,
    email_confirmed_at, created_at, updated_at,
    raw_app_meta_data, raw_user_meta_data, is_super_admin,
    confirmation_token, recovery_token, email_change_token_new, email_change
  ) VALUES (
    '00000000-0000-0000-0000-000000000000', uid2,
    'authenticated', 'authenticated',
    'schueler2@lernhub.htl',
    crypt('lernhub123', gen_salt('bf')),
    NOW(), NOW(), NOW(),
    '{"provider":"email","providers":["email"]}', '{}',
    FALSE, '', '', '', ''
  );
  INSERT INTO auth.identities (id, user_id, provider_id, identity_data, provider, last_sign_in_at, created_at, updated_at)
  VALUES (gen_random_uuid(), uid2, 'schueler2@lernhub.htl',
    jsonb_build_object('sub', uid2::text, 'email', 'schueler2@lernhub.htl'),
    'email', NOW(), NOW(), NOW());

  -- ── User 3: schueler3 ──────────────────────────────────────
  INSERT INTO auth.users (
    instance_id, id, aud, role, email, encrypted_password,
    email_confirmed_at, created_at, updated_at,
    raw_app_meta_data, raw_user_meta_data, is_super_admin,
    confirmation_token, recovery_token, email_change_token_new, email_change
  ) VALUES (
    '00000000-0000-0000-0000-000000000000', uid3,
    'authenticated', 'authenticated',
    'schueler3@lernhub.htl',
    crypt('lernhub123', gen_salt('bf')),
    NOW(), NOW(), NOW(),
    '{"provider":"email","providers":["email"]}', '{}',
    FALSE, '', '', '', ''
  );
  INSERT INTO auth.identities (id, user_id, provider_id, identity_data, provider, last_sign_in_at, created_at, updated_at)
  VALUES (gen_random_uuid(), uid3, 'schueler3@lernhub.htl',
    jsonb_build_object('sub', uid3::text, 'email', 'schueler3@lernhub.htl'),
    'email', NOW(), NOW(), NOW());

  -- ── User 4: demo ──────────────────────────────────────────
  INSERT INTO auth.users (
    instance_id, id, aud, role, email, encrypted_password,
    email_confirmed_at, created_at, updated_at,
    raw_app_meta_data, raw_user_meta_data, is_super_admin,
    confirmation_token, recovery_token, email_change_token_new, email_change
  ) VALUES (
    '00000000-0000-0000-0000-000000000000', uid4,
    'authenticated', 'authenticated',
    'demo@lernhub.htl',
    crypt('lernhub123', gen_salt('bf')),
    NOW(), NOW(), NOW(),
    '{"provider":"email","providers":["email"]}', '{}',
    FALSE, '', '', '', ''
  );
  INSERT INTO auth.identities (id, user_id, provider_id, identity_data, provider, last_sign_in_at, created_at, updated_at)
  VALUES (gen_random_uuid(), uid4, 'demo@lernhub.htl',
    jsonb_build_object('sub', uid4::text, 'email', 'demo@lernhub.htl'),
    'email', NOW(), NOW(), NOW());

  -- ── User 5: lehrer ────────────────────────────────────────
  INSERT INTO auth.users (
    instance_id, id, aud, role, email, encrypted_password,
    email_confirmed_at, created_at, updated_at,
    raw_app_meta_data, raw_user_meta_data, is_super_admin,
    confirmation_token, recovery_token, email_change_token_new, email_change
  ) VALUES (
    '00000000-0000-0000-0000-000000000000', uid5,
    'authenticated', 'authenticated',
    'lehrer@lernhub.htl',
    crypt('lernhub123', gen_salt('bf')),
    NOW(), NOW(), NOW(),
    '{"provider":"email","providers":["email"]}', '{}',
    FALSE, '', '', '', ''
  );
  INSERT INTO auth.identities (id, user_id, provider_id, identity_data, provider, last_sign_in_at, created_at, updated_at)
  VALUES (gen_random_uuid(), uid5, 'lehrer@lernhub.htl',
    jsonb_build_object('sub', uid5::text, 'email', 'lehrer@lernhub.htl'),
    'email', NOW(), NOW(), NOW());

END $$;

-- Ergebnis prüfen:
-- SELECT email, email_confirmed_at, created_at FROM auth.users
-- WHERE email LIKE '%@lernhub.htl' ORDER BY created_at;
