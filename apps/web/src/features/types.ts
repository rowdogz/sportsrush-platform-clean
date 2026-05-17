export type PageMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export type PaginatedResult<T> = {
  readonly data: readonly T[];
  readonly meta: PageMeta;
};

export type FixtureStatus =
  | "scheduled"
  | "live"
  | "completed"
  | "postponed"
  | "cancelled"
  | "abandoned";

export type PublicCompetition = {
  readonly id: string;
  readonly sportId: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName: string | null;
  readonly countryCode: string | null;
};

export type PublicSeason = {
  readonly id: string;
  readonly competitionId: string;
  readonly slug: string;
  readonly name: string;
  readonly startsOn: string | null;
  readonly endsOn: string | null;
  readonly competition: Pick<
    PublicCompetition,
    "id" | "slug" | "name" | "shortName"
  >;
};

export type PublicRound = {
  readonly id: string;
  readonly seasonId: string;
  readonly round: string;
  readonly name: string;
  readonly displayOrder: number;
  readonly startsAt: string | null;
  readonly endsAt: string | null;
  readonly season: Pick<PublicSeason, "id" | "slug" | "name">;
  readonly competition: Pick<
    PublicCompetition,
    "id" | "slug" | "name" | "shortName"
  >;
};

export type PublicTeamSummary = {
  readonly id: string;
  readonly name: string;
  readonly shortName: string | null;
  readonly displayName: string | null;
  readonly logoUrl: string | null;
  readonly badgeUrl: string | null;
};

export type PublicFixture = {
  readonly id: string;
  readonly kickoffTime: string;
  readonly venue: string | null;
  readonly status: FixtureStatus;
  readonly homeScore: number | null;
  readonly awayScore: number | null;
  readonly homeTeam: PublicTeamSummary;
  readonly awayTeam: PublicTeamSummary;
  readonly round: {
    readonly id: string | null;
    readonly round: string;
    readonly name: string;
    readonly displayOrder: number | null;
  };
  readonly season: Pick<PublicSeason, "id" | "slug" | "name">;
  readonly competition: Pick<
    PublicCompetition,
    "id" | "slug" | "name" | "shortName"
  >;
};

export type Prediction = {
  readonly id: string;
  readonly userId: string;
  readonly fixtureId: string;
  readonly homeScore: number;
  readonly awayScore: number;
  readonly createdAt: string;
  readonly updatedAt: string;
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

export type AuthUser = {
  readonly id: string;
  readonly email: string;
  readonly role: string;
};

export type AuthResponse = {
  readonly user?: AuthUser | null;
  readonly accessToken: string;
  readonly refreshToken: string;
  readonly profile?: unknown;
  readonly session?: unknown;
};
