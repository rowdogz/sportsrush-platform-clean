import { execSync } from "node:child_process";
import { existsSync, rmSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(scriptDir, "../..");
const apiRoot = path.join(repoRoot, "apps", "api");
const d1StateDir = path.join(
  apiRoot,
  ".wrangler",
  "state",
  "v3",
  "d1",
  "miniflare-D1DatabaseObject",
);

if (existsSync(d1StateDir)) {
  rmSync(d1StateDir, { recursive: true, force: true });
  console.log(`Removed local D1 state: ${d1StateDir}`);
} else {
  console.log(`No local D1 state found at: ${d1StateDir}`);
}

execSync("pnpm db:migrate:local", {
  cwd: apiRoot,
  stdio: "inherit",
});

execSync("pnpm db:seed:dev", {
  cwd: apiRoot,
  stdio: "inherit",
});

console.log("Local D1 database reset, migrated, and re-seeded.");
