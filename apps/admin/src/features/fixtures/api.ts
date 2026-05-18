import { apiRequest } from "../../lib/apiClient";
import type {
  AdminFixture,
  FixtureCorrectionPayload,
  FixtureListFilters,
  FixtureListResponse,
  FixtureResultPayload,
  FixtureStatus,
  FixtureTransitionPayload,
  FixtureWritePayload,
} from "./types";

type RawFixture = {
  readonly id: string;
  readonly sportId?: string;
  readonly sport_id?: string;
  readonly competitionId?: string;
  readonly competition_id?: string;
  readonly seasonId?: string;
  readonly season_id?: string;
  readonly roundId?: string | null;
  readonly round_id?: string | null;
  readonly round: string;
  readonly roundName?: string;
  readonly round_name?: string;
  readonly roundOrder?: number | string | null;
  readonly round_order?: number | string | null;
  readonly homeTeamId?: string;
  readonly home_team_id?: string;
  readonly awayTeamId?: string;
  readonly away_team_id?: string;
  readonly scheduledAt?: string;
  readonly scheduled_at?: string;
  readonly originalScheduledAt?: string | null;
  readonly original_scheduled_at?: string | null;
  readonly venueName?: string | null;
  readonly venue_name?: string | null;
  readonly status: FixtureStatus;
  readonly homeScore?: number | string | null;
  readonly home_score?: number | string | null;
  readonly awayScore?: number | string | null;
  readonly away_score?: number | string | null;
  readonly resultSource?: string | null;
  readonly result_source?: string | null;
  readonly resultEnteredAt?: string | null;
  readonly result_entered_at?: string | null;
  readonly resultEnteredBy?: string | null;
  readonly result_entered_by?: string | null;
  readonly legacyMatchId?: number | string | null;
  readonly legacy_match_id?: number | string | null;
  readonly legacyFixtureId?: string | null;
  readonly legacy_fixture_id?: string | null;
  readonly externalSource?: string | null;
  readonly external_source?: string | null;
  readonly externalId?: string | null;
  readonly external_id?: string | null;
};

type RawFixtureListResponse = {
  readonly data: readonly RawFixture[];
  readonly meta: FixtureListResponse["meta"];
};

type RawFixtureResponse =
  | RawFixture
  | {
      readonly data: RawFixture;
    };

type RawFixtureRecalculationResponse =
  | {
      readonly scoredPredictions: number;
    }
  | {
      readonly data: {
        readonly scoredPredictions: number;
      };
    };

function toNullableString(value: string | null | undefined): string | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  return value;
}

function toNullableNumber(
  value: number | string | null | undefined,
): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  return Number(value);
}

function normalizeFixture(fixture: RawFixture): AdminFixture {
  return {
    id: fixture.id,
    sportId: fixture.sportId ?? fixture.sport_id ?? "",
    competitionId: fixture.competitionId ?? fixture.competition_id ?? "",
    seasonId: fixture.seasonId ?? fixture.season_id ?? "",
    roundId: fixture.roundId ?? fixture.round_id ?? null,
    round: fixture.round,
    roundName: fixture.roundName ?? fixture.round_name ?? "",
    roundOrder: toNullableNumber(fixture.roundOrder ?? fixture.round_order),
    homeTeamId: fixture.homeTeamId ?? fixture.home_team_id ?? "",
    awayTeamId: fixture.awayTeamId ?? fixture.away_team_id ?? "",
    scheduledAt: fixture.scheduledAt ?? fixture.scheduled_at ?? "",
    originalScheduledAt:
      fixture.originalScheduledAt ?? fixture.original_scheduled_at ?? null,
    venueName: fixture.venueName ?? fixture.venue_name ?? null,
    status: fixture.status,
    homeScore: toNullableNumber(fixture.homeScore ?? fixture.home_score),
    awayScore: toNullableNumber(fixture.awayScore ?? fixture.away_score),
    resultSource: fixture.resultSource ?? fixture.result_source ?? null,
    resultEnteredAt:
      fixture.resultEnteredAt ?? fixture.result_entered_at ?? null,
    resultEnteredBy:
      fixture.resultEnteredBy ?? fixture.result_entered_by ?? null,
    legacyMatchId: toNullableNumber(
      fixture.legacyMatchId ?? fixture.legacy_match_id,
    ),
    legacyFixtureId:
      fixture.legacyFixtureId ?? fixture.legacy_fixture_id ?? null,
    externalSource: fixture.externalSource ?? fixture.external_source ?? null,
    externalId: fixture.externalId ?? fixture.external_id ?? null,
  };
}

function normalizeFixtureResponse(response: RawFixtureResponse): AdminFixture {
  return normalizeFixture("data" in response ? response.data : response);
}

function normalizeFixtureRecalculationResponse(
  response: RawFixtureRecalculationResponse,
): { readonly scoredPredictions: number } {
  return "data" in response ? response.data : response;
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

export async function listFixtures(
  filters: FixtureListFilters = {},
): Promise<FixtureListResponse> {
  const params = new URLSearchParams();
  params.set("page", "1");
  params.set("limit", "50");
  appendOptionalParam(params, "competitionId", filters.competitionId);
  appendOptionalParam(params, "seasonId", filters.seasonId);
  appendOptionalParam(params, "round", filters.round);
  appendOptionalParam(params, "status", filters.status);
  appendOptionalParam(params, "dateFrom", filters.dateFrom);
  appendOptionalParam(params, "dateTo", filters.dateTo);

  const response = await apiRequest<RawFixtureListResponse>(
    `/v1/admin/fixtures?${params.toString()}`,
  );

  return {
    data: response.data.map(normalizeFixture),
    meta: response.meta,
  };
}

export async function getFixture(id: string): Promise<AdminFixture> {
  const response = await apiRequest<RawFixtureResponse>(
    `/v1/admin/fixtures/${id}`,
  );

  return normalizeFixtureResponse(response);
}

export async function createFixture(
  payload: FixtureWritePayload,
): Promise<AdminFixture> {
  const response = await apiRequest<RawFixtureResponse>("/v1/admin/fixtures", {
    method: "POST",
    body: payload,
  });

  return normalizeFixtureResponse(response);
}

export async function updateFixture(
  id: string,
  payload: FixtureWritePayload,
): Promise<AdminFixture> {
  const response = await apiRequest<RawFixtureResponse>(
    `/v1/admin/fixtures/${id}`,
    {
      method: "PATCH",
      body: payload,
    },
  );

  return normalizeFixtureResponse(response);
}

export async function transitionFixture(
  id: string,
  payload: FixtureTransitionPayload,
): Promise<AdminFixture> {
  const response = await apiRequest<RawFixtureResponse>(
    `/v1/admin/fixtures/${id}/transition`,
    {
      method: "POST",
      body: payload,
    },
  );

  return normalizeFixtureResponse(response);
}

export async function enterFixtureResult(
  id: string,
  payload: FixtureResultPayload,
): Promise<AdminFixture> {
  const response = await apiRequest<RawFixtureResponse>(
    `/v1/admin/fixtures/${id}/result`,
    {
      method: "POST",
      body: payload,
    },
  );

  return normalizeFixtureResponse(response);
}

export async function correctFixtureResult(
  id: string,
  payload: FixtureCorrectionPayload,
): Promise<AdminFixture> {
  const response = await apiRequest<RawFixtureResponse>(
    `/v1/admin/fixtures/${id}/correct-result`,
    {
      method: "POST",
      body: payload,
    },
  );

  return normalizeFixtureResponse(response);
}

export async function recalculateFixturePredictionScores(
  id: string,
): Promise<{ readonly scoredPredictions: number }> {
  const response = await apiRequest<RawFixtureRecalculationResponse>(
    `/v1/predictions/fixtures/${id}/recalculate`,
    {
      method: "POST",
    },
  );

  return normalizeFixtureRecalculationResponse(response);
}

export function toFixtureWritePayload(
  payload: FixtureWritePayload,
): FixtureWritePayload {
  return {
    ...payload,
    roundId: toNullableString(payload.roundId),
    originalScheduledAt: toNullableString(payload.originalScheduledAt),
    venueName: toNullableString(payload.venueName),
    legacyFixtureId: toNullableString(payload.legacyFixtureId),
    externalSource: toNullableString(payload.externalSource),
    externalId: toNullableString(payload.externalId),
  };
}
