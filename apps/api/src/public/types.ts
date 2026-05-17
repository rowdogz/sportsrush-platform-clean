export type PublicFixtureStatus =
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
  readonly status: PublicFixtureStatus;
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
