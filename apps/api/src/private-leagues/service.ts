import { randomUUID } from "node:crypto";
import { hasRole } from "@sr/auth";
import type { Role, TokenPayload } from "@sr/types";
import type { DbClient } from "../lib/db";
import { AppError, AuthorizationError, NotFoundError } from "../lib/errors";
import { createAuditEvent } from "../admin/repository";
import { normalizeSlug } from "../admin/normalization";
import {
  createPrivateLeague,
  findLeagueMember,
  findPrivateLeagueById,
  findPrivateLeagueByInviteCode,
  listLeagueCompetitions,
  listLeagueMembers,
  listPrivateLeagues,
  listPrivateLeaguesForUser,
  removeLeagueMember,
  replaceLeagueCompetitions,
  updatePrivateLeague,
  upsertLeagueMember,
  type PrivateLeagueDetailRow,
  type PrivateLeagueMemberRow,
} from "./repository";
import type {
  PrivateLeagueJoinInput,
  PrivateLeagueListQuery,
  PrivateLeagueMemberWriteInput,
  PrivateLeagueUpdateInput,
  PrivateLeagueWriteInput,
} from "./schemas";

export class PrivateLeagueDomainError extends AppError {
  override readonly name = "PrivateLeagueDomainError";

  constructor(message: string, correlationId: string) {
    super(422, "PRIVATE_LEAGUE_DOMAIN_ERROR", message, { correlationId });
  }
}

export type PrivateLeagueServiceContext = {
  readonly db: DbClient;
  readonly now: string;
  readonly correlationId: string;
  readonly actorUserId: string;
  readonly actorRole: Role;
};

export type PrivateLeagueDto = {
  readonly id: string;
  readonly slug: string;
  readonly name: string;
  readonly description: string | null;
  readonly logoUrl: string | null;
  readonly bannerUrl: string | null;
  readonly inviteCode: string;
  readonly ownerUserId: string | null;
  readonly isArchived: boolean;
  readonly memberCount: number;
  readonly competitionCount: number;
  readonly createdAt: string;
  readonly updatedAt: string;
  readonly archivedAt: string | null;
};

export type PrivateLeagueMemberDto = {
  readonly userId: string;
  readonly email: string | null;
  readonly displayName: string | null;
  readonly role: "owner" | "admin" | "member";
  readonly isActive: boolean;
  readonly joinedAt: string;
};

export type PrivateLeagueDetailDto = PrivateLeagueDto & {
  readonly members: readonly PrivateLeagueMemberDto[];
  readonly competitions: readonly {
    readonly competitionId: string;
    readonly competitionName: string | null;
  }[];
};

export type MemberPrivateLeagueDto = Omit<PrivateLeagueDto, "inviteCode"> & {
  readonly viewerRole: "owner" | "admin" | "member" | null;
};

export type MemberPrivateLeagueDetailDto = Omit<
  PrivateLeagueDetailDto,
  "inviteCode"
> & {
  readonly viewerRole: "owner" | "admin" | "member" | null;
};

function assert(
  condition: unknown,
  message: string,
  correlationId: string,
): asserts condition {
  if (!condition) throw new PrivateLeagueDomainError(message, correlationId);
}

function assertAdmin(context: PrivateLeagueServiceContext) {
  assert(
    hasRole(
      {
        userId: context.actorUserId,
        role: context.actorRole,
        sessionId: "private-league-service",
        iat: 0,
        exp: 0,
      } as TokenPayload,
      "admin",
    ),
    "Admin access required.",
    context.correlationId,
  );
}

function inviteCode(): string {
  return randomUUID().replaceAll("-", "").slice(0, 10).toUpperCase();
}

function toDto(row: PrivateLeagueDetailRow): PrivateLeagueDto {
  return {
    id: row.id,
    slug: row.slug,
    name: row.name,
    description: row.description,
    logoUrl: row.logo_url,
    bannerUrl: row.banner_url,
    inviteCode: row.invite_code,
    ownerUserId: row.owner_user_id,
    isArchived: row.is_archived === 1,
    memberCount: row.member_count,
    competitionCount: row.competition_count,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
    archivedAt: row.archived_at,
  };
}

function toMemberDto(row: PrivateLeagueMemberRow): PrivateLeagueMemberDto {
  return {
    userId: row.user_id,
    email: row.email,
    displayName: row.display_name,
    role: row.role,
    isActive: row.is_active === 1,
    joinedAt: row.joined_at,
  };
}

function toMemberLeagueDto(
  row: PrivateLeagueDetailRow,
  viewerRole: "owner" | "admin" | "member" | null,
): MemberPrivateLeagueDto {
  const { inviteCode: _inviteCode, ...league } = toDto(row);
  return { ...league, viewerRole };
}

async function audit(
  context: PrivateLeagueServiceContext,
  action: string,
  targetId: string,
  before: unknown,
  after: unknown,
) {
  await createAuditEvent(
    context.db,
    randomUUID(),
    {
      actorUserId: context.actorUserId,
      action,
      targetType: "private_league",
      targetId,
      before,
      after,
    },
    context.now,
  );
}

async function detail(
  db: DbClient,
  row: PrivateLeagueDetailRow,
): Promise<PrivateLeagueDetailDto> {
  const [members, competitions] = await Promise.all([
    listLeagueMembers(db, row.id),
    listLeagueCompetitions(db, row.id),
  ]);
  return {
    ...toDto(row),
    members: members.map(toMemberDto),
    competitions: competitions.map((competition) => ({
      competitionId: competition.competition_id,
      competitionName: competition.competition_name,
    })),
  };
}

async function requireMemberAccess(
  context: PrivateLeagueServiceContext,
  leagueId: string,
) {
  const row = await findPrivateLeagueById(context.db, leagueId);
  if (row === null) throw new NotFoundError("Private league not found");
  if (
    hasRole(
      {
        userId: context.actorUserId,
        role: context.actorRole,
        sessionId: "private-league-member-service",
        iat: 0,
        exp: 0,
      } as TokenPayload,
      "admin",
    )
  ) {
    return { row, member: null as PrivateLeagueMemberRow | null };
  }
  const member = await findLeagueMember(
    context.db,
    leagueId,
    context.actorUserId,
  );
  if (member === null || member.is_active !== 1) {
    throw new AuthorizationError("Private league membership required");
  }
  return { row, member };
}

export async function listPrivateLeaguesService(
  context: PrivateLeagueServiceContext,
  query: PrivateLeagueListQuery,
) {
  assertAdmin(context);
  const result = await listPrivateLeagues(context.db, query);
  return {
    rows: result.rows.map(toDto),
    total: result.total,
  };
}

export async function getPrivateLeagueService(
  context: PrivateLeagueServiceContext,
  id: string,
) {
  assertAdmin(context);
  const row = await findPrivateLeagueById(context.db, id);
  assert(row !== null, "Private league not found.", context.correlationId);
  return detail(context.db, row);
}

export async function createPrivateLeagueService(
  context: PrivateLeagueServiceContext,
  input: PrivateLeagueWriteInput,
) {
  assertAdmin(context);
  const row = await createPrivateLeague(
    context.db,
    randomUUID(),
    inviteCode(),
    { ...input, slug: normalizeSlug(input.slug) },
    context.now,
  );
  await replaceLeagueCompetitions(
    context.db,
    row.id,
    input.competitionIds.map(() => randomUUID()),
    input.competitionIds,
    context.now,
  );
  const created = await getPrivateLeagueService(context, row.id);
  await audit(context, "private_league.create", row.id, null, created);
  return created;
}

export async function updatePrivateLeagueService(
  context: PrivateLeagueServiceContext,
  id: string,
  input: PrivateLeagueUpdateInput,
) {
  assertAdmin(context);
  const before = await getPrivateLeagueService(context, id);
  const row = await updatePrivateLeague(
    context.db,
    id,
    {
      ...input,
      ...(input.slug !== undefined ? { slug: normalizeSlug(input.slug) } : {}),
    },
    context.now,
  );
  assert(row !== null, "Private league not found.", context.correlationId);
  if (input.competitionIds !== undefined) {
    await replaceLeagueCompetitions(
      context.db,
      id,
      input.competitionIds.map(() => randomUUID()),
      input.competitionIds,
      context.now,
    );
  }
  const after = await getPrivateLeagueService(context, id);
  await audit(context, "private_league.update", id, before, after);
  return after;
}

export async function archivePrivateLeagueService(
  context: PrivateLeagueServiceContext,
  id: string,
  isArchived: boolean,
) {
  assertAdmin(context);
  const before = await getPrivateLeagueService(context, id);
  const row = await updatePrivateLeague(
    context.db,
    id,
    { isArchived },
    context.now,
  );
  assert(row !== null, "Private league not found.", context.correlationId);
  const after = await getPrivateLeagueService(context, id);
  await audit(
    context,
    isArchived ? "private_league.archive" : "private_league.unarchive",
    id,
    before,
    after,
  );
  return after;
}

export async function addPrivateLeagueMemberService(
  context: PrivateLeagueServiceContext,
  id: string,
  input: PrivateLeagueMemberWriteInput,
) {
  assertAdmin(context);
  const before = await getPrivateLeagueService(context, id);
  await upsertLeagueMember(
    context.db,
    randomUUID(),
    id,
    input.userId,
    input.role,
    context.now,
  );
  const after = await getPrivateLeagueService(context, id);
  await audit(context, "private_league.member.upsert", id, before, after);
  return after.members;
}

export async function removePrivateLeagueMemberService(
  context: PrivateLeagueServiceContext,
  id: string,
  userId: string,
) {
  assertAdmin(context);
  const before = await getPrivateLeagueService(context, id);
  await removeLeagueMember(context.db, id, userId, context.now);
  const after = await getPrivateLeagueService(context, id);
  await audit(context, "private_league.member.remove", id, before, after);
  return after.members;
}

export async function listMemberPrivateLeaguesService(
  context: PrivateLeagueServiceContext,
  query: PrivateLeagueListQuery,
) {
  const result = await listPrivateLeaguesForUser(
    context.db,
    context.actorUserId,
    query,
  );
  const rows = await Promise.all(
    result.rows.map(async (row) => {
      const membership = await findLeagueMember(
        context.db,
        row.id,
        context.actorUserId,
      );
      return toMemberLeagueDto(row, membership?.role ?? null);
    }),
  );
  return { rows, total: result.total };
}

export async function getMemberPrivateLeagueService(
  context: PrivateLeagueServiceContext,
  id: string,
): Promise<MemberPrivateLeagueDetailDto> {
  const { row, member } = await requireMemberAccess(context, id);
  const base = await detail(context.db, row);
  const { inviteCode: _inviteCode, ...rest } = base;
  return { ...rest, viewerRole: member?.role ?? null };
}

export async function joinPrivateLeagueByInviteCodeService(
  context: PrivateLeagueServiceContext,
  input: PrivateLeagueJoinInput,
): Promise<MemberPrivateLeagueDetailDto> {
  const row = await findPrivateLeagueByInviteCode(context.db, input.inviteCode);
  if (row === null || row.is_archived === 1) {
    throw new NotFoundError("Private league not found");
  }
  const before = await findLeagueMember(
    context.db,
    row.id,
    context.actorUserId,
  );
  await upsertLeagueMember(
    context.db,
    randomUUID(),
    row.id,
    context.actorUserId,
    before?.role ?? "member",
    context.now,
  );
  const after = await getMemberPrivateLeagueService(context, row.id);
  await audit(
    context,
    "private_league.member.self_join",
    row.id,
    before ? toMemberDto(before) : null,
    after.viewerRole,
  );
  return after;
}
