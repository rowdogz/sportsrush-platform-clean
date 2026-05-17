import { z } from "zod";

export const PredictionWriteSchema = z.object({
  fixtureId: z.string().min(1),
  homeScore: z.number().int().nonnegative(),
  awayScore: z.number().int().nonnegative(),
});

export const PredictionListQuerySchema = z.object({
  page: z.coerce.number().int().positive().default(1),
  limit: z.coerce.number().int().positive().max(100).default(25),
  competitionId: z.string().min(1).optional(),
  roundId: z.string().min(1).optional(),
  privateLeagueId: z.string().min(1).optional(),
  month: z
    .string()
    .regex(/^[0-9]{4}-[0-9]{2}$/)
    .optional(),
});

export type PredictionWriteInput = z.infer<typeof PredictionWriteSchema>;
export type PredictionListQuery = z.infer<typeof PredictionListQuerySchema>;
