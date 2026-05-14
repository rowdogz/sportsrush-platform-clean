/**
 * API version constant.
 * Keep this in sync with apps/api/package.json "version".
 * This is embedded at build time — Cloudflare Workers cannot read the filesystem
 * at runtime, so we cannot dynamically import package.json.
 */
export const API_VERSION = "0.0.1";

/**
 * Minimum supported API client version.
 * Clients below this version will receive a deprecation warning header.
 * Bumped manually when a breaking change is shipped.
 */
export const MIN_CLIENT_VERSION = "0.0.1";
