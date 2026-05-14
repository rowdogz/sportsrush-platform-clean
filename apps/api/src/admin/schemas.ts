import { z } from "zod";

export const UuidSchema = z.string().min(1);
export const IsoTimestampSchema = z.string().datetime({ offset: true });
export const OptionalLegacyIdSchema = z.string().min(1).optional();

export const FixtureStatusSchema = z.enum([
  "scheduled",
  "postponed",
  "abandoned",
  "void",
  "cancelled",
  "completed",
]);

export type FixtureStatus = z.infer<typeof FixtureStatusSchema>;

export const CreateCompetitionSchema = z.object({
  sportId: UuidSchema,
  slug: z.string().min(1),
  name: z.string().min(1),
  shortName: z.string().min(1).optional(),
  countryCode: z.string().min(2).max(3).optional(),
  legacyId: OptionalLegacyIdSchema,
});

export const UpdateCompetitionSchema = CreateCompetitionSchema.partial().extend(
  {
    isActive: z.boolean().optional(),
  },
);

export const CreateSeasonSchema = z.object({
  competitionId: UuidSchema,
  slug: z.string().min(1),
  name: z.string().min(1),
  startsOn: z.string().min(1).optional(),
  endsOn: z.string().min(1).optional(),
  isActive: z.boolean().optional(),
  legacyId: OptionalLegacyIdSchema,
});

export const UpdateSeasonSchema = CreateSeasonSchema.partial();

export const CreateTeamSchema = z.object({
  sportId: UuidSchema,
  slug: z.string().min(1),
  name: z.string().min(1),
  shortName: z.string().min(1).optional(),
  displayName: z.string().min(1).optional(),
  countryCode: z.string().min(2).max(3).optional(),
  legacyId: OptionalLegacyIdSchema,
});

export const UpdateTeamSchema = CreateTeamSchema.partial().extend({
  isActive: z.boolean().optional(),
});

export const CreateTeamAliasSchema = z.object({
  teamId: UuidSchema,
  sportId: UuidSchema,
  alias: z.string().min(1),
  normalizedAlias: z.string().min(1).optional(),
  source: z.string().min(1).default("manual"),
  priority: z.number().int().default(100),
  isActive: z.boolean().optional(),
  legacyId: OptionalLegacyIdSchema,
});

export const UpdateTeamAliasSchema = CreateTeamAliasSchema.partial();

export const AliasLookupQuerySchema = z.object({
  sportId: UuidSchema,
  source: z.string().min(1).optional(),
  alias: z.string().min(1).optional(),
});

export const CreateRoundSchema = z.object({
  seasonId: UuidSchema,
  round: z.string().min(1),
  roundName: z.string().min(1),
  displayOrder: z.number().int(),
  startsAt: IsoTimestampSchema.optional(),
  endsAt: IsoTimestampSchema.optional(),
  legacyId: OptionalLegacyIdSchema,
});

export const UpdateRoundSchema = CreateRoundSchema.partial();

export const CreateFixtureSchema = z.object({
  sportId: UuidSchema,
  competitionId: UuidSchema,
  seasonId: UuidSchema,
  roundId: UuidSchema.optional(),
  round: z.string().min(1),
  roundName: z.string().min(1),
  roundOrder: z.number().int().optional(),
  homeTeamId: UuidSchema,
  awayTeamId: UuidSchema,
  scheduledAt: IsoTimestampSchema,
  originalScheduledAt: IsoTimestampSchema.optional(),
  venueName: z.string().min(1).optional(),
  status: FixtureStatusSchema.default("scheduled"),
  homeScore: z.number().int().nonnegative().optional(),
  awayScore: z.number().int().nonnegative().optional(),
  legacyMatchId: z.number().int().optional(),
  legacyFixtureId: z.string().min(1).optional(),
  externalSource: z.string().min(1).optional(),
  externalId: z.string().min(1).optional(),
});

export const UpdateFixtureSchema = CreateFixtureSchema.partial();

export const FixtureListQuerySchema = z.object({
  competitionId: UuidSchema.optional(),
  seasonId: UuidSchema.optional(),
  round: z.string().min(1).optional(),
  status: FixtureStatusSchema.optional(),
  dateFrom: IsoTimestampSchema.optional(),
  dateTo: IsoTimestampSchema.optional(),
});

export const FixtureStatusUpdateSchema = z.object({
  status: FixtureStatusSchema,
});

export const EnterResultSchema = z.object({
  homeScore: z.number().int().nonnegative(),
  awayScore: z.number().int().nonnegative(),
  resultSource: z.string().min(1).optional(),
});

export const CorrectResultSchema = EnterResultSchema.extend({
  reason: z.string().min(1),
});

export type CreateCompetitionInput = z.infer<typeof CreateCompetitionSchema>;
export type UpdateCompetitionInput = z.infer<typeof UpdateCompetitionSchema>;
export type CreateSeasonInput = z.infer<typeof CreateSeasonSchema>;
export type UpdateSeasonInput = z.infer<typeof UpdateSeasonSchema>;
export type CreateTeamInput = z.infer<typeof CreateTeamSchema>;
export type UpdateTeamInput = z.infer<typeof UpdateTeamSchema>;
export type CreateTeamAliasInput = z.infer<typeof CreateTeamAliasSchema>;
export type UpdateTeamAliasInput = z.infer<typeof UpdateTeamAliasSchema>;
export type CreateRoundInput = z.infer<typeof CreateRoundSchema>;
export type UpdateRoundInput = z.infer<typeof UpdateRoundSchema>;
export type CreateFixtureInput = z.infer<typeof CreateFixtureSchema>;
export type UpdateFixtureInput = z.infer<typeof UpdateFixtureSchema>;
export type FixtureListQuery = z.infer<typeof FixtureListQuerySchema>;
export type EnterResultInput = z.infer<typeof EnterResultSchema>;
export type CorrectResultInput = z.infer<typeof CorrectResultSchema>;
