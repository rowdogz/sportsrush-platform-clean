import { apiRequest } from "../../lib/apiClient";
import type { AdminTeam, TeamListResponse, TeamWritePayload } from "./types";

type RawTeam = {
  readonly id: string;
  readonly sportId?: string;
  readonly sport_id?: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName?: string | null;
  readonly short_name?: string | null;
  readonly displayName?: string | null;
  readonly display_name?: string | null;
  readonly countryCode?: string | null;
  readonly country_code?: string | null;
  readonly legacyId?: string | number | null;
  readonly legacy_id?: string | number | null;
  readonly isActive?: boolean | number;
  readonly is_active?: boolean | number;
};

type RawTeamListResponse = {
  readonly data: readonly RawTeam[];
  readonly meta: TeamListResponse["meta"];
};

type RawTeamResponse =
  | RawTeam
  | {
      readonly data: RawTeam;
    };

function toBoolean(value: boolean | number | undefined): boolean {
  return value === true || value === 1;
}

function toNullableString(
  value: string | number | null | undefined,
): string | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  return String(value);
}

function normalizeTeam(team: RawTeam): AdminTeam {
  return {
    id: team.id,
    sportId: team.sportId ?? team.sport_id ?? "",
    slug: team.slug,
    name: team.name,
    shortName: team.shortName ?? team.short_name ?? null,
    displayName: team.displayName ?? team.display_name ?? null,
    countryCode: team.countryCode ?? team.country_code ?? null,
    legacyId: toNullableString(team.legacyId ?? team.legacy_id),
    isActive: toBoolean(team.isActive ?? team.is_active),
  };
}

function normalizeTeamResponse(response: RawTeamResponse): AdminTeam {
  return normalizeTeam("data" in response ? response.data : response);
}

export async function listTeams(): Promise<TeamListResponse> {
  const response = await apiRequest<RawTeamListResponse>(
    "/v1/admin/teams?page=1&limit=50",
  );

  return {
    data: response.data.map(normalizeTeam),
    meta: response.meta,
  };
}

export async function createTeam(
  payload: TeamWritePayload,
): Promise<AdminTeam> {
  const response = await apiRequest<RawTeamResponse>("/v1/admin/teams", {
    method: "POST",
    body: payload,
  });

  return normalizeTeamResponse(response);
}

export async function updateTeam(
  id: string,
  payload: TeamWritePayload,
): Promise<AdminTeam> {
  const response = await apiRequest<RawTeamResponse>(`/v1/admin/teams/${id}`, {
    method: "PATCH",
    body: payload,
  });

  return normalizeTeamResponse(response);
}

export async function archiveTeam(id: string): Promise<void> {
  await apiRequest<unknown>(`/v1/admin/teams/${id}/archive`, {
    method: "POST",
  });
}
