export const fixtureStatuses = [
  "scheduled",
  "postponed",
  "abandoned",
  "void",
  "cancelled",
  "completed",
] as const;

export type FixtureStatus = (typeof fixtureStatuses)[number];

export type AdminFixture = {
  readonly id: string;
  readonly sportId: string;
  readonly competitionId: string;
  readonly seasonId: string;
  readonly roundId: string | null;
  readonly round: string;
  readonly roundName: string;
  readonly roundOrder: number | null;
  readonly homeTeamId: string;
  readonly awayTeamId: string;
  readonly scheduledAt: string;
  readonly originalScheduledAt: string | null;
  readonly venueName: string | null;
  readonly status: FixtureStatus;
  readonly homeScore: number | null;
  readonly awayScore: number | null;
  readonly resultSource: string | null;
  readonly resultEnteredAt: string | null;
  readonly resultEnteredBy: string | null;
  readonly legacyMatchId: number | null;
  readonly legacyFixtureId: string | null;
  readonly externalSource: string | null;
  readonly externalId: string | null;
};

export type FixtureListFilters = {
  readonly competitionId?: string | null;
  readonly seasonId?: string | null;
  readonly round?: string | null;
  readonly status?: FixtureStatus | null;
  readonly dateFrom?: string | null;
  readonly dateTo?: string | null;
};

export type FixtureWritePayload = {
  readonly sportId: string;
  readonly competitionId: string;
  readonly seasonId: string;
  readonly roundId?: string | null;
  readonly round: string;
  readonly roundName: string;
  readonly roundOrder?: number | null;
  readonly homeTeamId: string;
  readonly awayTeamId: string;
  readonly scheduledAt: string;
  readonly originalScheduledAt?: string | null;
  readonly venueName?: string | null;
  readonly status?: FixtureStatus;
  readonly homeScore?: number | null;
  readonly awayScore?: number | null;
  readonly legacyMatchId?: number | null;
  readonly legacyFixtureId?: string | null;
  readonly externalSource?: string | null;
  readonly externalId?: string | null;
};

export type FixtureTransitionPayload = {
  readonly status: FixtureStatus;
  readonly preserveScores?: boolean;
  readonly partialHomeScore?: number;
  readonly partialAwayScore?: number;
};

export type FixtureResultPayload = {
  readonly homeScore: number;
  readonly awayScore: number;
  readonly resultSource?: string | null;
};

export type FixtureCorrectionPayload = FixtureResultPayload & {
  readonly reason: string;
};

export type FixtureListResponse = {
  readonly data: readonly AdminFixture[];
  readonly meta: {
    readonly page: number;
    readonly limit: number;
    readonly total: number;
    readonly hasMore: boolean;
  };
};
