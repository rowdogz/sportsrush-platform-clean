export type AdminCompetition = {
  readonly id: string;
  readonly name: string;
  readonly slug: string;
  readonly sportId: string;
  readonly countryCode: string | null;
  readonly isActive: boolean;
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
