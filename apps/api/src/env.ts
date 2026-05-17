/**
 * Cloudflare Worker environment bindings for the SportsRush API.
 *
 * The `Env` type is the single source of truth for all runtime bindings.
 * Every binding must be declared here AND in wrangler.toml before it can
 * be used in handler code.
 *
 * Naming conventions:
 *   SCREAMING_SNAKE_CASE  — wrangler [vars] and secrets
 *   PascalCase            — D1Database, KVNamespace, Queue (added in PR-05+)
 *
 * DO NOT add bindings that are not yet wired in wrangler.toml.
 * Adding a type without a wrangler binding silently produces `undefined`
 * at runtime — it will not throw at startup.
 */
export type Env = {
  /** "development" | "staging" | "production" — set via [vars] in wrangler.toml */
  readonly ENVIRONMENT: "development" | "staging" | "production";

  /** Semver string from wrangler.toml [vars] */
  readonly API_VERSION: string;

  /**
   * HS256 JWT signing secret.
   * Set via: wrangler secret put JWT_SECRET
   * or via .dev.vars for local development (file is gitignored).
   * Minimum 32 bytes of cryptographic randomness.
   * Generate: openssl rand -base64 32
   */
  readonly JWT_SECRET: string;

  /**
   * Allowed CORS origin for the web app.
   * In development this defaults to '*' in the CORS middleware.
   * In staging/production set via: wrangler secret put WEB_ORIGIN
   * Example: "https://sportsrush.app"
   */
  readonly WEB_ORIGIN?: string;

  /**
   * Cloudflare D1 database binding.
   * Wired in PR-05. Optional in the type so that tests and future packages
   * that don't need DB access can omit the binding from their mock env.
   *
   * Handlers that require DB MUST guard with:
   *   if (!c.env.DB) throw new InternalError('Database binding is not configured')
   *
   * Local dev: populated automatically by wrangler dev (local SQLite).
   * Remote:    requires a real D1 database — see apps/api/CLOUDFLARE_SETUP.md.
   */
  readonly DB?: D1Database;

  // ── KV Namespace — wired in a future PR ────────────────────────────────────
  // readonly KV: KVNamespace

  // ── Cloudflare Queues — wired in PR-07 ─────────────────────────────────────
  // readonly DOMAIN_EVENTS: Queue
};

/**
 * Hono environment type — passed as the generic to `new Hono<HonoEnv>()`.
 * Bundles Cloudflare bindings (Bindings) and per-request context (Variables).
 */
export type HonoEnv = {
  Bindings: Env;
  Variables: {
    /** UUID v4 set by the logger middleware; propagated on every response. */
    correlationId: string;
    /** Unix ms timestamp of when the request arrived. */
    requestedAt: number;
    /**
     * Set by requireAuth() after successful token verification.
     * Absent on unauthenticated routes — do not access without calling requireAuth first.
     */
    user?: import("@sr/types").TokenPayload;
  };
};

export type EnvValidationResult =
  | { readonly ok: true }
  | { readonly ok: false; readonly issues: readonly string[] };

export function validateEnv(env: Partial<Env>): EnvValidationResult {
  const issues: string[] = [];

  if (
    env.ENVIRONMENT !== "development" &&
    env.ENVIRONMENT !== "staging" &&
    env.ENVIRONMENT !== "production"
  ) {
    issues.push("ENVIRONMENT must be development, staging, or production.");
  }

  if (!env.API_VERSION?.trim()) {
    issues.push("API_VERSION is required.");
  }

  if (!env.JWT_SECRET || env.JWT_SECRET.length < 32) {
    issues.push("JWT_SECRET must be at least 32 characters.");
  }

  if (
    (env.ENVIRONMENT === "staging" || env.ENVIRONMENT === "production") &&
    !env.WEB_ORIGIN?.trim()
  ) {
    issues.push("WEB_ORIGIN is required in staging and production.");
  }

  return issues.length > 0 ? { ok: false, issues } : { ok: true };
}
