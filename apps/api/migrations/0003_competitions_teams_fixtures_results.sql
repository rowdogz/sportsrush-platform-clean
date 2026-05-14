-- Migration 0003: Competitions, teams, fixtures and results schema
-- SportsRush platform — PR-08 schema only.
--
-- Scope:
--   - sports
--   - competitions
--   - seasons
--   - teams
--   - team_aliases
--   - competition_teams
--   - rounds
--   - fixtures
--   - result_corrections
--
-- Rules:
--   - UUID TEXT primary keys.
--   - ISO 8601 timestamps stored as TEXT.
--   - round and round_name are first-class fixture fields.
--   - team aliases are unique per sport after normalisation.
--   - fixture statuses include postponed/abandoned/void/cancelled/completed.
--   - result corrections are append-only and auditable.
--   - duplicate fixture prevention is enforced at DB level.

PRAGMA foreign_keys = ON;

CREATE TABLE sports (
  id TEXT PRIMARY KEY,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  legacy_id TEXT,
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(slug)) > 0),
  CHECK (length(trim(name)) > 0)
);

CREATE TABLE competitions (
  id TEXT PRIMARY KEY,
  sport_id TEXT NOT NULL REFERENCES sports(id) ON DELETE RESTRICT,
  slug TEXT NOT NULL,
  name TEXT NOT NULL,
  short_name TEXT,
  country_code TEXT,
  is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  legacy_id TEXT,
  UNIQUE (sport_id, slug),
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(slug)) > 0),
  CHECK (length(trim(name)) > 0)
);

CREATE INDEX idx_competitions_sport_id ON competitions(sport_id);
CREATE INDEX idx_competitions_active ON competitions(is_active);

CREATE TABLE seasons (
  id TEXT PRIMARY KEY,
  competition_id TEXT NOT NULL REFERENCES competitions(id) ON DELETE RESTRICT,
  slug TEXT NOT NULL,
  name TEXT NOT NULL,
  starts_on TEXT,
  ends_on TEXT,
  is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  legacy_id TEXT,
  UNIQUE (competition_id, slug),
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(slug)) > 0),
  CHECK (length(trim(name)) > 0),
  CHECK (starts_on IS NULL OR ends_on IS NULL OR starts_on <= ends_on)
);

CREATE INDEX idx_seasons_competition_id ON seasons(competition_id);
CREATE INDEX idx_seasons_active ON seasons(is_active);

CREATE TABLE teams (
  id TEXT PRIMARY KEY,
  sport_id TEXT NOT NULL REFERENCES sports(id) ON DELETE RESTRICT,
  slug TEXT NOT NULL,
  name TEXT NOT NULL,
  short_name TEXT,
  display_name TEXT,
  country_code TEXT,
  is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  legacy_id TEXT,
  UNIQUE (sport_id, slug),
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(slug)) > 0),
  CHECK (length(trim(name)) > 0)
);

CREATE INDEX idx_teams_sport_id ON teams(sport_id);
CREATE INDEX idx_teams_active ON teams(is_active);

CREATE TABLE team_aliases (
  id TEXT PRIMARY KEY,
  team_id TEXT NOT NULL REFERENCES teams(id) ON DELETE CASCADE,
  sport_id TEXT NOT NULL REFERENCES sports(id) ON DELETE RESTRICT,
  alias TEXT NOT NULL,
  normalized_alias TEXT NOT NULL,
  source TEXT NOT NULL DEFAULT 'manual',
  priority INTEGER NOT NULL DEFAULT 100,
  is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  legacy_id TEXT,
  UNIQUE (sport_id, normalized_alias),
  UNIQUE (team_id, normalized_alias),
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(alias)) > 0),
  CHECK (length(trim(normalized_alias)) > 0)
);

CREATE INDEX idx_team_aliases_team_id ON team_aliases(team_id);
CREATE INDEX idx_team_aliases_lookup ON team_aliases(sport_id, normalized_alias, is_active);

CREATE TABLE competition_teams (
  id TEXT PRIMARY KEY,
  competition_id TEXT NOT NULL REFERENCES competitions(id) ON DELETE CASCADE,
  season_id TEXT NOT NULL REFERENCES seasons(id) ON DELETE CASCADE,
  team_id TEXT NOT NULL REFERENCES teams(id) ON DELETE RESTRICT,
  created_at TEXT NOT NULL,
  legacy_id TEXT,
  UNIQUE (season_id, team_id)
);

CREATE INDEX idx_competition_teams_competition_id ON competition_teams(competition_id);
CREATE INDEX idx_competition_teams_team_id ON competition_teams(team_id);

CREATE TABLE rounds (
  id TEXT PRIMARY KEY,
  season_id TEXT NOT NULL REFERENCES seasons(id) ON DELETE CASCADE,
  round TEXT NOT NULL,
  round_name TEXT NOT NULL,
  display_order INTEGER NOT NULL,
  starts_at TEXT,
  ends_at TEXT,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  legacy_id TEXT,
  UNIQUE (season_id, round),
  UNIQUE (season_id, display_order),
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(round)) > 0),
  CHECK (length(trim(round_name)) > 0),
  CHECK (starts_at IS NULL OR ends_at IS NULL OR starts_at <= ends_at)
);

CREATE INDEX idx_rounds_season_order ON rounds(season_id, display_order);

CREATE TABLE fixtures (
  id TEXT PRIMARY KEY,
  sport_id TEXT NOT NULL REFERENCES sports(id) ON DELETE RESTRICT,
  competition_id TEXT NOT NULL REFERENCES competitions(id) ON DELETE RESTRICT,
  season_id TEXT NOT NULL REFERENCES seasons(id) ON DELETE RESTRICT,
  round_id TEXT REFERENCES rounds(id) ON DELETE SET NULL,

  -- First-class round fields are deliberately denormalised because prediction
  -- pages depend on stable round metadata even if source round records change.
  round TEXT NOT NULL,
  round_name TEXT NOT NULL,
  round_order INTEGER,

  home_team_id TEXT NOT NULL REFERENCES teams(id) ON DELETE RESTRICT,
  away_team_id TEXT NOT NULL REFERENCES teams(id) ON DELETE RESTRICT,

  scheduled_at TEXT NOT NULL,
  original_scheduled_at TEXT,
  venue_name TEXT,

  status TEXT NOT NULL DEFAULT 'scheduled'
    CHECK (status IN (
      'scheduled',
      'postponed',
      'abandoned',
      'void',
      'cancelled',
      'completed'
    )),

  home_score INTEGER,
  away_score INTEGER,
  result_source TEXT,
  result_entered_at TEXT,
  result_entered_by TEXT,

  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,

  legacy_match_id INTEGER,
  legacy_fixture_id TEXT,
  external_source TEXT,
  external_id TEXT,

  UNIQUE (season_id, home_team_id, away_team_id, scheduled_at),
  UNIQUE (external_source, external_id),

  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(round)) > 0),
  CHECK (length(trim(round_name)) > 0),
  CHECK (home_team_id <> away_team_id),
  CHECK (home_score IS NULL OR home_score >= 0),
  CHECK (away_score IS NULL OR away_score >= 0),
  CHECK (
    (status = 'completed' AND home_score IS NOT NULL AND away_score IS NOT NULL)
    OR
    (status <> 'completed')
  )
);

CREATE INDEX idx_fixtures_sport_id ON fixtures(sport_id);
CREATE INDEX idx_fixtures_competition_season ON fixtures(competition_id, season_id);
CREATE INDEX idx_fixtures_round ON fixtures(season_id, round_order, round);
CREATE INDEX idx_fixtures_scheduled_at ON fixtures(scheduled_at);
CREATE INDEX idx_fixtures_status ON fixtures(status);
CREATE INDEX idx_fixtures_home_team ON fixtures(home_team_id);
CREATE INDEX idx_fixtures_away_team ON fixtures(away_team_id);
CREATE INDEX idx_fixtures_legacy_match_id ON fixtures(legacy_match_id);

CREATE TABLE result_corrections (
  id TEXT PRIMARY KEY,
  fixture_id TEXT NOT NULL REFERENCES fixtures(id) ON DELETE RESTRICT,

  previous_status TEXT NOT NULL,
  previous_home_score INTEGER,
  previous_away_score INTEGER,

  corrected_status TEXT NOT NULL,
  corrected_home_score INTEGER,
  corrected_away_score INTEGER,

  reason TEXT NOT NULL,
  corrected_by_user_id TEXT,
  corrected_by_display_name TEXT,
  created_at TEXT NOT NULL,

  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(reason)) > 0),
  CHECK (previous_status IN (
    'scheduled',
    'postponed',
    'abandoned',
    'void',
    'cancelled',
    'completed'
  )),
  CHECK (corrected_status IN (
    'scheduled',
    'postponed',
    'abandoned',
    'void',
    'cancelled',
    'completed'
  )),
  CHECK (previous_home_score IS NULL OR previous_home_score >= 0),
  CHECK (previous_away_score IS NULL OR previous_away_score >= 0),
  CHECK (corrected_home_score IS NULL OR corrected_home_score >= 0),
  CHECK (corrected_away_score IS NULL OR corrected_away_score >= 0)
);

CREATE INDEX idx_result_corrections_fixture_id ON result_corrections(fixture_id);
CREATE INDEX idx_result_corrections_created_at ON result_corrections(created_at);
