import { randomUUID } from "node:crypto";
import type { DbClient } from "../lib/db";
import { AppError } from "../lib/errors";
import {
  createPrediction,
  findFixtureForPrediction,
  findPrediction,
  listFixturePredictions,
  listFixturePredictionsForScoring,
  listRankings,
  listUserPredictions,
  updatePrediction,
  upsertPredictionScore,
  type LeaderboardRow,
  type PredictionFixtureRow,
  type PredictionRow,
} from "./repository";
import type { PredictionListQuery, PredictionWriteInput } from "./schemas";

export class PredictionDomainError extends AppError {
  override readonly name = "PredictionDomainError";

  constructor(message: string, correlationId: string) {
    super(422, "PREDICTION_DOMAIN_ERROR", message, { correlationId });
  }
}

export type PredictionServiceContext = {
  readonly db: DbClient;
  readonly now: string;
  readonly correlationId: string;
  readonly actorUserId: string;
};

export type PredictionDto = {
  readonly id: string;
  readonly userId: string;
  readonly fixtureId: string;
  readonly homeScore: number;
  readonly awayScore: number;
  readonly createdAt: string;
  readonly updatedAt: string;
};

export type ScoringBreakdown = {
  readonly exactScore: number;
  readonly correctResult: number;
  readonly homeScore: number;
  readonly awayScore: number;
  readonly goalDifference: number;
  readonly total: number;
};

export type LeaderboardEntry = {
  readonly rank: number;
  readonly movement: number | null;
  readonly userId: string;
  readonly email: string | null;
  readonly displayName: string | null;
  readonly totalPoints: number;
  readonly exactScores: number;
  readonly correctResults: number;
  readonly predictionsScored: number;
  readonly lastScoredAt: string | null;
};

function assert(
  condition: unknown,
  message: string,
  correlationId: string,
): asserts condition {
  if (!condition) throw new PredictionDomainError(message, correlationId);
}

function toPredictionDto(row: PredictionRow): PredictionDto {
  return {
    id: row.id,
    userId: row.user_id,
    fixtureId: row.fixture_id,
    homeScore: row.home_score,
    awayScore: row.away_score,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  };
}

function isLocked(fixture: PredictionFixtureRow, now: string): boolean {
  return fixture.scheduled_at <= now;
}

function resultKind(homeScore: number, awayScore: number) {
  return homeScore === awayScore
    ? "draw"
    : homeScore > awayScore
      ? "home"
      : "away";
}

export function scorePrediction(
  prediction: Pick<PredictionRow, "home_score" | "away_score">,
  fixture: Pick<PredictionFixtureRow, "home_score" | "away_score" | "status">,
): ScoringBreakdown {
  if (fixture.home_score === null || fixture.away_score === null) {
    return {
      exactScore: 0,
      correctResult: 0,
      homeScore: 0,
      awayScore: 0,
      goalDifference: 0,
      total: 0,
    };
  }
  const exactScore =
    prediction.home_score === fixture.home_score &&
    prediction.away_score === fixture.away_score
      ? 5
      : 0;
  const correctResult =
    resultKind(prediction.home_score, prediction.away_score) ===
    resultKind(fixture.home_score, fixture.away_score)
      ? 3
      : 0;
  const homeScore = prediction.home_score === fixture.home_score ? 1 : 0;
  const awayScore = prediction.away_score === fixture.away_score ? 1 : 0;
  const goalDifference =
    prediction.home_score - prediction.away_score ===
    fixture.home_score - fixture.away_score
      ? 1
      : 0;
  return {
    exactScore,
    correctResult,
    homeScore,
    awayScore,
    goalDifference,
    total: exactScore + correctResult + homeScore + awayScore + goalDifference,
  };
}

export async function createPredictionService(
  context: PredictionServiceContext,
  input: PredictionWriteInput,
) {
  const fixture = await findFixtureForPrediction(context.db, input.fixtureId);
  assert(fixture !== null, "Fixture not found.", context.correlationId);
  assert(
    !isLocked(fixture, context.now),
    "Predictions are locked at kickoff.",
    context.correlationId,
  );
  const existing = await findPrediction(
    context.db,
    context.actorUserId,
    input.fixtureId,
  );
  assert(
    existing === null,
    "Prediction already exists for this fixture.",
    context.correlationId,
  );
  return toPredictionDto(
    await createPrediction(
      context.db,
      randomUUID(),
      context.actorUserId,
      input,
      context.now,
    ),
  );
}

export async function updatePredictionService(
  context: PredictionServiceContext,
  input: PredictionWriteInput,
) {
  const fixture = await findFixtureForPrediction(context.db, input.fixtureId);
  assert(fixture !== null, "Fixture not found.", context.correlationId);
  assert(
    !isLocked(fixture, context.now),
    "Predictions are locked at kickoff.",
    context.correlationId,
  );
  const existing = await findPrediction(
    context.db,
    context.actorUserId,
    input.fixtureId,
  );
  assert(existing !== null, "Prediction not found.", context.correlationId);
  const updated = await updatePrediction(
    context.db,
    context.actorUserId,
    input,
    context.now,
  );
  assert(updated !== null, "Prediction update failed.", context.correlationId);
  return toPredictionDto(updated);
}

export async function listUserPredictionsService(
  context: PredictionServiceContext,
  query: PredictionListQuery,
) {
  const result = await listUserPredictions(
    context.db,
    context.actorUserId,
    query,
  );
  return { rows: result.rows.map(toPredictionDto), total: result.total };
}

export async function listFixturePredictionsService(
  context: PredictionServiceContext,
  fixtureId: string,
  query: PredictionListQuery,
) {
  const result = await listFixturePredictions(context.db, fixtureId, query);
  return { rows: result.rows.map(toPredictionDto), total: result.total };
}

export async function recalculateFixtureScoresService(
  context: PredictionServiceContext,
  fixtureId: string,
) {
  const fixture = await findFixtureForPrediction(context.db, fixtureId);
  assert(fixture !== null, "Fixture not found.", context.correlationId);
  assert(
    (fixture.status === "completed" || fixture.status === "abandoned") &&
      fixture.home_score !== null &&
      fixture.away_score !== null,
    "Only completed or scored abandoned fixtures can be scored.",
    context.correlationId,
  );
  const predictions = await listFixturePredictionsForScoring(
    context.db,
    fixtureId,
  );
  for (const prediction of predictions) {
    const breakdown = scorePrediction(prediction, fixture);
    await upsertPredictionScore(context.db, {
      id: randomUUID(),
      predictionId: prediction.id,
      userId: prediction.user_id,
      fixtureId: fixture.id,
      competitionId: fixture.competition_id,
      seasonId: fixture.season_id,
      roundId: fixture.round_id,
      scoredAt: context.now,
      totalPoints: breakdown.total,
      exactScorePoints: breakdown.exactScore,
      correctResultPoints: breakdown.correctResult,
      homeScorePoints: breakdown.homeScore,
      awayScorePoints: breakdown.awayScore,
      goalDifferencePoints: breakdown.goalDifference,
      breakdownJson: JSON.stringify(breakdown),
    });
  }
  return { scoredPredictions: predictions.length };
}

function toLeaderboardEntry(
  row: LeaderboardRow,
  index: number,
): LeaderboardEntry {
  return {
    rank: index + 1,
    movement: null,
    userId: row.user_id,
    email: row.email,
    displayName: row.display_name,
    totalPoints: row.total_points,
    exactScores: row.exact_scores,
    correctResults: row.correct_results,
    predictionsScored: row.predictions_scored,
    lastScoredAt: row.last_scored_at,
  };
}

export async function listLeaderboardService(
  context: PredictionServiceContext,
  query: PredictionListQuery,
) {
  const result = await listRankings(context.db, query);
  return {
    rows: result.rows.map(toLeaderboardEntry),
    total: result.total,
  };
}
