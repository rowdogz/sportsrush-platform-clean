import { z } from "zod";

// TODO: Implement full competition validation schemas (Phase 2)
// Reference: SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 3 (Competitions)

export const SportSchema = z.enum([
  "rugby_league",
  "football",
  "rugby_union",
  "cricket",
  "other",
]);

export const CompetitionVisibilitySchema = z.enum([
  "public",
  "unlisted",
  "archived",
]);

export const CreateCompetitionSchema = z.object({
  name: z.string().min(1).max(100).trim(),
  sport: SportSchema,
  description: z.string().max(500).trim().nullable().optional(),
  logoUrl: z.string().url().max(500).nullable().optional(),
  visibility: CompetitionVisibilitySchema.default("public"),
  displayOrder: z.number().int().min(0).default(0),
});

export type CreateCompetitionInput = z.infer<typeof CreateCompetitionSchema>;
