/**
 * SportsRush API — Cloudflare Worker entry point.
 *
 * This file is the Cloudflare Worker `main` module (see wrangler.toml).
 * It does nothing except create the Hono app and export it as the default
 * export, which the Workers runtime calls for every incoming request.
 *
 * All application logic lives in src/app.ts and the src/ subdirectories.
 * Keep this file minimal — it exists only to satisfy the Workers module contract.
 */
import { createApp } from "./app";

const app = createApp();

export default app;
