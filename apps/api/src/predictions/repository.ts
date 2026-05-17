import type { DbClient } from "../lib/db";
import type { PredictionListQuery, PredictionWriteInput } from "./schemas";

export type PredictionRow = {
  readonly id: string;
  readonly user_id: string;
  readonly fixture_id: string;
  readonly home_score: number;
  readonly away_score: number;
  readonly created_at: string;
  readonly updated_at: string;
};

export type PredictionFixtureRow = {
  readonly id: string;
  readonly competition_id: string;
  readonly season_id: string;
  readonly round_id: string | null;
  readonly scheduled_at: string;
  readonly status: string;
  readonly home_score: number | null;
  readonly away_score: number | null;
};

export type PredictionScoreInput = {
  readonly id: string;
  readonly predictionId: string;
  readonly userId: string;
  readonly fixtureId: string;
  readonly competitionId: string;
  readonly seasonId: string;
  readonly roundId: string | null;
  readonly scoredAt: string;
  readonly totalPoints: number;
  readonly exactScorePoints: number;
  readonly correctResultPoints: number;
  readonly homeScorePoints: number;
  readonly awayScorePoints: number;
  readonly goalDifferencePoints: number;
  readonly breakdownJson: string;
};

export type LeaderboardRow = {
  readonly user_id: string;
  readonly email: string | null;
  readonly display_name: string | null;
  readonly total_points: number;
  readonly exact_scores: number;
  readonly correct_results: number;
  readonly predictions_scored: number;
  readonly last_scored_at: string | null;
};

function offset(query: PredictionListQuery): number {
  return ((query.page ?? 1) - 1) * (query.limit ?? 25);
}

export async function findFixtureForPrediction(
  db: DbClient,
  fixtureId: string,
): Promise<PredictionFixtureRow | null> {
  return db.queryOne<PredictionFixtureRow>(
    `SELECT id, competition_id, season_id, round_id, scheduled_at, status, home_score, away_score
       FROM fixtures WHERE id = ?`,
    [fixtureId],
  );
}

export async function findPrediction(
  db: DbClient,
  userId: string,
  fixtureId: string,
): Promise<PredictionRow | null> {
  return db.queryOne<PredictionRow>(
    "SELECT * FROM predictions WHERE user_id = ? AND fixture_id = ?",
    [userId, fixtureId],
  );
}

export async function createPrediction(
  db: DbClient,
  id: string,
  userId: string,
  input: PredictionWriteInput,
  now: string,
): Promise<PredictionRow> {
  await db.execute(
    `INSERT INTO predictions
       (id, user_id, fixture_id, home_score, away_score, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    [id, userId, input.fixtureId, input.homeScore, input.awayScore, now, now],
  );
  const row = await findPrediction(db, userId, input.fixtureId);
  if (row === null) throw new Error("Prediction insert failed");
  return row;
}

export async function updatePrediction(
  db: DbClient,
  userId: string,
  input: PredictionWriteInput,
  now: string,
): Promise<PredictionRow | null> {
  await db.execute(
    `UPDATE predictions
        SET home_score = ?, away_score = ?, updated_at = ?
      WHERE user_id = ? AND fixture_id = ?`,
    [input.homeScore, input.awayScore, now, userId, input.fixtureId],
  );
  return findPrediction(db, userId, input.fixtureId);
}

export async function listUserPredictions(
  db: DbClient,
  userId: string,
  query: PredictionListQuery,
) {
  const rows = await db.queryAll<PredictionRow>(
    `SELECT p.*
       FROM predictions p
       JOIN fixtures f ON f.id = p.fixture_id
      WHERE p.user_id = ?
        AND (? IS NULL OR f.competition_id = ?)
        AND (? IS NULL OR f.round_id = ?)
      ORDER BY f.scheduled_at DESC
      LIMIT ? OFFSET ?`,
    [
      userId,
      query.competitionId ?? null,
      query.competitionId ?? null,
      query.roundId ?? null,
      query.roundId ?? null,
      query.limit ?? 25,
      offset(query),
    ],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count
       FROM predictions p
       JOIN fixtures f ON f.id = p.fixture_id
      WHERE p.user_id = ?
        AND (? IS NULL OR f.competition_id = ?)
        AND (? IS NULL OR f.round_id = ?)`,
    [
      userId,
      query.competitionId ?? null,
      query.competitionId ?? null,
      query.roundId ?? null,
      query.roundId ?? null,
    ],
  );
  return { rows, total: total?.count ?? 0 };
}

export async function listFixturePredictions(
  db: DbClient,
  fixtureId: string,
  query: PredictionListQuery,
) {
  const rows = await db.queryAll<PredictionRow>(
    `SELECT * FROM predictions
      WHERE fixture_id = ?
      ORDER BY updated_at DESC
      LIMIT ? OFFSET ?`,
    [fixtureId, query.limit ?? 25, offset(query)],
  );
  const total = await db.queryOne<{ count: number }>(
    "SELECT COUNT(*) AS count FROM predictions WHERE fixture_id = ?",
    [fixtureId],
  );
  return { rows, total: total?.count ?? 0 };
}

export async function upsertPredictionScore(
  db: DbClient,
  input: PredictionScoreInput,
) {
  await db.execute(
    `INSERT INTO prediction_scores
       (id, prediction_id, user_id, fixture_id, competition_id, season_id, round_id,
        scored_at, total_points, exact_score_points, correct_result_points,
        home_score_points, away_score_points, goal_difference_points, breakdown_json)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON CONFLICT(prediction_id, fixture_id)
     DO UPDATE SET
        scored_at = excluded.scored_at,
        total_points = excluded.total_points,
        exact_score_points = excluded.exact_score_points,
        correct_result_points = excluded.correct_result_points,
        home_score_points = excluded.home_score_points,
        away_score_points = excluded.away_score_points,
        goal_difference_points = excluded.goal_difference_points,
        breakdown_json = excluded.breakdown_json`,
    [
      input.id,
      input.predictionId,
      input.userId,
      input.fixtureId,
      input.competitionId,
      input.seasonId,
      input.roundId,
      input.scoredAt,
      input.totalPoints,
      input.exactScorePoints,
      input.correctResultPoints,
      input.homeScorePoints,
      input.awayScorePoints,
      input.goalDifferencePoints,
      input.breakdownJson,
    ],
  );
}

export async function listFixturePredictionsForScoring(
  db: DbClient,
  fixtureId: string,
) {
  return db.queryAll<PredictionRow>(
    "SELECT * FROM predictions WHERE fixture_id = ? ORDER BY created_at",
    [fixtureId],
  );
}

export async function listRankings(db: DbClient, query: PredictionListQuery) {
  const privateLeagueJoin = query.privateLeagueId
    ? "JOIN private_league_members plm ON plm.user_id = ps.user_id AND plm.private_league_id = ? AND plm.is_active = 1"
    : "";
  const params: unknown[] = [];
  if (query.privateLeagueId) params.push(query.privateLeagueId);
  const clauses = [
    query.competitionId ? "ps.competition_id = ?" : "",
    query.roundId ? "ps.round_id = ?" : "",
    query.month ? "substr(ps.scored_at, 1, 7) = ?" : "",
  ].filter(Boolean);
  if (query.competitionId) params.push(query.competitionId);
  if (query.roundId) params.push(query.roundId);
  if (query.month) params.push(query.month);
  const where = clauses.length > 0 ? `WHERE ${clauses.join(" AND ")}` : "";
  const rows = await db.queryAll<LeaderboardRow>(
    `SELECT ps.user_id,
            u.email,
            up.display_name,
            SUM(ps.total_points) AS total_points,
            SUM(CASE WHEN ps.exact_score_points > 0 THEN 1 ELSE 0 END) AS exact_scores,
            SUM(CASE WHEN ps.correct_result_points > 0 THEN 1 ELSE 0 END) AS correct_results,
            COUNT(*) AS predictions_scored,
            MAX(ps.scored_at) AS last_scored_at
       FROM prediction_scores ps
       ${privateLeagueJoin}
       LEFT JOIN users u ON u.id = ps.user_id
       LEFT JOIN user_profiles up ON up.user_id = ps.user_id
       ${where}
      GROUP BY ps.user_id
      ORDER BY total_points DESC, exact_scores DESC, correct_results DESC, last_scored_at ASC, ps.user_id ASC
      LIMIT ? OFFSET ?`,
    [...params, query.limit ?? 25, offset(query)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count FROM (
       SELECT ps.user_id
         FROM prediction_scores ps
         ${privateLeagueJoin}
         ${where}
        GROUP BY ps.user_id
     ) ranked`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}
