import { z } from "zod";

export const PublicFixtureStatusQuerySchema = z.enum([
  "scheduled",
  "live",
  "completed",
  "postponed",
  "cancelled",
  "abandoned",
]);

export const PublicPaginationQuerySchema = z.object({
  page: z.coerce.number().int().positive().default(1),
  limit: z.coerce.number().int().positive().max(100).default(25),
});

export const PublicFixtureListQuerySchema = PublicPaginationQuerySchema.extend({
  competitionId: z.string().min(1).optional(),
  seasonId: z.string().min(1).optional(),
  roundId: z.string().min(1).optional(),
  status: PublicFixtureStatusQuerySchema.optional(),
  fromDate: z.string().datetime({ offset: true }).optional(),
  toDate: z.string().datetime({ offset: true }).optional(),
});

export const PublicCompetitionListQuerySchema = PublicPaginationQuerySchema;
export const PublicSeasonListQuerySchema = PublicPaginationQuerySchema.extend({
  competitionId: z.string().min(1).optional(),
});
export const PublicRoundListQuerySchema = PublicPaginationQuerySchema.extend({
  competitionId: z.string().min(1).optional(),
  seasonId: z.string().min(1).optional(),
});

export const PublicIdParamSchema = z.object({
  id: z.string().min(1),
});

export type PublicPaginationQuery = {
  readonly page: number;
  readonly limit: number;
};

export type PublicFixtureListQuery = PublicPaginationQuery & {
  readonly competitionId?: string | undefined;
  readonly seasonId?: string | undefined;
  readonly roundId?: string | undefined;
  readonly status?:
    | "scheduled"
    | "live"
    | "completed"
    | "postponed"
    | "cancelled"
    | "abandoned"
    | undefined;
  readonly fromDate?: string | undefined;
  readonly toDate?: string | undefined;
};

export type PublicSeasonListQuery = PublicPaginationQuery & {
  readonly competitionId?: string | undefined;
};

export type PublicRoundListQuery = PublicPaginationQuery & {
  readonly competitionId?: string | undefined;
  readonly seasonId?: string | undefined;
};
