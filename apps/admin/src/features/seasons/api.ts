import { apiRequest } from "../../lib/apiClient";
import type {
  AdminSeason,
  SeasonListFilters,
  SeasonListResponse,
  SeasonWritePayload,
} from "./types";

type RawSeason = {
  readonly id: string;
  readonly competitionId?: string;
  readonly competition_id?: string;
  readonly slug: string;
  readonly name: string;
  readonly startsOn?: string | null;
  readonly starts_on?: string | null;
  readonly endsOn?: string | null;
  readonly ends_on?: string | null;
  readonly isActive?: boolean | number;
  readonly is_active?: boolean | number;
  readonly legacyId?: string | number | null;
  readonly legacy_id?: string | number | null;
};

type RawSeasonListResponse = {
  readonly data: readonly RawSeason[];
  readonly meta: SeasonListResponse["meta"];
};

type RawSeasonResponse =
  | RawSeason
  | {
      readonly data: RawSeason;
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

function normalizeSeason(season: RawSeason): AdminSeason {
  return {
    id: season.id,
    competitionId: season.competitionId ?? season.competition_id ?? "",
    slug: season.slug,
    name: season.name,
    startsOn: season.startsOn ?? season.starts_on ?? null,
    endsOn: season.endsOn ?? season.ends_on ?? null,
    isActive: toBoolean(season.isActive ?? season.is_active),
    legacyId: toNullableString(season.legacyId ?? season.legacy_id),
  };
}

function normalizeSeasonResponse(response: RawSeasonResponse): AdminSeason {
  return normalizeSeason("data" in response ? response.data : response);
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

export async function listSeasons(
  filters: SeasonListFilters = {},
): Promise<SeasonListResponse> {
  const params = new URLSearchParams();
  params.set("page", "1");
  params.set("limit", "50");
  appendOptionalParam(params, "competitionId", filters.competitionId);
  appendOptionalParam(params, "search", filters.search);

  const response = await apiRequest<RawSeasonListResponse>(
    `/v1/admin/seasons?${params.toString()}`,
  );

  return {
    data: response.data.map(normalizeSeason),
    meta: response.meta,
  };
}

export async function createSeason(
  payload: SeasonWritePayload,
): Promise<AdminSeason> {
  const response = await apiRequest<RawSeasonResponse>("/v1/admin/seasons", {
    method: "POST",
    body: payload,
  });

  return normalizeSeasonResponse(response);
}

export async function updateSeason(
  id: string,
  payload: SeasonWritePayload,
): Promise<AdminSeason> {
  const response = await apiRequest<RawSeasonResponse>(
    `/v1/admin/seasons/${id}`,
    {
      method: "PATCH",
      body: payload,
    },
  );

  return normalizeSeasonResponse(response);
}

export async function activateSeason(
  season: AdminSeason,
): Promise<AdminSeason> {
  const response = await apiRequest<RawSeasonResponse>(
    `/v1/admin/seasons/${season.id}/activate`,
    {
      method: "POST",
      body: { competitionId: season.competitionId },
    },
  );

  return normalizeSeasonResponse(response);
}
