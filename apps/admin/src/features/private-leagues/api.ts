import { apiRequest } from "../../lib/apiClient";
import type {
  AdminPrivateLeague,
  PrivateLeagueListFilters,
  PrivateLeagueListResponse,
  PrivateLeagueWritePayload,
} from "./types";

type RawPrivateLeague = {
  readonly id: string;
  readonly slug: string;
  readonly name: string;
  readonly description?: string | null;
  readonly logoUrl?: string | null;
  readonly logo_url?: string | null;
  readonly bannerUrl?: string | null;
  readonly banner_url?: string | null;
  readonly inviteCode?: string;
  readonly invite_code?: string;
  readonly ownerUserId?: string | null;
  readonly owner_user_id?: string | null;
  readonly isArchived?: boolean | number;
  readonly is_archived?: boolean | number;
  readonly memberCount?: number;
  readonly member_count?: number;
  readonly competitionCount?: number;
  readonly competition_count?: number;
  readonly createdAt?: string;
  readonly created_at?: string;
  readonly updatedAt?: string;
  readonly updated_at?: string;
  readonly archivedAt?: string | null;
  readonly archived_at?: string | null;
};

type RawLeagueResponse = RawPrivateLeague | { readonly data: RawPrivateLeague };
type RawLeagueListResponse = {
  readonly data: readonly RawPrivateLeague[];
  readonly meta: PrivateLeagueListResponse["meta"];
};

function bool(value: boolean | number | undefined): boolean {
  return value === true || value === 1;
}

function normalize(raw: RawPrivateLeague): AdminPrivateLeague {
  return {
    id: raw.id,
    slug: raw.slug,
    name: raw.name,
    description: raw.description ?? null,
    logoUrl: raw.logoUrl ?? raw.logo_url ?? null,
    bannerUrl: raw.bannerUrl ?? raw.banner_url ?? null,
    inviteCode: raw.inviteCode ?? raw.invite_code ?? "",
    ownerUserId: raw.ownerUserId ?? raw.owner_user_id ?? null,
    isArchived: bool(raw.isArchived ?? raw.is_archived),
    memberCount: raw.memberCount ?? raw.member_count ?? 0,
    competitionCount: raw.competitionCount ?? raw.competition_count ?? 0,
    createdAt: raw.createdAt ?? raw.created_at ?? "",
    updatedAt: raw.updatedAt ?? raw.updated_at ?? "",
    archivedAt: raw.archivedAt ?? raw.archived_at ?? null,
  };
}

function normalizeResponse(response: RawLeagueResponse): AdminPrivateLeague {
  return normalize("data" in response ? response.data : response);
}

export async function listPrivateLeagues(
  filters: PrivateLeagueListFilters = {},
): Promise<PrivateLeagueListResponse> {
  const params = new URLSearchParams({ page: "1", limit: "50" });
  if (filters.search?.trim()) params.set("search", filters.search.trim());
  if (filters.includeArchived) params.set("includeArchived", "true");
  const response = await apiRequest<RawLeagueListResponse>(
    `/v1/admin/private-leagues?${params.toString()}`,
  );
  return { data: response.data.map(normalize), meta: response.meta };
}

export async function createPrivateLeague(
  payload: PrivateLeagueWritePayload,
): Promise<AdminPrivateLeague> {
  return normalizeResponse(
    await apiRequest<RawLeagueResponse>("/v1/admin/private-leagues", {
      method: "POST",
      body: payload,
    }),
  );
}

export async function updatePrivateLeague(
  id: string,
  payload: PrivateLeagueWritePayload,
): Promise<AdminPrivateLeague> {
  return normalizeResponse(
    await apiRequest<RawLeagueResponse>(`/v1/admin/private-leagues/${id}`, {
      method: "PATCH",
      body: payload,
    }),
  );
}

export async function archivePrivateLeague(id: string) {
  return normalizeResponse(
    await apiRequest<RawLeagueResponse>(
      `/v1/admin/private-leagues/${id}/archive`,
      { method: "POST" },
    ),
  );
}

export async function unarchivePrivateLeague(id: string) {
  return normalizeResponse(
    await apiRequest<RawLeagueResponse>(
      `/v1/admin/private-leagues/${id}/unarchive`,
      { method: "POST" },
    ),
  );
}

export async function addPrivateLeagueMember(
  id: string,
  payload: {
    readonly userId: string;
    readonly role: "owner" | "admin" | "member";
  },
) {
  return apiRequest(`/v1/admin/private-leagues/${id}/members`, {
    method: "POST",
    body: payload,
  });
}

export async function removePrivateLeagueMember(id: string, userId: string) {
  return apiRequest(`/v1/admin/private-leagues/${id}/members/${userId}`, {
    method: "DELETE",
  });
}
