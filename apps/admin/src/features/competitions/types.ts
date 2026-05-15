export type AdminCompetition = {
  readonly id: string;
  readonly name: string;
  readonly slug: string;
  readonly sportId: string;
  readonly shortName: string | null;
  readonly countryCode: string | null;
  readonly legacyId: string | null;
  readonly isActive: boolean;
};

export type CompetitionWritePayload = {
  readonly sportId: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName?: string | null;
  readonly countryCode?: string | null;
  readonly legacyId?: string | null;
};

export type CompetitionListMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export type CompetitionListResponse = {
  readonly data: readonly AdminCompetition[];
  readonly meta: CompetitionListMeta;
};
