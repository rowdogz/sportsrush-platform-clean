import { z } from "zod";

export const PrivateLeagueIdSchema = z.string().min(1);

export const PrivateLeagueWriteSchema = z.object({
  slug: z.string().min(1),
  name: z.string().min(1),
  description: z.string().min(1).nullable().optional(),
  logoUrl: z.string().url().nullable().optional(),
  bannerUrl: z.string().url().nullable().optional(),
  ownerUserId: z.string().min(1).nullable().optional(),
  competitionIds: z.array(z.string().min(1)).default([]),
});

export const PrivateLeagueUpdateSchema = PrivateLeagueWriteSchema.partial();

export const PrivateLeagueMemberWriteSchema = z.object({
  userId: z.string().min(1),
  role: z.enum(["owner", "admin", "member"]).default("member"),
});

export const PrivateLeagueListQuerySchema = z.object({
  page: z.coerce.number().int().positive().default(1),
  limit: z.coerce.number().int().positive().max(100).default(25),
  search: z.string().min(1).optional(),
  includeArchived: z.enum(["true", "false"]).optional(),
});

export const PrivateLeagueJoinSchema = z.object({
  inviteCode: z.string().trim().min(4).max(32),
});

export type PrivateLeagueWriteInput = z.infer<typeof PrivateLeagueWriteSchema>;
export type PrivateLeagueUpdateInput = z.infer<
  typeof PrivateLeagueUpdateSchema
>;
export type PrivateLeagueMemberWriteInput = z.infer<
  typeof PrivateLeagueMemberWriteSchema
>;
export type PrivateLeagueListQuery = z.infer<
  typeof PrivateLeagueListQuerySchema
>;
export type PrivateLeagueJoinInput = z.infer<typeof PrivateLeagueJoinSchema>;
