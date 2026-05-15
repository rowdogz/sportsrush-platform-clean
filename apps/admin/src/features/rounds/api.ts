import { apiRequest } from "../../lib/apiClient";
import type {
  AdminRound,
  RoundListFilters,
  RoundListResponse,
  RoundWritePayload,
} from "./types";

type RawRound = {
  readonly id: string;
  readonly seasonId?: string;
  readonly season_id?: string;
  readonly round: string;
  readonly roundName?: string;
  readonly round_name?: string;
  readonly displayOrder?: number | string;
  readonly display_order?: number | string;
  readonly startsAt?: string | null;
  readonly starts_at?: string | null;
  readonly endsAt?: string | null;
  readonly ends_at?: string | null;
  readonly legacyId?: string | number | null;
  readonly legacy_id?: string | number | null;
};

type RawRoundListResponse = {
  readonly data: readonly RawRound[];
};

type RawRoundResponse =
  | RawRound
  | {
      readonly data: RawRound;
    };

function toNumber(value: number | string | undefined): number {
  if (typeof value === "number") {
    return value;
  }

  if (typeof value === "string") {
    return Number(value);
  }

  return 0;
}

function toNullableString(
  value: string | number | null | undefined,
): string | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  return String(value);
}

function normalizeRound(round: RawRound): AdminRound {
  return {
    id: round.id,
    seasonId: round.seasonId ?? round.season_id ?? "",
    round: round.round,
    roundName: round.roundName ?? round.round_name ?? "",
    displayOrder: toNumber(round.displayOrder ?? round.display_order),
    startsAt: round.startsAt ?? round.starts_at ?? null,
    endsAt: round.endsAt ?? round.ends_at ?? null,
    legacyId: toNullableString(round.legacyId ?? round.legacy_id),
  };
}

function normalizeRoundResponse(response: RawRoundResponse): AdminRound {
  return normalizeRound("data" in response ? response.data : response);
}

export async function listRounds(
  filters: RoundListFilters,
): Promise<RoundListResponse> {
  const params = new URLSearchParams();
  params.set("seasonId", filters.seasonId.trim());

  const response = await apiRequest<RawRoundListResponse>(
    `/v1/admin/rounds?${params.toString()}`,
  );

  return {
    data: response.data.map(normalizeRound),
  };
}

export async function createRound(
  payload: RoundWritePayload,
): Promise<AdminRound> {
  const response = await apiRequest<RawRoundResponse>("/v1/admin/rounds", {
    method: "POST",
    body: payload,
  });

  return normalizeRoundResponse(response);
}

export async function updateRound(
  id: string,
  payload: RoundWritePayload,
): Promise<AdminRound> {
  const response = await apiRequest<RawRoundResponse>(
    `/v1/admin/rounds/${id}`,
    {
      method: "PATCH",
      body: payload,
    },
  );

  return normalizeRoundResponse(response);
}
