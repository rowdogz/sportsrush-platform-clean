export type AdminTeamAlias = {
  readonly id: string;
  readonly teamId: string;
  readonly sportId: string;
  readonly alias: string;
  readonly normalizedAlias: string;
  readonly source: string;
  readonly priority: number;
  readonly isActive: boolean;
  readonly legacyId: string | null;
};

export type TeamAliasListFilters = {
  readonly sportId: string;
  readonly source?: string | null;
  readonly alias?: string | null;
};

export type TeamAliasWritePayload = {
  readonly teamId: string;
  readonly sportId: string;
  readonly alias: string;
  readonly normalizedAlias?: string | null;
  readonly source?: string | null;
  readonly priority?: number | null;
  readonly isActive?: boolean;
  readonly legacyId?: string | null;
};

export type TeamAliasListResponse = {
  readonly data: readonly AdminTeamAlias[];
};
