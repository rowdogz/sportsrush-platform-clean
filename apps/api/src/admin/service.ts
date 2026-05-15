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
  createAuditEvent,
  createFixture,
  createRound,
  createSeason,
  createTeam,
  createTeamAlias,
  deleteTeamAlias,
  findAliasBySource,
  findAdminUserById,
  findCompetitionById,
  findFixtureById,
  findRoundById,
  findSeasonById,
  findTeamAliasById,
  findTeamById,
  insertResultCorrection,
  listAliasesBySource,
  listAdminUsers,
  listCompetitions,
  listFixtures,
  listSeasons,
  markActiveSeason,
  setFixtureStatus,
  enterFixtureResult,
  updateAdminUserRole,
  updateAdminUserStatus,
  updateCompetition,
  updateFixture,
  updateRound,
  updateSeason,
  updateTeam,
  updateTeamAlias,
  type FixtureRow,
  type Pagination,
  type SeasonListFilters,
  type AdminUserRole,
  type UserListFilters,
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
import {
  normalizeAlias,
  normalizeSlug,
  normalizeSource,
} from "./normalization";

const TRANSITIONS: Record<FixtureStatus, readonly FixtureStatus[]> = {
  scheduled: ["postponed", "cancelled", "completed", "abandoned", "void"],
  postponed: ["scheduled", "cancelled", "void"],
  abandoned: ["completed", "void"],
  completed: [],
  cancelled: [],
  void: [],
};

const ADMIN_USER_ROLES: readonly AdminUserRole[] = [
  "user",
  "admin",
  "superadmin",
];

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

async function writeAuditEvent(
  context: ServiceContext,
  action: string,
  targetType: string,
  targetId: string | null,
  before: unknown,
  after: unknown,
) {
  await createAuditEvent(
    context.db,
    randomUUID(),
    {
      actorUserId: context.actorUserId,
      action,
      targetType,
      targetId,
      before: toAuditMetadata(before),
      after: toAuditMetadata(after),
    },
    context.now,
  );
}

function toAuditMetadata(value: unknown): unknown {
  if (value === null || value === undefined) return null;
  return JSON.parse(JSON.stringify(value)) as unknown;
}

function logCorrelation(correlationId: string, message: string): void {
  console.error(`[${correlationId}] ${message}`);
}

function assert(
  condition: unknown,
  message: string,
  correlationId: string,
): asserts condition {
  if (!condition) {
    logCorrelation(correlationId, message);
    throw new AdminDomainError(message, correlationId);
  }
}

export function getAllowedFixtureTransitions(
  status: FixtureStatus,
): readonly FixtureStatus[] {
  return TRANSITIONS[status] ?? [];
}

export async function createCompetitionService(
  context: ServiceContext,
  input: CreateCompetitionInput,
) {
  const competition = await createCompetition(
    context.db,
    randomUUID(),
    { ...input, slug: normalizeSlug(input.slug) },
    context.now,
  );
  await writeAuditEvent(
    context,
    "competition.create",
    "competition",
    competition.id,
    null,
    competition,
  );
  return competition;
}

export async function updateCompetitionService(
  context: ServiceContext,
  id: string,
  input: UpdateCompetitionInput,
) {
  const before = await findCompetitionById(context.db, id);
  const competition = await updateCompetition(
    context.db,
    id,
    {
      ...input,
      slug: input.slug === undefined ? undefined : normalizeSlug(input.slug),
    },
    context.now,
  );
  await writeAuditEvent(
    context,
    "competition.update",
    "competition",
    id,
    before,
    competition,
  );
  return competition;
}

export async function archiveCompetitionService(
  context: ServiceContext,
  id: string,
) {
  const before = await findCompetitionById(context.db, id);
  const competition = await updateCompetition(
    context.db,
    id,
    { isActive: false },
    context.now,
  );
  await writeAuditEvent(
    context,
    "competition.archive",
    "competition",
    id,
    before,
    competition,
  );
  return competition;
}

export async function listCompetitionsService(
  context: ServiceContext,
  pagination: Pagination,
) {
  return listCompetitions(context.db, pagination);
}

export async function listAdminUsersService(
  context: ServiceContext,
  pagination: Pagination,
  filters: UserListFilters,
) {
  return listAdminUsers(context.db, pagination, filters);
}

function assertAdminUserRole(
  role: string,
  correlationId: string,
): asserts role is AdminUserRole {
  assert(
    ADMIN_USER_ROLES.includes(role as AdminUserRole),
    "Invalid user role.",
    correlationId,
  );
}

async function assertAdminUserExists(context: ServiceContext, userId: string) {
  const user = await findAdminUserById(context.db, userId);
  assert(user !== null, "User not found.", context.correlationId);
  return user;
}

function assertNotRemovingOwnAdminAccess(
  context: ServiceContext,
  userId: string,
  nextRole: AdminUserRole,
) {
  assert(
    userId !== context.actorUserId ||
      nextRole === "admin" ||
      nextRole === "superadmin",
    "Admins cannot remove their own admin access.",
    context.correlationId,
  );
}

function assertNotDisablingSelf(context: ServiceContext, userId: string) {
  assert(
    userId !== context.actorUserId,
    "Admins cannot deactivate or suspend their own account.",
    context.correlationId,
  );
}

export async function updateAdminUserRoleService(
  context: ServiceContext,
  userId: string,
  role: string,
) {
  assertAdminUserRole(role, context.correlationId);
  const before = await assertAdminUserExists(context, userId);
  assertNotRemovingOwnAdminAccess(context, userId, role);
  const user = await updateAdminUserRole(context.db, userId, role, context.now);
  await writeAuditEvent(
    context,
    "user.role.update",
    "user",
    userId,
    before,
    user,
  );
  return user;
}

export async function updateAdminUserStatusService(
  context: ServiceContext,
  userId: string,
  isActive: boolean,
  action = "user.status.update",
) {
  const before = await assertAdminUserExists(context, userId);
  if (!isActive) {
    assertNotDisablingSelf(context, userId);
  }
  const user = await updateAdminUserStatus(
    context.db,
    userId,
    isActive,
    context.now,
  );
  await writeAuditEvent(context, action, "user", userId, before, user);
  return user;
}

export async function suspendAdminUserService(
  context: ServiceContext,
  userId: string,
) {
  return updateAdminUserStatusService(context, userId, false, "user.suspend");
}

export async function reactivateAdminUserService(
  context: ServiceContext,
  userId: string,
) {
  return updateAdminUserStatusService(context, userId, true, "user.reactivate");
}

export async function createSeasonService(
  context: ServiceContext,
  input: CreateSeasonInput,
) {
  const season = await createSeason(
    context.db,
    randomUUID(),
    input,
    context.now,
  );
  await writeAuditEvent(
    context,
    "season.create",
    "season",
    season.id,
    null,
    season,
  );
  return season;
}

export async function listSeasonsService(
  context: ServiceContext,
  pagination: Pagination,
  filters: SeasonListFilters,
) {
  return listSeasons(context.db, pagination, filters);
}

export async function updateSeasonService(
  context: ServiceContext,
  id: string,
  input: UpdateSeasonInput,
) {
  const before = await findSeasonById(context.db, id);
  const season = await updateSeason(context.db, id, input, context.now);
  await writeAuditEvent(context, "season.update", "season", id, before, season);
  return season;
}

export async function activateSeasonService(
  context: ServiceContext,
  seasonId: string,
  competitionId: string,
) {
  const before = await findSeasonById(context.db, seasonId);
  const season = await markActiveSeason(
    context.db,
    seasonId,
    competitionId,
    context.now,
  );
  await writeAuditEvent(
    context,
    "season.activate",
    "season",
    seasonId,
    before,
    season,
  );
  return season;
}

export async function createTeamService(
  context: ServiceContext,
  input: CreateTeamInput,
) {
  const team = await createTeam(
    context.db,
    randomUUID(),
    { ...input, slug: normalizeSlug(input.slug) },
    context.now,
  );
  await writeAuditEvent(context, "team.create", "team", team.id, null, team);
  return team;
}

export async function updateTeamService(
  context: ServiceContext,
  id: string,
  input: UpdateTeamInput,
) {
  const before = await findTeamById(context.db, id);
  const team = await updateTeam(
    context.db,
    id,
    {
      ...input,
      slug: input.slug === undefined ? undefined : normalizeSlug(input.slug),
    },
    context.now,
  );
  await writeAuditEvent(context, "team.update", "team", id, before, team);
  return team;
}

export async function archiveTeamService(context: ServiceContext, id: string) {
  const before = await findTeamById(context.db, id);
  const team = await updateTeam(
    context.db,
    id,
    { isActive: false },
    context.now,
  );
  await writeAuditEvent(context, "team.archive", "team", id, before, team);
  return team;
}

export async function listTeamsService(
  context: ServiceContext,
  pagination: Pagination,
) {
  const rows = await context.db.queryAll(
    "SELECT * FROM teams ORDER BY name LIMIT ? OFFSET ?",
    [pagination.limit, (pagination.page - 1) * pagination.limit],
  );
  const total = await context.db.queryOne<{ count: number }>(
    "SELECT COUNT(*) AS count FROM teams",
  );
  return { rows, total: total?.count ?? 0 };
}

export async function createAliasService(
  context: ServiceContext,
  input: CreateTeamAliasInput,
) {
  const normalizedAlias = normalizeAlias(input.normalizedAlias ?? input.alias);
  const normalizedSource = normalizeSource(input.source);
  const existing = await listAliasesBySource(
    context.db,
    input.sportId,
    normalizedSource,
    normalizedAlias,
  );
  assert(
    existing.length === 0,
    `Alias already exists for source=${normalizedSource} alias=${normalizedAlias}`,
    context.correlationId,
  );
  const alias = await createTeamAlias(
    context.db,
    randomUUID(),
    input,
    normalizedAlias,
    normalizedSource,
    context.now,
  );
  await writeAuditEvent(
    context,
    "team_alias.create",
    "team_alias",
    alias.id,
    null,
    alias,
  );
  return alias;
}

export async function updateAliasService(
  context: ServiceContext,
  id: string,
  input: UpdateTeamAliasInput,
) {
  const before = await findTeamAliasById(context.db, id);
  const normalizedAlias =
    input.alias === undefined && input.normalizedAlias === undefined
      ? undefined
      : normalizeAlias(input.normalizedAlias ?? input.alias ?? "");
  const normalizedSource =
    input.source === undefined ? undefined : normalizeSource(input.source);
  const alias = await updateTeamAlias(
    context.db,
    id,
    input,
    normalizedAlias,
    normalizedSource,
    context.now,
  );
  await writeAuditEvent(
    context,
    "team_alias.update",
    "team_alias",
    id,
    before,
    alias,
  );
  return alias;
}

export async function deleteAliasService(context: ServiceContext, id: string) {
  const before = await findTeamAliasById(context.db, id);
  await deleteTeamAlias(context.db, id);
  await writeAuditEvent(
    context,
    "team_alias.delete",
    "team_alias",
    id,
    before,
    null,
  );
}

export async function lookupAliasService(
  context: ServiceContext,
  sportId: string,
  source: string,
  alias: string,
) {
  return findAliasBySource(
    context.db,
    sportId,
    normalizeSource(source),
    normalizeAlias(alias),
  );
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

export async function createRoundService(
  context: ServiceContext,
  input: CreateRoundInput,
) {
  const round = await createRound(context.db, randomUUID(), input, context.now);
  await writeAuditEvent(
    context,
    "round.create",
    "round",
    round.id,
    null,
    round,
  );
  return round;
}

export async function updateRoundService(
  context: ServiceContext,
  id: string,
  input: UpdateRoundInput,
) {
  const before = await findRoundById(context.db, id);
  const round = await updateRound(context.db, id, input, context.now);
  await writeAuditEvent(context, "round.update", "round", id, before, round);
  return round;
}

export async function listRoundsService(
  context: ServiceContext,
  seasonId: string,
) {
  return context.db.queryAll(
    "SELECT * FROM rounds WHERE season_id = ? ORDER BY display_order",
    [seasonId],
  );
}

export async function getFixtureService(context: ServiceContext, id: string) {
  return findFixtureById(context.db, id);
}

export async function createFixtureService(
  context: ServiceContext,
  input: CreateFixtureInput,
) {
  const fixture = await createFixture(
    context.db,
    randomUUID(),
    input,
    context.now,
  );
  await writeAuditEvent(
    context,
    "fixture.create",
    "fixture",
    fixture.id,
    null,
    fixture,
  );
  return fixture;
}

export async function updateFixtureService(
  context: ServiceContext,
  id: string,
  input: UpdateFixtureInput,
) {
  const before = await findFixtureById(context.db, id);
  const fixture = await updateFixture(context.db, id, input, context.now);
  await writeAuditEvent(
    context,
    "fixture.update",
    "fixture",
    id,
    before,
    fixture,
  );
  return fixture;
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
  assert(
    nextStatus !== "completed",
    "Use enterFixtureResultService to complete fixtures",
    context.correlationId,
  );
  const hasPartialScores =
    options.partialHomeScore !== undefined ||
    options.partialAwayScore !== undefined;
  assert(
    !hasPartialScores || nextStatus === "abandoned",
    "Partial scores are only allowed for abandoned fixtures",
    context.correlationId,
  );
  const clearScores =
    nextStatus === "void" || nextStatus === "cancelled"
      ? true
      : hasPartialScores
        ? false
        : !options.preserveScores;
  let updated = await setFixtureStatus(
    context.db,
    fixture.id,
    nextStatus,
    context.now,
    clearScores,
  );
  if (hasPartialScores) {
    updated = await updateFixture(
      context.db,
      fixture.id,
      {
        homeScore: options.partialHomeScore,
        awayScore: options.partialAwayScore,
      },
      context.now,
    );
  }
  assert(updated !== null, "Failed to update fixture", context.correlationId);
  await writeAuditEvent(
    context,
    "fixture.status.transition",
    "fixture",
    fixture.id,
    fixture,
    updated,
  );
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
  await writeAuditEvent(
    context,
    "fixture.result.enter",
    "fixture",
    fixture.id,
    fixture,
    updated,
  );
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
  assert(
    fixture.status === "completed",
    "Only completed fixtures can be corrected",
    context.correlationId,
  );
  assert(
    reason.trim().length > 0,
    "Correction reason required",
    context.correlationId,
  );
  if (
    fixture.home_score === correctedHomeScore &&
    fixture.away_score === correctedAwayScore
  )
    return fixture;
  const duplicate = await context.db.queryOne<{ id: string }>(
    `SELECT id FROM result_corrections
     WHERE fixture_id = ? AND corrected_home_score = ? AND corrected_away_score = ? AND reason = ?
     LIMIT 1`,
    [fixture.id, correctedHomeScore, correctedAwayScore, reason],
  );
  if (duplicate !== null) {
    const existingFixture = await findFixtureById(context.db, fixture.id);
    assert(
      existingFixture !== null,
      "Fixture not found",
      context.correlationId,
    );
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
    {
      homeScore: correctedHomeScore,
      awayScore: correctedAwayScore,
      status: "completed",
    },
    context.now,
  );
  assert(updated !== null, "Correction update failed", context.correlationId);
  await writeAuditEvent(
    context,
    "fixture.result.correct",
    "fixture",
    fixture.id,
    fixture,
    updated,
  );
  return updated;
}
