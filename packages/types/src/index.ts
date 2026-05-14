/**
 * @sr/types — Canonical domain types for the SportsRush platform.
 *
 * This is the single import path for all shared types:
 *
 *   import type { UUID, Match, MatchScore, PrivateLeague } from '@sr/types'
 *
 * Rules:
 *   - This package contains ONLY types — zero runtime code.
 *   - Every property on every type is readonly.
 *   - No `any` types. Use `unknown` for truly dynamic values.
 *   - UUID and Timestamp are branded types — they cannot be accidentally
 *     swapped with plain strings.
 *
 * See packages/types/README.md for full documentation.
 */

export type * from "./common";
export type * from "./auth";
export type * from "./profile";
export type * from "./competition";
export type * from "./fixture";
export type * from "./prediction";
export type * from "./scoring";
export type * from "./ranking";
export type * from "./league";
export type * from "./payment";
export type * from "./audit";
export type * from "./events";
export type * from "./scraper";
export type * from "./notification";
