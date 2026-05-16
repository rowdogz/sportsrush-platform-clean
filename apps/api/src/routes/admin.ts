import { Hono } from "hono";
import { z } from "zod";
import type { HonoEnv } from "../env";
import { requireRole } from "../middleware/auth";
import { requireDb } from "../lib/db";
import { created, ok, paginated } from "../lib/response";
import { AuthenticationError, ValidationError } from "../lib/errors";
import {
  AliasLookupQuerySchema,
  CorrectResultSchema,
  CreateCompetitionSchema,
  CreateFixtureSchema,
  CreateRoundSchema,
  CreateSeasonSchema,
  CreateTeamAliasSchema,
  CreateTeamSchema,
  EnterResultSchema,
  FixtureListQuerySchema,
  FixtureStatusUpdateSchema,
  UpdateCompetitionSchema,
  UpdateFixtureSchema,
  UpdateRoundSchema,
  UpdateSeasonSchema,
  UpdateTeamAliasSchema,
  UpdateTeamSchema,
} from "../admin/schemas";
import {
  archiveCompetitionService,
  archiveTeamService,
  activateSeasonService,
  correctFixtureResultService,
  createAliasService,
  createCompetitionService,
  createFixtureService,
  createRoundService,
  createSeasonService,
  createTeamService,
  deleteAliasService,
  enterFixtureResultService,
  getFixtureService,
  listAdminUsersService,
  listAdminAuditEventsService,
  listAliasesService,
  listCompetitionsService,
  listFixturesService,
  listRoundsService,
  listSeasonsService,
  listTeamsService,
  lookupAliasService,
  reactivateAdminUserService,
  suspendAdminUserService,
  transitionFixtureService,
  updateAdminUserRoleService,
  updateAdminUserStatusService,
  updateAliasService,
  updateCompetitionService,
  updateFixtureService,
  updateRoundService,
  updateSeasonService,
  updateTeamService,
  type ServiceContext,
} from "../admin/service";

const PaginationQuerySchema = z.object({
  page: z.coerce.number().int().positive().default(1),
  limit: z.coerce.number().int().positive().max(100).default(25),
});

type Pagination = {
  readonly page: number;
  readonly limit: number;
};

function parsePagination(raw: Record<string, string | undefined>): Pagination {
  const parsed = parseQuery(PaginationQuerySchema, raw);
  return {
    page: parsed.page ?? 1,
    limit: parsed.limit ?? 25,
  };
}

const ActivateSeasonSchema = z.object({
  competitionId: z.string().min(1),
});

const SeasonListQuerySchema = z.object({
  competitionId: z.string().min(1).optional(),
  search: z.string().min(1).optional(),
});

const UserListQuerySchema = z.object({
  search: z.string().min(1).optional(),
  role: z.enum(["user", "admin", "superadmin"]).optional(),
  isActive: z.enum(["true", "false"]).optional(),
});

const AuditEventListQuerySchema = z.object({
  actorUserId: z.string().min(1).optional(),
  entityType: z.string().min(1).optional(),
  entityId: z.string().min(1).optional(),
  action: z.string().min(1).optional(),
  dateFrom: z.string().min(1).optional(),
  dateTo: z.string().min(1).optional(),
});

const UpdateUserRoleSchema = z.object({
  role: z.enum(["user", "admin", "superadmin"]),
});

const UpdateUserStatusSchema = z.object({
  isActive: z.boolean(),
});

const RoundListQuerySchema = z.object({
  seasonId: z.string().min(1),
});

const FixtureTransitionSchema = FixtureStatusUpdateSchema.extend({
  preserveScores: z.boolean().optional(),
  partialHomeScore: z.number().int().nonnegative().optional(),
  partialAwayScore: z.number().int().nonnegative().optional(),
});

function parseBody<T>(schema: z.ZodType<T>, raw: unknown): T {
  const parsed = schema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid request body",
      parsed.error.flatten().fieldErrors,
    );
  }
  return parsed.data;
}

function parseQuery<T>(
  schema: z.ZodType<T>,
  raw: Record<string, string | undefined>,
): T {
  const parsed = schema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid query parameters",
      parsed.error.flatten().fieldErrors,
    );
  }
  return parsed.data;
}

function paginationMeta(page: number, limit: number, total: number) {
  return {
    page,
    limit,
    total,
    hasMore: page * limit < total,
  };
}

async function makeServiceContext(
  c: Parameters<typeof ok>[0],
): Promise<ServiceContext> {
  const user = c.var.user;
  if (user === undefined) throw new AuthenticationError();
  return {
    db: await requireDb(c),
    now: new Date().toISOString(),
    correlationId: c.var.correlationId ?? "unknown",
    actorUserId: user.userId,
  };
}

export const adminRoutes = new Hono<HonoEnv>();

adminRoutes.use("*", requireRole("admin"));

adminRoutes.get("/users", async (c) => {
  const pagination = parsePagination({
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  });
  const query = parseQuery(UserListQuerySchema, {
    search: c.req.query("search"),
    role: c.req.query("role"),
    isActive: c.req.query("isActive"),
  });
  const context = await makeServiceContext(c);
  const result = await listAdminUsersService(context, pagination, {
    search: query.search,
    role: query.role,
    isActive:
      query.isActive === undefined ? undefined : query.isActive === "true",
  });
  return paginated(
    c,
    result.rows,
    paginationMeta(pagination.page, pagination.limit, result.total),
  );
});

adminRoutes.get("/audit-events", async (c) => {
  const pagination = parsePagination({
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  });
  const filters = parseQuery(AuditEventListQuerySchema, {
    actorUserId: c.req.query("actorUserId"),
    entityType: c.req.query("entityType"),
    entityId: c.req.query("entityId"),
    action: c.req.query("action"),
    dateFrom: c.req.query("dateFrom"),
    dateTo: c.req.query("dateTo"),
  });
  const context = await makeServiceContext(c);
  const result = await listAdminAuditEventsService(
    context,
    pagination,
    filters,
  );
  return paginated(
    c,
    result.rows,
    paginationMeta(pagination.page, pagination.limit, result.total),
  );
});

adminRoutes.patch("/users/:id/role", async (c) => {
  const input = parseBody(UpdateUserRoleSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(
    c,
    await updateAdminUserRoleService(context, c.req.param("id"), input.role),
  );
});

adminRoutes.patch("/users/:id/status", async (c) => {
  const input = parseBody(UpdateUserStatusSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(
    c,
    await updateAdminUserStatusService(
      context,
      c.req.param("id"),
      input.isActive,
    ),
  );
});

adminRoutes.post("/users/:id/suspend", async (c) => {
  const context = await makeServiceContext(c);
  return ok(c, await suspendAdminUserService(context, c.req.param("id")));
});

adminRoutes.post("/users/:id/reactivate", async (c) => {
  const context = await makeServiceContext(c);
  return ok(c, await reactivateAdminUserService(context, c.req.param("id")));
});

adminRoutes.get("/competitions", async (c) => {
  const pagination = parsePagination({
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  });
  const context = await makeServiceContext(c);
  const result = await listCompetitionsService(context, pagination);
  return paginated(
    c,
    result.rows,
    paginationMeta(pagination.page, pagination.limit, result.total),
  );
});

adminRoutes.post("/competitions", async (c) => {
  const input = parseBody(CreateCompetitionSchema, await c.req.json());
  const context = await makeServiceContext(c);
  const competition = await createCompetitionService(context, input);
  return created(c, competition);
});

adminRoutes.patch("/competitions/:id", async (c) => {
  const input = parseBody(UpdateCompetitionSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(
    c,
    await updateCompetitionService(context, c.req.param("id"), input),
  );
});

adminRoutes.post("/competitions/:id/archive", async (c) => {
  const context = await makeServiceContext(c);
  return ok(c, await archiveCompetitionService(context, c.req.param("id")));
});

adminRoutes.get("/seasons", async (c) => {
  const pagination = parsePagination({
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  });
  const filters = parseQuery(SeasonListQuerySchema, {
    competitionId: c.req.query("competitionId"),
    search: c.req.query("search"),
  });
  const context = await makeServiceContext(c);
  const result = await listSeasonsService(context, pagination, filters);
  return paginated(
    c,
    result.rows,
    paginationMeta(pagination.page, pagination.limit, result.total),
  );
});

adminRoutes.post("/seasons", async (c) => {
  const input = parseBody(CreateSeasonSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return created(c, await createSeasonService(context, input));
});

adminRoutes.patch("/seasons/:id", async (c) => {
  const input = parseBody(UpdateSeasonSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(c, await updateSeasonService(context, c.req.param("id"), input));
});

adminRoutes.post("/seasons/:id/activate", async (c) => {
  const input = parseBody(ActivateSeasonSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(
    c,
    await activateSeasonService(
      context,
      c.req.param("id"),
      input.competitionId,
    ),
  );
});

adminRoutes.get("/teams", async (c) => {
  const pagination = parsePagination({
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  });
  const context = await makeServiceContext(c);
  const result = await listTeamsService(context, pagination);
  return paginated(
    c,
    result.rows,
    paginationMeta(pagination.page, pagination.limit, result.total),
  );
});

adminRoutes.post("/teams", async (c) => {
  const input = parseBody(CreateTeamSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return created(c, await createTeamService(context, input));
});

adminRoutes.patch("/teams/:id", async (c) => {
  const input = parseBody(UpdateTeamSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(c, await updateTeamService(context, c.req.param("id"), input));
});

adminRoutes.post("/teams/:id/archive", async (c) => {
  const context = await makeServiceContext(c);
  return ok(c, await archiveTeamService(context, c.req.param("id")));
});

adminRoutes.get("/team-aliases", async (c) => {
  const query = parseQuery(AliasLookupQuerySchema, {
    sportId: c.req.query("sportId"),
    source: c.req.query("source"),
    alias: c.req.query("alias"),
  });
  const context = await makeServiceContext(c);
  if (query.source !== undefined && query.alias !== undefined) {
    return ok(
      c,
      await lookupAliasService(
        context,
        query.sportId,
        query.source,
        query.alias,
      ),
    );
  }
  return ok(
    c,
    await listAliasesService(context, query.sportId, query.source, query.alias),
  );
});

adminRoutes.post("/team-aliases", async (c) => {
  const input = parseBody(CreateTeamAliasSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return created(
    c,
    await createAliasService(context, {
      ...input,
      source: input.source ?? "manual",
      priority: input.priority ?? 100,
    }),
  );
});

adminRoutes.patch("/team-aliases/:id", async (c) => {
  const input = parseBody(UpdateTeamAliasSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(c, await updateAliasService(context, c.req.param("id"), input));
});

adminRoutes.delete("/team-aliases/:id", async (c) => {
  const context = await makeServiceContext(c);
  await deleteAliasService(context, c.req.param("id"));
  return ok(c, { deleted: true });
});

adminRoutes.get("/rounds", async (c) => {
  const query = parseQuery(RoundListQuerySchema, {
    seasonId: c.req.query("seasonId"),
  });
  const context = await makeServiceContext(c);
  return ok(c, await listRoundsService(context, query.seasonId));
});

adminRoutes.post("/rounds", async (c) => {
  const input = parseBody(CreateRoundSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return created(c, await createRoundService(context, input));
});

adminRoutes.patch("/rounds/:id", async (c) => {
  const input = parseBody(UpdateRoundSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(c, await updateRoundService(context, c.req.param("id"), input));
});

adminRoutes.get("/fixtures", async (c) => {
  const pagination = parsePagination({
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  });
  const filters = parseQuery(FixtureListQuerySchema, {
    competitionId: c.req.query("competitionId"),
    seasonId: c.req.query("seasonId"),
    round: c.req.query("round"),
    status: c.req.query("status"),
    dateFrom: c.req.query("dateFrom"),
    dateTo: c.req.query("dateTo"),
  });
  const context = await makeServiceContext(c);
  const result = await listFixturesService(context, filters, pagination);
  return paginated(
    c,
    result.rows,
    paginationMeta(pagination.page, pagination.limit, result.total),
  );
});

adminRoutes.post("/fixtures", async (c) => {
  const input = parseBody(CreateFixtureSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return created(
    c,
    await createFixtureService(context, {
      ...input,
      status: input.status ?? "scheduled",
    }),
  );
});

adminRoutes.get("/fixtures/:id", async (c) => {
  const context = await makeServiceContext(c);
  return ok(c, await getFixtureService(context, c.req.param("id")));
});

adminRoutes.patch("/fixtures/:id", async (c) => {
  const input = parseBody(UpdateFixtureSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(c, await updateFixtureService(context, c.req.param("id"), input));
});

adminRoutes.post("/fixtures/:id/transition", async (c) => {
  const input = parseBody(FixtureTransitionSchema, await c.req.json());
  const context = await makeServiceContext(c);
  const options = {
    ...(input.preserveScores !== undefined
      ? { preserveScores: input.preserveScores }
      : {}),
    ...(input.partialHomeScore !== undefined
      ? { partialHomeScore: input.partialHomeScore }
      : {}),
    ...(input.partialAwayScore !== undefined
      ? { partialAwayScore: input.partialAwayScore }
      : {}),
  };

  return ok(
    c,
    await transitionFixtureService(
      context,
      c.req.param("id"),
      input.status,
      options,
    ),
  );
});

adminRoutes.post("/fixtures/:id/result", async (c) => {
  const input = parseBody(EnterResultSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(
    c,
    await enterFixtureResultService(
      context,
      c.req.param("id"),
      input.homeScore,
      input.awayScore,
      input.resultSource,
    ),
  );
});

adminRoutes.post("/fixtures/:id/correct-result", async (c) => {
  const input = parseBody(CorrectResultSchema, await c.req.json());
  const context = await makeServiceContext(c);
  return ok(
    c,
    await correctFixtureResultService(
      context,
      c.req.param("id"),
      input.homeScore,
      input.awayScore,
      input.reason,
    ),
  );
});
