import { z } from "zod";
import { UUIDSchema, TimestampSchema } from "./common";

// TODO: Implement full fixture validation schemas (Phase 2)
// Reference: SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 4 (Fixtures & Results)

export const MatchStatusSchema = z.enum([
  "scheduled",
  "completed",
  "postponed",
  "abandoned",
  "void",
]);

export const CreateMatchSchema = z.object({
  competitionId: UUIDSchema,
  homeTeamId: UUIDSchema,
  awayTeamId: UUIDSchema,
  playDate: TimestampSchema,
  round: z.number().int().min(1),
  roundName: z.string().max(100).trim().nullable().optional(),
});

export const EnterResultSchema = z.object({
  homeScore: z.number().int().min(0).max(99),
  awayScore: z.number().int().min(0).max(99),
});

export const CorrectResultSchema = z.object({
  homeScore: z.number().int().min(0).max(99),
  awayScore: z.number().int().min(0).max(99),
  reason: z
    .string()
    .min(1, { message: "Reason is required for result corrections" })
    .max(500),
});

export type CreateMatchInput = z.infer<typeof CreateMatchSchema>;
export type EnterResultInput = z.infer<typeof EnterResultSchema>;
export type CorrectResultInput = z.infer<typeof CorrectResultSchema>;
