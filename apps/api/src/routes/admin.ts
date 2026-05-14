import { Hono } from "hono";
import { z } from "zod";
import type { HonoEnv } from "../env";
import { requireRole } from "../middleware/auth";
import { requireDb } from "../lib/db";
import { created, ok, paginated } from "../lib/response";
import { AuthenticationError, ValidationError } from "../lib/errors";
import {
  CreateCompetitionSchema,
  CreateSeasonSchema,
  CreateTeamSchema,
  UpdateCompetitionSchema,
  UpdateSeasonSchema,
  UpdateTeamSchema,
} from "../admin/schemas";
import {
  archiveCompetitionService,
  archiveTeamService,
  activateSeasonService,
  createCompetitionService,
  createSeasonService,
  createTeamService,
  listCompetitionsService,
  listTeamsService,
  updateCompetitionService,
  updateSeasonService,
  updateTeamService,
  type ServiceContext,
} from "../admin/service";

const PaginationQuerySchema = z.object({
  page: z.coerce.number().int().positive().default(1),
  limit: z.coerce.number().int().positive().max(100).default(25),
});

const ActivateSeasonSchema = z.object({
  competitionId: z.string().min(1),
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

adminRoutes.get("/competitions", async (c) => {
  const pagination = parseQuery(PaginationQuerySchema, {
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
  const pagination = parseQuery(PaginationQuerySchema, {
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
