export type AdminRound = {
  readonly id: string;
  readonly seasonId: string;
  readonly round: string;
  readonly roundName: string;
  readonly displayOrder: number;
  readonly startsAt: string | null;
  readonly endsAt: string | null;
  readonly legacyId: string | null;
};

export type RoundListFilters = {
  readonly seasonId: string;
};

export type RoundWritePayload = {
  readonly seasonId: string;
  readonly round: string;
  readonly roundName: string;
  readonly displayOrder: number;
  readonly startsAt?: string | null;
  readonly endsAt?: string | null;
  readonly legacyId?: string | null;
};

export type RoundListResponse = {
  readonly data: readonly AdminRound[];
};
