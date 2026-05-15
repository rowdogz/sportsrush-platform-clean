import { apiRequest } from "../../lib/apiClient";
import type {
  AdminCompetition,
  CompetitionListResponse,
  CompetitionWritePayload,
} from "./types";

type RawCompetition = {
  readonly id: string;
  readonly name: string;
  readonly slug: string;
  readonly sportId?: string;
  readonly sport_id?: string;
  readonly shortName?: string | null;
  readonly short_name?: string | null;
  readonly countryCode?: string | null;
  readonly country_code?: string | null;
  readonly legacyId?: string | number | null;
  readonly legacy_id?: string | number | null;
  readonly isActive?: boolean | number;
  readonly is_active?: boolean | number;
};

type RawCompetitionListResponse = {
  readonly data: readonly RawCompetition[];
  readonly meta: CompetitionListResponse["meta"];
};

type RawCompetitionResponse =
  | RawCompetition
  | {
      readonly data: RawCompetition;
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

function normalizeCompetition(competition: RawCompetition): AdminCompetition {
  return {
    id: competition.id,
    name: competition.name,
    slug: competition.slug,
    sportId: competition.sportId ?? competition.sport_id ?? "",
    shortName: competition.shortName ?? competition.short_name ?? null,
    countryCode: competition.countryCode ?? competition.country_code ?? null,
    legacyId: toNullableString(competition.legacyId ?? competition.legacy_id),
    isActive: toBoolean(competition.isActive ?? competition.is_active),
  };
}

function normalizeCompetitionResponse(
  response: RawCompetitionResponse,
): AdminCompetition {
  return normalizeCompetition("data" in response ? response.data : response);
}

export async function listCompetitions(): Promise<CompetitionListResponse> {
  const response = await apiRequest<RawCompetitionListResponse>(
    "/v1/admin/competitions?page=1&limit=50",
  );

  return {
    data: response.data.map(normalizeCompetition),
    meta: response.meta,
  };
}

export async function createCompetition(
  payload: CompetitionWritePayload,
): Promise<AdminCompetition> {
  const response = await apiRequest<RawCompetitionResponse>(
    "/v1/admin/competitions",
    {
      method: "POST",
      body: payload,
    },
  );

  return normalizeCompetitionResponse(response);
}

export async function updateCompetition(
  id: string,
  payload: CompetitionWritePayload,
): Promise<AdminCompetition> {
  const response = await apiRequest<RawCompetitionResponse>(
    `/v1/admin/competitions/${id}`,
    {
      method: "PATCH",
      body: payload,
    },
  );

  return normalizeCompetitionResponse(response);
}

export async function archiveCompetition(id: string): Promise<void> {
  await apiRequest<unknown>(`/v1/admin/competitions/${id}/archive`, {
    method: "POST",
  });
}
