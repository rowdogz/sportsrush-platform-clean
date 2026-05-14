/**
 * @sr/validation — Zod schemas for all SportsRush domain inputs.
 *
 * Usage:
 *   import { RegisterSchema, LoginSchema } from '@sr/validation'
 *   import type { RegisterInput } from '@sr/validation'
 *
 * Rules:
 *   - Every schema enforces .max() on every string field (prevents oversized payloads).
 *   - Email fields use .email() and .toLowerCase() normalisation.
 *   - Input types are derived with z.infer<typeof Schema> — not hand-written.
 *   - Schema names use PascalCase with a Schema suffix.
 *   - Inferred input types use PascalCase with an Input suffix.
 *
 * Domain stubs (competition, fixture, prediction, league, profile) are partially
 * implemented — full schemas are completed as each domain is built.
 */

export * from "./common";
export * from "./auth";
export * from "./profile";
export * from "./competition";
export * from "./fixture";
export * from "./prediction";
export * from "./league";
