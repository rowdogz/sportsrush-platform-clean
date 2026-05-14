import { z } from "zod";

// TODO: Implement full prediction validation schemas (Phase 2)
// Reference: SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 5 (Predictions)
// Note: Lock window enforcement (play_date - lock_minutes) is validated
// at the service layer, not here.

export const PredictionInputSchema = z.object({
  homeScore: z.number().int().min(0).max(99),
  awayScore: z.number().int().min(0).max(99),
  /**
   * Joker flag. Optional — omitting preserves the existing value on update.
   * Requires OWNER DECISION OD-01 before the joker limit per round is enforced.
   */
  joker: z.boolean().optional(),
});

export const PredictionOverrideSchema = z.object({
  overrideType: z.enum(["open", "lock"]),
  reason: z
    .string()
    .min(1, { message: "Reason is required for prediction overrides" })
    .max(500),
});

export type PredictionInput = z.infer<typeof PredictionInputSchema>;
export type PredictionOverrideInput = z.infer<typeof PredictionOverrideSchema>;
