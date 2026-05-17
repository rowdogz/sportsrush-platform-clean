export type AdminPrivateLeague = {
  readonly id: string;
  readonly slug: string;
  readonly name: string;
  readonly description: string | null;
  readonly logoUrl: string | null;
  readonly bannerUrl: string | null;
  readonly inviteCode: string;
  readonly ownerUserId: string | null;
  readonly isArchived: boolean;
  readonly memberCount: number;
  readonly competitionCount: number;
  readonly createdAt: string;
  readonly updatedAt: string;
  readonly archivedAt: string | null;
};

export type PrivateLeagueWritePayload = {
  readonly slug: string;
  readonly name: string;
  readonly description?: string | null;
  readonly logoUrl?: string | null;
  readonly bannerUrl?: string | null;
  readonly ownerUserId?: string | null;
  readonly competitionIds?: readonly string[];
};

export type PrivateLeagueListResponse = {
  readonly data: readonly AdminPrivateLeague[];
  readonly meta: {
    readonly page: number;
    readonly limit: number;
    readonly total: number;
    readonly hasMore: boolean;
  };
};

export type PrivateLeagueListFilters = {
  readonly search?: string;
  readonly includeArchived?: boolean;
};
