const checks = [
  {
    name: "API health",
    url: "http://localhost:8788/health",
    assert: (body) => body?.data?.status === "ok",
  },
  {
    name: "Public fixtures",
    url: "http://localhost:8788/v1/public/fixtures?page=1&limit=1",
    assert: (body) => Array.isArray(body?.data),
  },
  {
    name: "Web app",
    url: "http://localhost:3000",
    assert: (body) => typeof body === "string" && body.includes("SportsRush"),
    text: true,
  },
  {
    name: "Admin app",
    url: "http://localhost:3001",
    assert: (body) => typeof body === "string" && body.includes("SportsRush"),
    text: true,
  },
];

let failed = false;

for (const check of checks) {
  try {
    const response = await fetch(check.url);
    const body = check.text ? await response.text() : await response.json();
    if (!response.ok || !check.assert(body)) {
      throw new Error(`Unexpected response (${response.status})`);
    }
    console.log(`✓ ${check.name} — ${check.url}`);
  } catch (error) {
    failed = true;
    console.error(
      `✗ ${check.name} — ${check.url} — ${
        error instanceof Error ? error.message : "Unknown error"
      }`,
    );
  }
}

if (failed) {
  process.exitCode = 1;
} else {
  console.log("Local platform healthcheck passed.");
}
