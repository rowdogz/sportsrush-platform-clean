import { defineConfig } from "vitest/config";

/**
 * Standard Vitest config for the API package.
 *
 * We run tests with the 'node' environment (not the Cloudflare Workers pool)
 * because PR-04 has no Worker-specific bindings (D1, KV, Queues).
 * Hono's app.request() works in Node.js — no miniflare required.
 *
 * When D1/KV tests are added (PR-05+), switch to:
 *   @cloudflare/vitest-pool-workers
 * and update this file accordingly.
 */
export default defineConfig({
  test: {
    environment: "node",
    globals: false,
  },
});
