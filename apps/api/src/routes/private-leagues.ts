import { Hono } from "hono";
import { z } from "zod";
import type { HonoEnv } from "../env";
import { requireRole } from "../middleware/auth";
import { requireDb } from "../lib/db";
import { created, ok, paginated } from "../lib/response";
import { AuthenticationError, ValidationError } from "../lib/errors";
import {
  PrivateLeagueListQuerySchema,
  PrivateLeagueMemberWriteSchema,
  PrivateLeagueUpdateSchema,
  PrivateLeagueWriteSchema,
  type PrivateLeagueListQuery,
  type PrivateLeagueMemberWriteInput,
  type PrivateLeagueWriteInput,
} from "../private-leagues/schemas";
import {
  addPrivateLeagueMemberService,
  archivePrivateLeagueService,
  createPrivateLeagueService,
  getPrivateLeagueService,
  listPrivateLeaguesService,
  removePrivateLeagueMemberService,
  updatePrivateLeagueService,
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
  readonly includeArchived?: "true" | "false" | undefined;
}): PrivateLeagueListQuery {
  return { ...query, page: query.page ?? 1, limit: query.limit ?? 25 };
}

function normalizeLeagueWrite(input: {
  readonly slug: string;
  readonly name: string;
  readonly description?: string | null | undefined;
  readonly logoUrl?: string | null | undefined;
  readonly bannerUrl?: string | null | undefined;
  readonly ownerUserId?: string | null | undefined;
  readonly competitionIds?: string[] | undefined;
}): PrivateLeagueWriteInput {
  return { ...input, competitionIds: input.competitionIds ?? [] };
}

function normalizeMemberWrite(input: {
  readonly userId: string;
  readonly role?: "owner" | "admin" | "member" | undefined;
}): PrivateLeagueMemberWriteInput {
  return { ...input, role: input.role ?? "member" };
}

export const privateLeagueRoutes = new Hono<HonoEnv>();

privateLeagueRoutes.use("*", requireRole("admin"));

privateLeagueRoutes.get("/", async (c) => {
  const query = normalizeListQuery(
    parseQuery(PrivateLeagueListQuerySchema, {
      page: c.req.query("page"),
      limit: c.req.query("limit"),
      search: c.req.query("search"),
      includeArchived: c.req.query("includeArchived"),
    }),
  );
  const result = await listPrivateLeaguesService(await makeContext(c), query);
  return paginated(c, result.rows, meta(query.page, query.limit, result.total));
});

privateLeagueRoutes.post("/", async (c) => {
  const input = normalizeLeagueWrite(
    parseBody(PrivateLeagueWriteSchema, await c.req.json()),
  );
  return created(
    c,
    await createPrivateLeagueService(await makeContext(c), input),
  );
});

privateLeagueRoutes.get("/:id", async (c) => {
  return ok(
    c,
    await getPrivateLeagueService(await makeContext(c), c.req.param("id")),
  );
});

privateLeagueRoutes.patch("/:id", async (c) => {
  const input = parseBody(PrivateLeagueUpdateSchema, await c.req.json());
  return ok(
    c,
    await updatePrivateLeagueService(
      await makeContext(c),
      c.req.param("id"),
      input,
    ),
  );
});

privateLeagueRoutes.post("/:id/archive", async (c) => {
  return ok(
    c,
    await archivePrivateLeagueService(
      await makeContext(c),
      c.req.param("id"),
      true,
    ),
  );
});

privateLeagueRoutes.post("/:id/unarchive", async (c) => {
  return ok(
    c,
    await archivePrivateLeagueService(
      await makeContext(c),
      c.req.param("id"),
      false,
    ),
  );
});

privateLeagueRoutes.post("/:id/members", async (c) => {
  const input = normalizeMemberWrite(
    parseBody(PrivateLeagueMemberWriteSchema, await c.req.json()),
  );
  return ok(
    c,
    await addPrivateLeagueMemberService(
      await makeContext(c),
      c.req.param("id"),
      input,
    ),
  );
});

privateLeagueRoutes.delete("/:id/members/:userId", async (c) => {
  return ok(
    c,
    await removePrivateLeagueMemberService(
      await makeContext(c),
      c.req.param("id"),
      c.req.param("userId"),
    ),
  );
});
