import { randomUUID } from "node:crypto";
import { AppError } from "../lib/errors";

type FixtureStatus =
  | "scheduled"
  | "postponed"
  | "completed"
  | "abandoned"
  | "cancelled"
  | "void";
import type { DbClient } from "../lib/db";
import {
  createCompetition,
  createFixture,
  createRound,
  createSeason,
  createTeam,
  createTeamAlias,
  deleteTeamAlias,
  findAliasBySource,
  findCompetitionById,
  findFixtureById,
  findRoundById,
  findSeasonById,
  findTeamAliasById,
  findTeamById,
  insertResultCorrection,
  listAliasesBySource,
  listCompetitions,
  listFixtures,
  markActiveSeason,
  setFixtureStatus,
  enterFixtureResult,
  updateCompetition,
  updateFixture,
  updateRound,
  updateSeason,
  updateTeam,
  updateTeamAlias,
  type FixtureRow,
  type Pagination,
} from "./repository";
import type {
  CreateCompetitionInput,
  CreateFixtureInput,
  CreateRoundInput,
  CreateSeasonInput,
  CreateTeamAliasInput,
  CreateTeamInput,
  FixtureListQuery,
  UpdateCompetitionInput,
  UpdateFixtureInput,
  UpdateRoundInput,
  UpdateSeasonInput,
  UpdateTeamAliasInput,
  UpdateTeamInput,
} from "./schemas";
import { normalizeAlias, normalizeSlug, normalizeSource } from "./normalization";

const TRANSITIONS: Record<FixtureStatus, readonly FixtureStatus[]> = {
  scheduled: ["postponed", "cancelled", "completed", "abandoned", "void"],
  postponed: ["scheduled", "cancelled", "void"],
  abandoned: ["completed", "void"],
  completed: [],
  cancelled: [],
  void: [],
};

export class AdminDomainError extends AppError {
  override readonly name = "AdminDomainError";
  readonly correlationId: string;

  constructor(message: string, correlationId: string) {
    super(422, "ADMIN_DOMAIN_ERROR", message, { correlationId });
    this.correlationId = correlationId;
  }
}

export type ServiceContext = {
  readonly db: DbClient;
  readonly now: string;
  readonly correlationId: string;
  readonly actorUserId: string;
  readonly actorDisplayName?: string;
};

export type TransitionOptions = {
  readonly preserveScores?: boolean;
  readonly partialHomeScore?: number;
  readonly partialAwayScore?: number;
};

function logCorrelation(correlationId: string, message: string): void {
  console.error(`[${correlationId}] ${message}`);
}

function assert(condition: unknown, message: string, correlationId: string): asserts condition {
  if (!condition) {
    logCorrelation(correlationId, message);
    throw new AdminDomainError(message, correlationId);
  }
}

export function getAllowedFixtureTransitions(status: FixtureStatus): readonly FixtureStatus[] {
  return TRANSITIONS[status] ?? [];
}

export async function createCompetitionService(context: ServiceContext, input: CreateCompetitionInput) {
  return createCompetition(
    context.db,
    randomUUID(),
    { ...input, slug: normalizeSlug(input.slug) },
    context.now,
  );
}

export async function updateCompetitionService(
  context: ServiceContext,
  id: string,
  input: UpdateCompetitionInput,
) {
  return updateCompetition(
    context.db,
    id,
    { ...input, slug: input.slug === undefined ? undefined : normalizeSlug(input.slug) },
    context.now,
  );
}

export async function archiveCompetitionService(context: ServiceContext, id: string) {
  return updateCompetition(context.db, id, { isActive: false }, context.now);
}

export async function listCompetitionsService(context: ServiceContext, pagination: Pagination) {
  return listCompetitions(context.db, pagination);
}

export async function createSeasonService(context: ServiceContext, input: CreateSeasonInput) {
  return createSeason(context.db, randomUUID(), input, context.now);
}

export async function updateSeasonService(context: ServiceContext, id: string, input: UpdateSeasonInput) {
  return updateSeason(context.db, id, input, context.now);
}

export async function activateSeasonService(
  context: ServiceContext,
  seasonId: string,
  competitionId: string,
) {
  return markActiveSeason(context.db, seasonId, competitionId, context.now);
}

export async function createTeamService(context: ServiceContext, input: CreateTeamInput) {
  return createTeam(
    context.db,
    randomUUID(),
    { ...input, slug: normalizeSlug(input.slug) },
    context.now,
  );
}

export async function updateTeamService(context: ServiceContext, id: string, input: UpdateTeamInput) {
  return updateTeam(
    context.db,
    id,
    { ...input, slug: input.slug === undefined ? undefined : normalizeSlug(input.slug) },
    context.now,
  );
}

export async function archiveTeamService(context: ServiceContext, id: string) {
  return updateTeam(context.db, id, { isActive: false }, context.now);
}

export async function listTeamsService(context: ServiceContext, pagination: Pagination) {
  const rows = await context.db.queryAll("SELECT * FROM teams ORDER BY name LIMIT ? OFFSET ?", [
    pagination.limit,
    (pagination.page - 1) * pagination.limit,
  ]);
  const total = await context.db.queryOne<{ count: number }>("SELECT COUNT(*) AS count FROM teams");
  return { rows, total: total?.count ?? 0 };
}

export async function createAliasService(context: ServiceContext, input: CreateTeamAliasInput) {
  const normalizedAlias = normalizeAlias(input.normalizedAlias ?? input.alias);
  const normalizedSource = normalizeSource(input.source);
  const existing = await listAliasesBySource(context.db, input.sportId, normalizedSource, normalizedAlias);
  assert(
    existing.length === 0,
    `Alias already exists for source=${normalizedSource} alias=${normalizedAlias}`,
    context.correlationId,
  );
  return createTeamAlias(context.db, randomUUID(), input, normalizedAlias, normalizedSource, context.now);
}

export async function updateAliasService(
  context: ServiceContext,
  id: string,
  input: UpdateTeamAliasInput,
) {
  const normalizedAlias = input.alias === undefined && input.normalizedAlias === undefined
    ? undefined
    : normalizeAlias(input.normalizedAlias ?? input.alias ?? "");
  const normalizedSource = input.source === undefined ? undefined : normalizeSource(input.source);
  return updateTeamAlias(context.db, id, input, normalizedAlias, normalizedSource, context.now);
}

export async function deleteAliasService(context: ServiceContext, id: string) {
  await deleteTeamAlias(context.db, id);
}

export async function lookupAliasService(
  context: ServiceContext,
  sportId: string,
  source: string,
  alias: string,
) {
  return findAliasBySource(context.db, sportId, normalizeSource(source), normalizeAlias(alias));
}

export async function listAliasesService(
  context: ServiceContext,
  sportId: string,
  source?: string,
  alias?: string,
) {
  return listAliasesBySource(
    context.db,
    sportId,
    source === undefined ? undefined : normalizeSource(source),
    alias === undefined ? undefined : normalizeAlias(alias),
  );
}

export async function createRoundService(context: ServiceContext, input: CreateRoundInput) {
  return createRound(context.db, randomUUID(), input, context.now);
}

export async function updateRoundService(context: ServiceContext, id: string, input: UpdateRoundInput) {
  return updateRound(context.db, id, input, context.now);
}

export async function listRoundsService(context: ServiceContext, seasonId: string) {
  return context.db.queryAll("SELECT * FROM rounds WHERE season_id = ? ORDER BY display_order", [seasonId]);
}

export async function getFixtureService(context: ServiceContext, id: string) {
  return findFixtureById(context.db, id);
}

export async function createFixtureService(context: ServiceContext, input: CreateFixtureInput) {
  return createFixture(context.db, randomUUID(), input, context.now);
}

export async function updateFixtureService(context: ServiceContext, id: string, input: UpdateFixtureInput) {
  return updateFixture(context.db, id, input, context.now);
}

export async function listFixturesService(
  context: ServiceContext,
  filters: FixtureListQuery,
  pagination: Pagination,
) {
  return listFixtures(context.db, filters, pagination);
}

export async function transitionFixtureService(
  context: ServiceContext,
  fixtureId: string,
  nextStatus: FixtureStatus,
  options: TransitionOptions = {},
): Promise<FixtureRow> {
  const fixture = await findFixtureById(context.db, fixtureId);
  assert(fixture !== null, "Fixture not found", context.correlationId);
  const allowed = TRANSITIONS[fixture.status] ?? [];
  assert(
    allowed.includes(nextStatus),
    `Invalid transition ${fixture.status} -> ${nextStatus}`,
    context.correlationId,
  );
  assert(nextStatus !== "completed", "Use enterFixtureResultService to complete fixtures", context.correlationId);
  const hasPartialScores = options.partialHomeScore !== undefined || options.partialAwayScore !== undefined;
  assert(
    !hasPartialScores || nextStatus === "abandoned",
    "Partial scores are only allowed for abandoned fixtures",
    context.correlationId,
  );
  const clearScores =
    nextStatus === "void" || nextStatus === "cancelled" ? true : hasPartialScores ? false : !options.preserveScores;
  let updated = await setFixtureStatus(context.db, fixture.id, nextStatus, context.now, clearScores);
  if (hasPartialScores) {
    updated = await updateFixture(
      context.db,
      fixture.id,
      { homeScore: options.partialHomeScore, awayScore: options.partialAwayScore },
      context.now,
    );
  }
  assert(updated !== null, "Failed to update fixture", context.correlationId);
  return updated;
}

export async function enterFixtureResultService(
  context: ServiceContext,
  fixtureId: string,
  homeScore: number,
  awayScore: number,
  resultSource?: string,
): Promise<FixtureRow> {
  const fixture = await findFixtureById(context.db, fixtureId);
  assert(fixture !== null, "Fixture not found", context.correlationId);
  if (fixture.status === "completed") {
    assert(
      fixture.home_score === homeScore && fixture.away_score === awayScore,
      "Completed fixtures require correction flow",
      context.correlationId,
    );
    return fixture;
  }
  assert(
    fixture.status === "scheduled" || fixture.status === "abandoned",
    `Cannot enter result for ${fixture.status} fixture`,
    context.correlationId,
  );
  const updated = await enterFixtureResult(
    context.db,
    fixture.id,
    homeScore,
    awayScore,
    resultSource ?? null,
    context.actorUserId,
    context.now,
  );
  assert(updated !== null, "Result update failed", context.correlationId);
  return updated;
}

export async function correctFixtureResultService(
  context: ServiceContext,
  fixtureId: string,
  correctedHomeScore: number,
  correctedAwayScore: number,
  reason: string,
): Promise<FixtureRow> {
  const fixture = await findFixtureById(context.db, fixtureId);
  assert(fixture !== null, "Fixture not found", context.correlationId);
  assert(fixture.status === "completed", "Only completed fixtures can be corrected", context.correlationId);
  assert(reason.trim().length > 0, "Correction reason required", context.correlationId);
  if (fixture.home_score === correctedHomeScore && fixture.away_score === correctedAwayScore) return fixture;
  const duplicate = await context.db.queryOne<{ id: string }>(
    `SELECT id FROM result_corrections
     WHERE fixture_id = ? AND corrected_home_score = ? AND corrected_away_score = ? AND reason = ?
     LIMIT 1`,
    [fixture.id, correctedHomeScore, correctedAwayScore, reason],
  );
  if (duplicate !== null) {
    const existingFixture = await findFixtureById(context.db, fixture.id);
    assert(existingFixture !== null, "Fixture not found", context.correlationId);
    return existingFixture;
  }
  await insertResultCorrection(
    context.db,
    randomUUID(),
    fixture,
    correctedHomeScore,
    correctedAwayScore,
    reason,
    context.actorUserId,
    context.actorDisplayName ?? null,
    context.now,
  );
  const updated = await updateFixture(
    context.db,
    fixture.id,
    { homeScore: correctedHomeScore, awayScore: correctedAwayScore, status: "completed" },
    context.now,
  );
  assert(updated !== null, "Correction update failed", context.correlationId);
  return updated;
}
