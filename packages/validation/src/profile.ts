import { z } from "zod";
import { UUIDSchema } from "./common";

// TODO: Implement full profile validation schemas (Phase 2)
// Reference: SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 2 (Users & Profiles)

export const UpdatePreferencesSchema = z.object({
  defaultCompetitionId: UUIDSchema.nullable().optional(),
  notifyOnResults: z.boolean().optional(),
  notifyOnRoundOpen: z.boolean().optional(),
  notifyOnMonthlyWinner: z.boolean().optional(),
});

export const UpdateTimezoneSchema = z.object({
  // IANA timezone string — validated against a known list in the service layer
  timezone: z.string().min(1).max(64).trim(),
});

export type UpdatePreferencesInput = z.infer<typeof UpdatePreferencesSchema>;
export type UpdateTimezoneInput = z.infer<typeof UpdateTimezoneSchema>;
