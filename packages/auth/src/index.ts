/**
 * @sr/auth — Auth utilities for the SportsRush platform.
 *
 * All functions use the Web Crypto API (crypto.subtle) and are compatible
 * with Cloudflare Workers, Deno, and Node.js 20+. No Node.js native modules.
 *
 * Usage:
 *   import { hashPassword, verifyPassword } from '@sr/auth'
 *   import { createAccessToken, verifyAccessToken } from '@sr/auth'
 *   import { hasRole, assertRole, RoleError } from '@sr/auth'
 *   import { generateRefreshToken } from '@sr/auth'
 */

export * from "./constants";
export * from "./hash";
export * from "./jwt";
export * from "./roles";
export * from "./session";
