export type AdminTeam = {
  readonly id: string;
  readonly sportId: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName: string | null;
  readonly displayName: string | null;
  readonly countryCode: string | null;
  readonly legacyId: string | null;
  readonly isActive: boolean;
};

export type TeamWritePayload = {
  readonly sportId: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName?: string | null;
  readonly displayName?: string | null;
  readonly countryCode?: string | null;
  readonly legacyId?: string | null;
};

export type TeamListMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export type TeamListResponse = {
  readonly data: readonly AdminTeam[];
  readonly meta: TeamListMeta;
};
