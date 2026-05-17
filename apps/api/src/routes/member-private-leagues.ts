import { Hono } from "hono";
import { z } from "zod";
import type { HonoEnv } from "../env";
import { requireAuth } from "../middleware/auth";
import { requireDb } from "../lib/db";
import { created, ok, paginated } from "../lib/response";
import { AuthenticationError, ValidationError } from "../lib/errors";
import {
  PrivateLeagueJoinSchema,
  PrivateLeagueListQuerySchema,
  type PrivateLeagueJoinInput,
  type PrivateLeagueListQuery,
} from "../private-leagues/schemas";
import {
  getMemberPrivateLeagueService,
  joinPrivateLeagueByInviteCodeService,
  listMemberPrivateLeaguesService,
  type PrivateLeagueServiceContext,
} from "../private-leagues/service";

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
): Promise<PrivateLeagueServiceContext> {
  const user = c.var.user;
  if (user === undefined) throw new AuthenticationError();
  return {
    db: await requireDb(c),
    now: new Date().toISOString(),
    correlationId: c.var.correlationId ?? "unknown",
    actorUserId: user.userId,
    actorRole: user.role,
  };
}

function meta(page: number, limit: number, total: number) {
  return { page, limit, total, hasMore: page * limit < total };
}

function normalizeListQuery(query: {
  readonly page?: number | undefined;
  readonly limit?: number | undefined;
  readonly search?: string | undefined;
}): PrivateLeagueListQuery {
  return { ...query, page: query.page ?? 1, limit: query.limit ?? 25 };
}

function normalizeJoinInput(input: {
  readonly inviteCode: string;
}): PrivateLeagueJoinInput {
  return { inviteCode: input.inviteCode.trim().toUpperCase() };
}

export const memberPrivateLeagueRoutes = new Hono<HonoEnv>();

memberPrivateLeagueRoutes.use("*", requireAuth());

memberPrivateLeagueRoutes.get("/", async (c) => {
  const query = normalizeListQuery(
    parseQuery(PrivateLeagueListQuerySchema.omit({ includeArchived: true }), {
      page: c.req.query("page"),
      limit: c.req.query("limit"),
      search: c.req.query("search"),
    }),
  );
  const result = await listMemberPrivateLeaguesService(
    await makeContext(c),
    query,
  );
  return paginated(c, result.rows, meta(query.page, query.limit, result.total));
});

memberPrivateLeagueRoutes.post("/join", async (c) => {
  const input = normalizeJoinInput(
    parseBody(PrivateLeagueJoinSchema, await c.req.json()),
  );
  return created(
    c,
    await joinPrivateLeagueByInviteCodeService(await makeContext(c), input),
  );
});

memberPrivateLeagueRoutes.get("/:id", async (c) => {
  return ok(
    c,
    await getMemberPrivateLeagueService(
      await makeContext(c),
      c.req.param("id"),
    ),
  );
});
