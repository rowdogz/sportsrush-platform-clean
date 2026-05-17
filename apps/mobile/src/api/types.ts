export type PaginatedResult<T> = {
  readonly data: readonly T[];
  readonly meta: {
    readonly page: number;
    readonly limit: number;
    readonly total: number;
    readonly hasMore: boolean;
  };
};

export type PublicFixture = {
  readonly id: string;
  readonly kickoffTime: string;
  readonly venue: string | null;
  readonly status: string;
  readonly homeScore: number | null;
  readonly awayScore: number | null;
  readonly homeTeam: {
    readonly id: string;
    readonly name: string;
    readonly shortName: string | null;
    readonly displayName: string | null;
  };
  readonly awayTeam: {
    readonly id: string;
    readonly name: string;
    readonly shortName: string | null;
    readonly displayName: string | null;
  };
  readonly competition: {
    readonly id: string;
    readonly slug: string;
    readonly name: string;
    readonly shortName: string | null;
  };
};

export type LeaderboardEntry = {
  readonly rank: number;
  readonly movement: number | null;
  readonly userId: string;
  readonly email: string | null;
  readonly displayName: string | null;
  readonly totalPoints: number;
  readonly exactScores: number;
  readonly correctResults: number;
  readonly predictionsScored: number;
  readonly lastScoredAt: string | null;
};
