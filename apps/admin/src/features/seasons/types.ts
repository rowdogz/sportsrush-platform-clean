export type AdminSeason = {
  readonly id: string;
  readonly competitionId: string;
  readonly slug: string;
  readonly name: string;
  readonly startsOn: string | null;
  readonly endsOn: string | null;
  readonly isActive: boolean;
  readonly legacyId: string | null;
};

export type SeasonListFilters = {
  readonly competitionId?: string | null;
  readonly search?: string | null;
};

export type SeasonWritePayload = {
  readonly competitionId: string;
  readonly slug: string;
  readonly name: string;
  readonly startsOn?: string | null;
  readonly endsOn?: string | null;
  readonly isActive?: boolean;
  readonly legacyId?: string | null;
};

export type SeasonListMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export type SeasonListResponse = {
  readonly data: readonly AdminSeason[];
  readonly meta: SeasonListMeta;
};
