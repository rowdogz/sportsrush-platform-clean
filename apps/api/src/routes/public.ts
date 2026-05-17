import { Hono } from "hono";
import { z } from "zod";
import type { HonoEnv } from "../env";
import { requireDb } from "../lib/db";
import { ok, paginated } from "../lib/response";
import { ValidationError } from "../lib/errors";
import {
  PublicCompetitionListQuerySchema,
  PublicFixtureListQuerySchema,
  PublicIdParamSchema,
  PublicRoundListQuerySchema,
  PublicSeasonListQuerySchema,
  type PublicFixtureListQuery,
  type PublicPaginationQuery,
  type PublicRoundListQuery,
  type PublicSeasonListQuery,
} from "../public/schemas";
import {
  getPublicFixtureService,
  listPublicCompetitionsService,
  listPublicFixturesService,
  listPublicRoundsService,
  listPublicSeasonsService,
  type PublicServiceContext,
} from "../public/service";

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

function parseParams<T>(schema: z.ZodType<T>, raw: Record<string, string>): T {
  const parsed = schema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid route parameters",
      parsed.error.flatten().fieldErrors,
    );
  }
  return parsed.data;
}

function paginationMeta(page: number, limit: number, total: number) {
  return { page, limit, total, hasMore: page * limit < total };
}

async function makePublicServiceContext(
  c: Parameters<typeof ok>[0],
): Promise<PublicServiceContext> {
  return { db: await requireDb(c) };
}

function sendPaginated<T>(
  c: Parameters<typeof ok>[0],
  rows: readonly T[],
  total: number,
  pagination: PublicPaginationQuery,
) {
  return paginated(
    c,
    rows,
    paginationMeta(pagination.page, pagination.limit, total),
  );
}

export const publicRoutes = new Hono<HonoEnv>();

publicRoutes.get("/competitions", async (c) => {
  const query = parseQuery(PublicCompetitionListQuerySchema, {
    page: c.req.query("page"),
    limit: c.req.query("limit"),
  }) as PublicPaginationQuery;
  const result = await listPublicCompetitionsService(
    await makePublicServiceContext(c),
    query,
  );
  return sendPaginated(c, result.rows, result.total, query);
});

publicRoutes.get("/seasons", async (c) => {
  const query = parseQuery(PublicSeasonListQuerySchema, {
    page: c.req.query("page"),
    limit: c.req.query("limit"),
    competitionId: c.req.query("competitionId"),
  }) as PublicSeasonListQuery;
  const result = await listPublicSeasonsService(
    await makePublicServiceContext(c),
    query,
    query,
  );
  return sendPaginated(c, result.rows, result.total, query);
});

publicRoutes.get("/rounds", async (c) => {
  const query = parseQuery(PublicRoundListQuerySchema, {
    page: c.req.query("page"),
    limit: c.req.query("limit"),
    competitionId: c.req.query("competitionId"),
    seasonId: c.req.query("seasonId"),
  }) as PublicRoundListQuery;
  const result = await listPublicRoundsService(
    await makePublicServiceContext(c),
    query,
    query,
  );
  return sendPaginated(c, result.rows, result.total, query);
});

publicRoutes.get("/fixtures", async (c) => {
  const query = parseQuery(PublicFixtureListQuerySchema, {
    page: c.req.query("page"),
    limit: c.req.query("limit"),
    competitionId: c.req.query("competitionId"),
    seasonId: c.req.query("seasonId"),
    roundId: c.req.query("roundId"),
    status: c.req.query("status"),
    fromDate: c.req.query("fromDate"),
    toDate: c.req.query("toDate"),
  }) as PublicFixtureListQuery;
  const result = await listPublicFixturesService(
    await makePublicServiceContext(c),
    query,
  );
  return sendPaginated(c, result.rows, result.total, query);
});

publicRoutes.get("/fixtures/:id", async (c) => {
  const params = parseParams(PublicIdParamSchema, { id: c.req.param("id") });
  return ok(
    c,
    await getPublicFixtureService(await makePublicServiceContext(c), params.id),
  );
});
