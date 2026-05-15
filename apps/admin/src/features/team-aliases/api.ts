import { apiRequest } from "../../lib/apiClient";
import type {
  AdminTeamAlias,
  TeamAliasListFilters,
  TeamAliasListResponse,
  TeamAliasWritePayload,
} from "./types";

type RawTeamAlias = {
  readonly id: string;
  readonly teamId?: string;
  readonly team_id?: string;
  readonly sportId?: string;
  readonly sport_id?: string;
  readonly alias: string;
  readonly normalizedAlias?: string;
  readonly normalized_alias?: string;
  readonly source: string;
  readonly priority?: number | string;
  readonly isActive?: boolean | number;
  readonly is_active?: boolean | number;
  readonly legacyId?: string | number | null;
  readonly legacy_id?: string | number | null;
};

type RawTeamAliasListResponse = {
  readonly data: readonly RawTeamAlias[];
};

type RawTeamAliasResponse =
  | RawTeamAlias
  | {
      readonly data: RawTeamAlias;
    };

function toBoolean(value: boolean | number | undefined): boolean {
  return value === true || value === 1;
}

function toNumber(value: number | string | undefined): number {
  if (typeof value === "number") {
    return value;
  }

  if (typeof value === "string") {
    return Number(value);
  }

  return 100;
}

function toNullableString(
  value: string | number | null | undefined,
): string | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  return String(value);
}

function normalizeTeamAlias(alias: RawTeamAlias): AdminTeamAlias {
  return {
    id: alias.id,
    teamId: alias.teamId ?? alias.team_id ?? "",
    sportId: alias.sportId ?? alias.sport_id ?? "",
    alias: alias.alias,
    normalizedAlias: alias.normalizedAlias ?? alias.normalized_alias ?? "",
    source: alias.source,
    priority: toNumber(alias.priority),
    isActive: toBoolean(alias.isActive ?? alias.is_active),
    legacyId: toNullableString(alias.legacyId ?? alias.legacy_id),
  };
}

function normalizeTeamAliasResponse(
  response: RawTeamAliasResponse,
): AdminTeamAlias {
  return normalizeTeamAlias("data" in response ? response.data : response);
}

function appendOptionalParam(
  params: URLSearchParams,
  key: string,
  value: string | null | undefined,
) {
  const trimmedValue = value?.trim();
  if (trimmedValue) {
    params.set(key, trimmedValue);
  }
}

export async function listTeamAliases(
  filters: TeamAliasListFilters,
): Promise<TeamAliasListResponse> {
  const params = new URLSearchParams();
  params.set("sportId", filters.sportId.trim());
  appendOptionalParam(params, "source", filters.source);
  appendOptionalParam(params, "alias", filters.alias);

  const response = await apiRequest<RawTeamAliasListResponse>(
    `/v1/admin/team-aliases?${params.toString()}`,
  );

  return {
    data: response.data.map(normalizeTeamAlias),
  };
}

export async function createTeamAlias(
  payload: TeamAliasWritePayload,
): Promise<AdminTeamAlias> {
  const response = await apiRequest<RawTeamAliasResponse>(
    "/v1/admin/team-aliases",
    {
      method: "POST",
      body: payload,
    },
  );

  return normalizeTeamAliasResponse(response);
}

export async function updateTeamAlias(
  id: string,
  payload: TeamAliasWritePayload,
): Promise<AdminTeamAlias> {
  const response = await apiRequest<RawTeamAliasResponse>(
    `/v1/admin/team-aliases/${id}`,
    {
      method: "PATCH",
      body: payload,
    },
  );

  return normalizeTeamAliasResponse(response);
}

export async function deleteTeamAlias(id: string): Promise<void> {
  await apiRequest<unknown>(`/v1/admin/team-aliases/${id}`, {
    method: "DELETE",
  });
}
