import { apiRequest } from "../../lib/apiClient";
import type { AdminCompetition, CompetitionListResponse } from "./types";

type RawCompetition = {
  readonly id: string;
  readonly name: string;
  readonly slug: string;
  readonly sportId?: string;
  readonly sport_id?: string;
  readonly countryCode?: string | null;
  readonly country_code?: string | null;
  readonly isActive?: boolean | number;
  readonly is_active?: boolean | number;
};

type RawCompetitionListResponse = {
  readonly data: readonly RawCompetition[];
  readonly meta: CompetitionListResponse["meta"];
};

function toBoolean(value: boolean | number | undefined): boolean {
  return value === true || value === 1;
}

function normalizeCompetition(competition: RawCompetition): AdminCompetition {
  return {
    id: competition.id,
    name: competition.name,
    slug: competition.slug,
    sportId: competition.sportId ?? competition.sport_id ?? "",
    countryCode: competition.countryCode ?? competition.country_code ?? null,
    isActive: toBoolean(competition.isActive ?? competition.is_active),
  };
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
