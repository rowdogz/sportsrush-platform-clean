import { z } from "zod";
import { UUIDSchema } from "./common";

// TODO: Implement full league validation schemas (Phase 2)
// Reference: SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 7 (Private Leagues)

export const LeagueTypeSchema = z.enum(["free", "paid"]);

export const CreateLeagueSchema = z.object({
  name: z.string().min(1).max(100).trim(),
  competitionId: UUIDSchema,
  description: z.string().max(500).trim().nullable().optional(),
  type: LeagueTypeSchema,
  maxMembers: z.number().int().min(2).max(10000).nullable().optional(),
});

export const AddLeagueMemberSchema = z.object({
  userId: UUIDSchema,
  reason: z.string().max(500).trim().optional(),
});

export type CreateLeagueInput = z.infer<typeof CreateLeagueSchema>;
export type AddLeagueMemberInput = z.infer<typeof AddLeagueMemberSchema>;
