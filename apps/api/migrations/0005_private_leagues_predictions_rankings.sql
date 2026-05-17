-- Migration 0005: Private leagues, predictions, scoring and rankings foundation

PRAGMA foreign_keys = ON;

CREATE TABLE private_leagues (
  id TEXT PRIMARY KEY,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  description TEXT,
  logo_url TEXT,
  banner_url TEXT,
  invite_code TEXT NOT NULL UNIQUE,
  owner_user_id TEXT REFERENCES users(id) ON DELETE SET NULL,
  is_archived INTEGER NOT NULL DEFAULT 0 CHECK (is_archived IN (0, 1)),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  archived_at TEXT,
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(slug)) > 0),
  CHECK (length(trim(name)) > 0),
  CHECK (length(trim(invite_code)) >= 6)
);

CREATE INDEX idx_private_leagues_archived ON private_leagues(is_archived);
CREATE INDEX idx_private_leagues_owner ON private_leagues(owner_user_id);

CREATE TABLE private_league_members (
  id TEXT PRIMARY KEY,
  private_league_id TEXT NOT NULL REFERENCES private_leagues(id) ON DELETE CASCADE,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  role TEXT NOT NULL DEFAULT 'member' CHECK (role IN ('owner', 'admin', 'member')),
  is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
  joined_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (private_league_id, user_id),
  CHECK (length(trim(id)) > 0)
);

CREATE INDEX idx_private_league_members_league ON private_league_members(private_league_id);
CREATE INDEX idx_private_league_members_user ON private_league_members(user_id);

CREATE TABLE private_league_competitions (
  id TEXT PRIMARY KEY,
  private_league_id TEXT NOT NULL REFERENCES private_leagues(id) ON DELETE CASCADE,
  competition_id TEXT NOT NULL REFERENCES competitions(id) ON DELETE CASCADE,
  created_at TEXT NOT NULL,
  UNIQUE (private_league_id, competition_id),
  CHECK (length(trim(id)) > 0)
);

CREATE INDEX idx_private_league_competitions_league ON private_league_competitions(private_league_id);
CREATE INDEX idx_private_league_competitions_competition ON private_league_competitions(competition_id);

CREATE TABLE predictions (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  fixture_id TEXT NOT NULL REFERENCES fixtures(id) ON DELETE CASCADE,
  home_score INTEGER NOT NULL CHECK (home_score >= 0),
  away_score INTEGER NOT NULL CHECK (away_score >= 0),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  UNIQUE (user_id, fixture_id),
  CHECK (length(trim(id)) > 0)
);

CREATE INDEX idx_predictions_user ON predictions(user_id);
CREATE INDEX idx_predictions_fixture ON predictions(fixture_id);

CREATE TABLE prediction_scores (
  id TEXT PRIMARY KEY,
  prediction_id TEXT NOT NULL REFERENCES predictions(id) ON DELETE CASCADE,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  fixture_id TEXT NOT NULL REFERENCES fixtures(id) ON DELETE CASCADE,
  competition_id TEXT NOT NULL REFERENCES competitions(id) ON DELETE CASCADE,
  season_id TEXT NOT NULL REFERENCES seasons(id) ON DELETE CASCADE,
  round_id TEXT REFERENCES rounds(id) ON DELETE SET NULL,
  scored_at TEXT NOT NULL,
  total_points INTEGER NOT NULL DEFAULT 0,
  exact_score_points INTEGER NOT NULL DEFAULT 0,
  correct_result_points INTEGER NOT NULL DEFAULT 0,
  home_score_points INTEGER NOT NULL DEFAULT 0,
  away_score_points INTEGER NOT NULL DEFAULT 0,
  goal_difference_points INTEGER NOT NULL DEFAULT 0,
  breakdown_json TEXT NOT NULL,
  UNIQUE (prediction_id, fixture_id)
);

CREATE INDEX idx_prediction_scores_user ON prediction_scores(user_id);
CREATE INDEX idx_prediction_scores_competition ON prediction_scores(competition_id, total_points DESC);
CREATE INDEX idx_prediction_scores_round ON prediction_scores(round_id, total_points DESC);
CREATE INDEX idx_prediction_scores_fixture ON prediction_scores(fixture_id);
