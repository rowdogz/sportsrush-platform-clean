-- Dev-only SportsRush seed data for local D1.
-- Safe to run repeatedly against the local sportsrush-dev database.
-- Do not apply this file to staging or production.

PRAGMA foreign_keys = ON;

INSERT INTO sports (id, slug, name, created_at, updated_at, legacy_id)
VALUES
  ('sport-rugby-league', 'rugby-league', 'Rugby League', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-sport-rugby-league')
ON CONFLICT(id) DO UPDATE SET
  slug = excluded.slug,
  name = excluded.name,
  updated_at = excluded.updated_at,
  legacy_id = excluded.legacy_id;

INSERT INTO users
  (id, email, email_normalized, email_verified_at, password_hash, role, is_active,
   is_legacy_migration, legacy_wp_user_id, legacy_migration_completed_at, created_at, updated_at)
VALUES
  (
    'sr-dev-superadmin',
    'admin@sportsrush.test',
    'admin@sportsrush.test',
    '2026-01-01T00:00:00.000Z',
    '$pbkdf2-sha256$600000$c3ItZGV2LXNlZWQtc2FsdA$K2bAKdrLwnAmp8OqELBVQ0_9gXmcl2NVyDoshF3Sz7U',
    'superadmin',
    1,
    0,
    NULL,
    NULL,
    '2026-01-01T00:00:00.000Z',
    '2026-01-01T00:00:00.000Z'
  )
ON CONFLICT(id) DO UPDATE SET
  email = excluded.email,
  email_normalized = excluded.email_normalized,
  email_verified_at = excluded.email_verified_at,
  password_hash = excluded.password_hash,
  role = excluded.role,
  is_active = excluded.is_active,
  updated_at = excluded.updated_at;

INSERT INTO user_profiles
  (user_id, display_name, avatar_url, timezone, created_at, updated_at)
VALUES
  ('sr-dev-superadmin', 'SportsRush Dev Admin', NULL, 'Europe/London', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z')
ON CONFLICT(user_id) DO UPDATE SET
  display_name = excluded.display_name,
  avatar_url = excluded.avatar_url,
  timezone = excluded.timezone,
  updated_at = excluded.updated_at;

INSERT INTO competitions
  (id, sport_id, slug, name, short_name, country_code, is_active, created_at, updated_at, legacy_id)
VALUES
  ('comp-super-league', 'sport-rugby-league', 'super-league', 'Betfred Super League', 'Super League', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-comp-super-league'),
  ('comp-challenge-cup', 'sport-rugby-league', 'challenge-cup', 'Betfred Challenge Cup', 'Challenge Cup', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-comp-challenge-cup'),
  ('comp-nrl', 'sport-rugby-league', 'nrl', 'National Rugby League', 'NRL', 'AU', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-comp-nrl')
ON CONFLICT(id) DO UPDATE SET
  sport_id = excluded.sport_id,
  slug = excluded.slug,
  name = excluded.name,
  short_name = excluded.short_name,
  country_code = excluded.country_code,
  is_active = excluded.is_active,
  updated_at = excluded.updated_at,
  legacy_id = excluded.legacy_id;

INSERT INTO seasons
  (id, competition_id, slug, name, starts_on, ends_on, is_active, created_at, updated_at, legacy_id)
VALUES
  ('season-super-league-2026', 'comp-super-league', '2026', '2026 Super League', '2026-02-12', '2026-10-10', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-season-sl-2026'),
  ('season-super-league-2025', 'comp-super-league', '2025', '2025 Super League', '2025-02-13', '2025-10-11', 0, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-season-sl-2025'),
  ('season-challenge-cup-2026', 'comp-challenge-cup', '2026', '2026 Challenge Cup', '2026-01-10', '2026-06-06', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-season-cc-2026'),
  ('season-nrl-2026', 'comp-nrl', '2026', '2026 NRL', '2026-03-05', '2026-10-04', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-season-nrl-2026')
ON CONFLICT(id) DO UPDATE SET
  competition_id = excluded.competition_id,
  slug = excluded.slug,
  name = excluded.name,
  starts_on = excluded.starts_on,
  ends_on = excluded.ends_on,
  is_active = excluded.is_active,
  updated_at = excluded.updated_at,
  legacy_id = excluded.legacy_id;

INSERT INTO teams
  (id, sport_id, slug, name, short_name, display_name, country_code, is_active, created_at, updated_at, legacy_id)
VALUES
  ('team-wigan-warriors', 'sport-rugby-league', 'wigan-warriors', 'Wigan Warriors', 'Wigan', 'Wigan Warriors', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-wigan'),
  ('team-st-helens', 'sport-rugby-league', 'st-helens', 'St Helens', 'Saints', 'St Helens', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-saints'),
  ('team-leeds-rhinos', 'sport-rugby-league', 'leeds-rhinos', 'Leeds Rhinos', 'Leeds', 'Leeds Rhinos', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-leeds'),
  ('team-warrington-wolves', 'sport-rugby-league', 'warrington-wolves', 'Warrington Wolves', 'Warrington', 'Warrington Wolves', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-warrington'),
  ('team-hull-kr', 'sport-rugby-league', 'hull-kr', 'Hull KR', 'Hull KR', 'Hull Kingston Rovers', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-hull-kr'),
  ('team-catalans-dragons', 'sport-rugby-league', 'catalans-dragons', 'Catalans Dragons', 'Catalans', 'Catalans Dragons', 'FR', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-catalans'),
  ('team-leigh-leopards', 'sport-rugby-league', 'leigh-leopards', 'Leigh Leopards', 'Leigh', 'Leigh Leopards', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-leigh'),
  ('team-salford-red-devils', 'sport-rugby-league', 'salford-red-devils', 'Salford Red Devils', 'Salford', 'Salford Red Devils', 'GB', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-salford'),
  ('team-brisbane-broncos', 'sport-rugby-league', 'brisbane-broncos', 'Brisbane Broncos', 'Brisbane', 'Brisbane Broncos', 'AU', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-brisbane'),
  ('team-penrith-panthers', 'sport-rugby-league', 'penrith-panthers', 'Penrith Panthers', 'Penrith', 'Penrith Panthers', 'AU', 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 'legacy-team-penrith')
ON CONFLICT(id) DO UPDATE SET
  sport_id = excluded.sport_id,
  slug = excluded.slug,
  name = excluded.name,
  short_name = excluded.short_name,
  display_name = excluded.display_name,
  country_code = excluded.country_code,
  is_active = excluded.is_active,
  updated_at = excluded.updated_at,
  legacy_id = excluded.legacy_id;

INSERT INTO team_aliases
  (id, team_id, sport_id, alias, normalized_alias, source, priority, is_active, created_at, updated_at, legacy_id)
VALUES
  ('alias-wigan-manual', 'team-wigan-warriors', 'sport-rugby-league', 'Wigan', 'wigan', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-wigan-bbc', 'team-wigan-warriors', 'sport-rugby-league', 'Wigan Warriors', 'wigan warriors', 'bbc sport', 90, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-saints-manual', 'team-st-helens', 'sport-rugby-league', 'Saints', 'saints', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-saints-bbc', 'team-st-helens', 'sport-rugby-league', 'St Helens', 'st helens', 'bbc sport', 90, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-leeds-manual', 'team-leeds-rhinos', 'sport-rugby-league', 'Leeds', 'leeds', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-wire-manual', 'team-warrington-wolves', 'sport-rugby-league', 'Wire', 'wire', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-hull-kr-manual', 'team-hull-kr', 'sport-rugby-league', 'Rovers', 'rovers', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-catalans-manual', 'team-catalans-dragons', 'sport-rugby-league', 'Catalans', 'catalans', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-leigh-manual', 'team-leigh-leopards', 'sport-rugby-league', 'Leigh', 'leigh', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-salford-manual', 'team-salford-red-devils', 'sport-rugby-league', 'Salford', 'salford', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-brisbane-manual', 'team-brisbane-broncos', 'sport-rugby-league', 'Broncos', 'broncos', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('alias-penrith-manual', 'team-penrith-panthers', 'sport-rugby-league', 'Panthers', 'panthers', 'manual', 100, 1, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL)
ON CONFLICT(id) DO UPDATE SET
  team_id = excluded.team_id,
  sport_id = excluded.sport_id,
  alias = excluded.alias,
  normalized_alias = excluded.normalized_alias,
  source = excluded.source,
  priority = excluded.priority,
  is_active = excluded.is_active,
  updated_at = excluded.updated_at,
  legacy_id = excluded.legacy_id;

INSERT INTO competition_teams
  (id, competition_id, season_id, team_id, created_at, legacy_id)
VALUES
  ('ct-sl-2026-wigan', 'comp-super-league', 'season-super-league-2026', 'team-wigan-warriors', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-saints', 'comp-super-league', 'season-super-league-2026', 'team-st-helens', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-leeds', 'comp-super-league', 'season-super-league-2026', 'team-leeds-rhinos', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-wire', 'comp-super-league', 'season-super-league-2026', 'team-warrington-wolves', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-hull-kr', 'comp-super-league', 'season-super-league-2026', 'team-hull-kr', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-catalans', 'comp-super-league', 'season-super-league-2026', 'team-catalans-dragons', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-leigh', 'comp-super-league', 'season-super-league-2026', 'team-leigh-leopards', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-sl-2026-salford', 'comp-super-league', 'season-super-league-2026', 'team-salford-red-devils', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-nrl-2026-brisbane', 'comp-nrl', 'season-nrl-2026', 'team-brisbane-broncos', '2026-01-01T00:00:00.000Z', NULL),
  ('ct-nrl-2026-penrith', 'comp-nrl', 'season-nrl-2026', 'team-penrith-panthers', '2026-01-01T00:00:00.000Z', NULL)
ON CONFLICT(id) DO UPDATE SET
  competition_id = excluded.competition_id,
  season_id = excluded.season_id,
  team_id = excluded.team_id,
  legacy_id = excluded.legacy_id;

INSERT INTO rounds
  (id, season_id, round, round_name, display_order, starts_at, ends_at, created_at, updated_at, legacy_id)
VALUES
  ('round-sl-2026-01', 'season-super-league-2026', '1', 'Round 1', 1, '2026-02-12T00:00:00.000Z', '2026-02-15T23:59:59.000Z', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('round-sl-2026-02', 'season-super-league-2026', '2', 'Round 2', 2, '2026-02-19T00:00:00.000Z', '2026-02-22T23:59:59.000Z', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('round-sl-2026-03', 'season-super-league-2026', '3', 'Round 3', 3, '2026-02-26T00:00:00.000Z', '2026-03-01T23:59:59.000Z', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('round-sl-2026-04', 'season-super-league-2026', '4', 'Round 4', 4, '2026-03-05T00:00:00.000Z', '2026-03-08T23:59:59.000Z', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('round-cc-2026-qf', 'season-challenge-cup-2026', 'QF', 'Quarter Finals', 1, '2026-04-04T00:00:00.000Z', '2026-04-05T23:59:59.000Z', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL),
  ('round-nrl-2026-01', 'season-nrl-2026', '1', 'Round 1', 1, '2026-03-05T00:00:00.000Z', '2026-03-08T23:59:59.000Z', '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', NULL)
ON CONFLICT(id) DO UPDATE SET
  season_id = excluded.season_id,
  round = excluded.round,
  round_name = excluded.round_name,
  display_order = excluded.display_order,
  starts_at = excluded.starts_at,
  ends_at = excluded.ends_at,
  updated_at = excluded.updated_at,
  legacy_id = excluded.legacy_id;

INSERT INTO fixtures
  (id, sport_id, competition_id, season_id, round_id, round, round_name, round_order,
   home_team_id, away_team_id, scheduled_at, original_scheduled_at, venue_name, status,
   home_score, away_score, result_source, result_entered_at, result_entered_by,
   created_at, updated_at, legacy_match_id, legacy_fixture_id, external_source, external_id)
VALUES
  ('fixture-sl-2026-001', 'sport-rugby-league', 'comp-super-league', 'season-super-league-2026', 'round-sl-2026-01', '1', 'Round 1', 1, 'team-wigan-warriors', 'team-st-helens', '2026-02-12T20:00:00.000Z', NULL, 'The Brick Community Stadium', 'completed', 24, 18, 'manual', '2026-02-12T22:00:00.000Z', 'sr-dev-superadmin', '2026-01-01T00:00:00.000Z', '2026-02-12T22:00:00.000Z', 100001, 'legacy-fixture-sl-2026-001', 'sportsrush-seed', 'sl-2026-001'),
  ('fixture-sl-2026-002', 'sport-rugby-league', 'comp-super-league', 'season-super-league-2026', 'round-sl-2026-01', '1', 'Round 1', 1, 'team-leeds-rhinos', 'team-warrington-wolves', '2026-02-13T20:00:00.000Z', NULL, 'AMT Headingley Rugby Stadium', 'completed', 16, 20, 'manual', '2026-02-13T22:00:00.000Z', 'sr-dev-superadmin', '2026-01-01T00:00:00.000Z', '2026-02-13T22:00:00.000Z', 100002, 'legacy-fixture-sl-2026-002', 'sportsrush-seed', 'sl-2026-002'),
  ('fixture-sl-2026-003', 'sport-rugby-league', 'comp-super-league', 'season-super-league-2026', 'round-sl-2026-02', '2', 'Round 2', 2, 'team-hull-kr', 'team-catalans-dragons', '2026-02-20T20:00:00.000Z', NULL, 'Sewell Group Craven Park', 'scheduled', NULL, NULL, NULL, NULL, NULL, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 100003, 'legacy-fixture-sl-2026-003', 'sportsrush-seed', 'sl-2026-003'),
  ('fixture-sl-2026-004', 'sport-rugby-league', 'comp-super-league', 'season-super-league-2026', 'round-sl-2026-02', '2', 'Round 2', 2, 'team-leigh-leopards', 'team-salford-red-devils', '2026-02-21T17:30:00.000Z', NULL, 'Leigh Sports Village', 'postponed', NULL, NULL, NULL, NULL, NULL, '2026-01-01T00:00:00.000Z', '2026-02-21T18:00:00.000Z', 100004, 'legacy-fixture-sl-2026-004', 'sportsrush-seed', 'sl-2026-004'),
  ('fixture-sl-2026-005', 'sport-rugby-league', 'comp-super-league', 'season-super-league-2026', 'round-sl-2026-03', '3', 'Round 3', 3, 'team-st-helens', 'team-leeds-rhinos', '2026-05-15T20:00:00.000Z', NULL, 'Totally Wicked Stadium', 'completed', 30, 12, 'manual', '2026-05-15T22:00:00.000Z', 'sr-dev-superadmin', '2026-01-01T00:00:00.000Z', '2026-05-15T22:00:00.000Z', 100005, 'legacy-fixture-sl-2026-005', 'sportsrush-seed', 'sl-2026-005'),
  ('fixture-sl-2026-006', 'sport-rugby-league', 'comp-super-league', 'season-super-league-2026', 'round-sl-2026-04', '4', 'Round 4', 4, 'team-warrington-wolves', 'team-wigan-warriors', '2026-05-22T20:00:00.000Z', NULL, 'Halliwell Jones Stadium', 'scheduled', NULL, NULL, NULL, NULL, NULL, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 100006, 'legacy-fixture-sl-2026-006', 'sportsrush-seed', 'sl-2026-006'),
  ('fixture-cc-2026-qf-001', 'sport-rugby-league', 'comp-challenge-cup', 'season-challenge-cup-2026', 'round-cc-2026-qf', 'QF', 'Quarter Finals', 1, 'team-hull-kr', 'team-wigan-warriors', '2026-04-04T14:30:00.000Z', NULL, 'Sewell Group Craven Park', 'completed', 10, 28, 'manual', '2026-04-04T16:30:00.000Z', 'sr-dev-superadmin', '2026-01-01T00:00:00.000Z', '2026-04-04T16:30:00.000Z', 200001, 'legacy-fixture-cc-2026-qf-001', 'sportsrush-seed', 'cc-2026-qf-001'),
  ('fixture-nrl-2026-001', 'sport-rugby-league', 'comp-nrl', 'season-nrl-2026', 'round-nrl-2026-01', '1', 'Round 1', 1, 'team-brisbane-broncos', 'team-penrith-panthers', '2026-03-06T09:00:00.000Z', NULL, 'Suncorp Stadium', 'scheduled', NULL, NULL, NULL, NULL, NULL, '2026-01-01T00:00:00.000Z', '2026-01-01T00:00:00.000Z', 300001, 'legacy-fixture-nrl-2026-001', 'sportsrush-seed', 'nrl-2026-001')
ON CONFLICT(id) DO UPDATE SET
  sport_id = excluded.sport_id,
  competition_id = excluded.competition_id,
  season_id = excluded.season_id,
  round_id = excluded.round_id,
  round = excluded.round,
  round_name = excluded.round_name,
  round_order = excluded.round_order,
  home_team_id = excluded.home_team_id,
  away_team_id = excluded.away_team_id,
  scheduled_at = excluded.scheduled_at,
  original_scheduled_at = excluded.original_scheduled_at,
  venue_name = excluded.venue_name,
  status = excluded.status,
  home_score = excluded.home_score,
  away_score = excluded.away_score,
  result_source = excluded.result_source,
  result_entered_at = excluded.result_entered_at,
  result_entered_by = excluded.result_entered_by,
  updated_at = excluded.updated_at,
  legacy_match_id = excluded.legacy_match_id,
  legacy_fixture_id = excluded.legacy_fixture_id,
  external_source = excluded.external_source,
  external_id = excluded.external_id;

INSERT INTO audit_events
  (id, actor_user_id, action, target_type, target_id, before_metadata, after_metadata, created_at)
VALUES
  ('audit-seed-001', 'sr-dev-superadmin', 'seed.import', 'database', 'sportsrush-dev', NULL, '{"summary":"Seeded SportsRush dev admin dataset"}', '2026-01-01T00:00:00.000Z'),
  ('audit-seed-002', 'sr-dev-superadmin', 'fixture.result.enter', 'fixture', 'fixture-sl-2026-005', NULL, '{"homeScore":30,"awayScore":12,"source":"manual"}', '2026-05-15T22:00:00.000Z'),
  ('audit-seed-003', 'sr-dev-superadmin', 'fixture.status.transition', 'fixture', 'fixture-sl-2026-004', '{"status":"scheduled"}', '{"status":"postponed"}', '2026-02-21T18:00:00.000Z')
ON CONFLICT(id) DO UPDATE SET
  actor_user_id = excluded.actor_user_id,
  action = excluded.action,
  target_type = excluded.target_type,
  target_id = excluded.target_id,
  before_metadata = excluded.before_metadata,
  after_metadata = excluded.after_metadata,
  created_at = excluded.created_at;
