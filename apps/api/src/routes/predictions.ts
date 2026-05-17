import { Hono } from "hono";
import { z } from "zod";
import type { HonoEnv } from "../env";
import { requireAuth, requireRole } from "../middleware/auth";
import { requireDb } from "../lib/db";
import { created, ok, paginated } from "../lib/response";
import { AuthenticationError, ValidationError } from "../lib/errors";
import {
  PredictionListQuerySchema,
  PredictionWriteSchema,
  type PredictionListQuery,
} from "../predictions/schemas";
import {
  createPredictionService,
  listFixturePredictionsService,
  listLeaderboardService,
  listUserPredictionsService,
  recalculateFixtureScoresService,
  updatePredictionService,
  type PredictionServiceContext,
} from "../predictions/service";

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

async function makeContext(
  c: Parameters<typeof ok>[0],
): Promise<PredictionServiceContext> {
  const user = c.var.user;
  if (user === undefined) throw new AuthenticationError();
  return {
    db: await requireDb(c),
    now: new Date().toISOString(),
    correlationId: c.var.correlationId ?? "unknown",
    actorUserId: user.userId,
  };
}

function listQuery(c: Parameters<typeof ok>[0]) {
  const query = parseQuery(PredictionListQuerySchema, {
    page: c.req.query("page"),
    limit: c.req.query("limit"),
    competitionId: c.req.query("competitionId"),
    roundId: c.req.query("roundId"),
    privateLeagueId: c.req.query("privateLeagueId"),
    month: c.req.query("month"),
  });
  return {
    ...query,
    page: query.page ?? 1,
    limit: query.limit ?? 25,
  } satisfies PredictionListQuery;
}

function meta(page: number, limit: number, total: number) {
  return { page, limit, total, hasMore: page * limit < total };
}

export const predictionRoutes = new Hono<HonoEnv>();

predictionRoutes.use("*", requireAuth());

predictionRoutes.post("/", async (c) => {
  const input = parseBody(PredictionWriteSchema, await c.req.json());
  return created(c, await createPredictionService(await makeContext(c), input));
});

predictionRoutes.patch("/", async (c) => {
  const input = parseBody(PredictionWriteSchema, await c.req.json());
  return ok(c, await updatePredictionService(await makeContext(c), input));
});

predictionRoutes.get("/me", async (c) => {
  const query = listQuery(c);
  const result = await listUserPredictionsService(await makeContext(c), query);
  return paginated(c, result.rows, meta(query.page, query.limit, result.total));
});

predictionRoutes.get("/fixtures/:fixtureId", async (c) => {
  const query = listQuery(c);
  const result = await listFixturePredictionsService(
    await makeContext(c),
    c.req.param("fixtureId"),
    query,
  );
  return paginated(c, result.rows, meta(query.page, query.limit, result.total));
});

predictionRoutes.post(
  "/fixtures/:fixtureId/recalculate",
  requireRole("admin"),
  async (c) => {
    return ok(
      c,
      await recalculateFixtureScoresService(
        await makeContext(c),
        c.req.param("fixtureId"),
      ),
    );
  },
);

predictionRoutes.get("/leaderboards", async (c) => {
  const query = listQuery(c);
  const result = await listLeaderboardService(await makeContext(c), query);
  return paginated(c, result.rows, meta(query.page, query.limit, result.total));
});
