import type { LeaderboardEntry, PaginatedResult, PublicFixture } from "./types";

const API_BASE_URL = "http://localhost:8787";

async function request<T>(
  path: string,
  options: {
    readonly method?: string;
    readonly token?: string;
    readonly body?: unknown;
  } = {},
): Promise<T> {
  const headers = new Headers({ Accept: "application/json" });
  if (options.token) headers.set("Authorization", `Bearer ${options.token}`);
  const init: RequestInit = { headers };
  if (options.method) init.method = options.method;
  if (options.body !== undefined) {
    headers.set("Content-Type", "application/json");
    init.body = JSON.stringify(options.body);
  }
  const response = await fetch(`${API_BASE_URL}${path}`, init);
  const payload = (await response.json()) as T & {
    readonly error?: { readonly message?: string };
  };
  if (!response.ok) {
    throw new Error(
      payload.error?.message ?? `Request failed with ${response.status}`,
    );
  }
  return payload;
}

export async function listFixtures(): Promise<PaginatedResult<PublicFixture>> {
  return request<PaginatedResult<PublicFixture>>(
    "/v1/public/fixtures?page=1&limit=25",
  );
}

export async function listLeaderboards(): Promise<
  PaginatedResult<LeaderboardEntry>
> {
  return request<PaginatedResult<LeaderboardEntry>>(
    "/v1/public/leaderboards?page=1&limit=25",
  );
}

export async function savePrediction(
  token: string,
  fixtureId: string,
  homeScore: number,
  awayScore: number,
): Promise<void> {
  await request("/v1/predictions", {
    method: "POST",
    token,
    body: { fixtureId, homeScore, awayScore },
  });
}
