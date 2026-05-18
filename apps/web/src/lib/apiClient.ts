import type {
  AuthMe,
  AuthResponse,
  LeaderboardEntry,
  PaginatedResult,
  Prediction,
  PrivateLeagueDetail,
  PrivateLeagueSummary,
  PublicCompetition,
  PublicFixture,
  PublicRound,
  PublicSeason,
} from "../features/types";

type ApiErrorBody = {
  readonly code?: string;
  readonly message?: string;
  readonly details?: unknown;
  readonly correlationId?: string;
};

type ApiErrorResponse = {
  readonly error?: ApiErrorBody;
};

type ApiSuccessResponse<T> = {
  readonly data: T;
  readonly meta?: PaginatedResult<
    T extends readonly (infer Item)[] ? Item : T
  >["meta"];
};

export type ApiRequestOptions = {
  readonly method?: string;
  readonly body?: unknown;
  readonly token?: string | null;
  readonly headers?: HeadersInit;
};

export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly details: unknown;

  constructor(
    status: number,
    code: string,
    message: string,
    details?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

function apiBaseUrl(): string {
  if (import.meta.env.MODE === "test") return "";
  const configuredBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? "")
    .trim()
    .replace(/\/$/, "");
  if (configuredBaseUrl) return configuredBaseUrl;
  if (import.meta.env.DEV) return "http://localhost:8788";
  return "";
}

function buildUrl(path: string): string {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${apiBaseUrl()}${normalizedPath}`;
}

async function readJson(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) return null;
  return JSON.parse(text) as unknown;
}

function errorBody(payload: unknown): ApiErrorBody | undefined {
  if (!payload || typeof payload !== "object" || !("error" in payload)) {
    return undefined;
  }
  const error = (payload as ApiErrorResponse).error;
  return error && typeof error === "object" ? error : undefined;
}

export async function apiRequest<T>(
  path: string,
  options: ApiRequestOptions = {},
): Promise<T> {
  const headers = new Headers({ Accept: "application/json" });
  if (options.headers) {
    new Headers(options.headers).forEach((value, key) =>
      headers.set(key, value),
    );
  }
  if (options.token) {
    headers.set("Authorization", `Bearer ${options.token}`);
  }

  const requestInit: RequestInit = { headers };
  if (options.method) requestInit.method = options.method;
  if (options.body !== undefined) {
    headers.set("Content-Type", "application/json");
    requestInit.body = JSON.stringify(options.body);
  }

  let response: Response;
  try {
    response = await fetch(buildUrl(path), requestInit);
  } catch (error) {
    throw new ApiError(
      0,
      "network_error",
      error instanceof Error
        ? error.message
        : "Unable to reach the SportsRush API.",
    );
  }

  const payload = await readJson(response);
  if (!response.ok) {
    const body = errorBody(payload);
    throw new ApiError(
      response.status,
      body?.code ?? "http_error",
      body?.message ?? `Request failed with ${response.status}`,
      body?.details,
    );
  }
  return payload as T;
}

function unwrap<T>(response: ApiSuccessResponse<T> | T): T {
  if (response && typeof response === "object" && "data" in response) {
    return (response as ApiSuccessResponse<T>).data;
  }
  return response as T;
}

function unwrapPaginated<T>(
  response: ApiSuccessResponse<readonly T[]>,
): PaginatedResult<T> {
  return {
    data: response.data,
    meta: response.meta ?? {
      page: 1,
      limit: response.data.length,
      total: response.data.length,
      hasMore: false,
    },
  };
}

export async function listCompetitions(): Promise<
  PaginatedResult<PublicCompetition>
> {
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly PublicCompetition[]>>(
      "/v1/public/competitions?page=1&limit=50",
    ),
  );
}

export async function listSeasons(
  competitionId?: string,
): Promise<PaginatedResult<PublicSeason>> {
  const search = new URLSearchParams({ page: "1", limit: "50" });
  if (competitionId) search.set("competitionId", competitionId);
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly PublicSeason[]>>(
      `/v1/public/seasons?${search.toString()}`,
    ),
  );
}

export async function listRounds(
  competitionId?: string,
  seasonId?: string,
): Promise<PaginatedResult<PublicRound>> {
  const search = new URLSearchParams({ page: "1", limit: "50" });
  if (competitionId) search.set("competitionId", competitionId);
  if (seasonId) search.set("seasonId", seasonId);
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly PublicRound[]>>(
      `/v1/public/rounds?${search.toString()}`,
    ),
  );
}

export type FixtureFilters = {
  readonly status?: string;
  readonly competitionId?: string;
  readonly seasonId?: string;
  readonly roundId?: string;
  readonly fromDate?: string;
  readonly toDate?: string;
};

export async function listFixtures(
  filters: FixtureFilters = {},
): Promise<PaginatedResult<PublicFixture>> {
  const search = new URLSearchParams({ page: "1", limit: "50" });
  Object.entries(filters).forEach(([key, value]) => {
    if (value) search.set(key, value);
  });
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly PublicFixture[]>>(
      `/v1/public/fixtures?${search.toString()}`,
    ),
  );
}

export async function getFixture(id: string): Promise<PublicFixture> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<PublicFixture>>(
      `/v1/public/fixtures/${id}`,
    ),
  );
}

export async function listLeaderboards(
  competitionId?: string,
): Promise<PaginatedResult<LeaderboardEntry>> {
  const search = new URLSearchParams({ page: "1", limit: "25" });
  if (competitionId) search.set("competitionId", competitionId);
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly LeaderboardEntry[]>>(
      `/v1/public/leaderboards?${search.toString()}`,
    ),
  );
}

export async function login(
  email: string,
  password: string,
): Promise<AuthResponse> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<AuthResponse>>("/v1/auth/login", {
      method: "POST",
      body: { email, password },
    }),
  );
}

export async function register(
  email: string,
  password: string,
  displayName?: string,
): Promise<AuthResponse> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<AuthResponse>>("/v1/auth/register", {
      method: "POST",
      body: { email, password, displayName },
    }),
  );
}

export async function refresh(refreshToken: string): Promise<AuthResponse> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<AuthResponse>>("/v1/auth/refresh", {
      method: "POST",
      body: { refreshToken },
    }),
  );
}

export async function requestPasswordReset(email: string): Promise<string> {
  const response = unwrap(
    await apiRequest<ApiSuccessResponse<{ readonly message: string }>>(
      "/v1/auth/request-password-reset",
      { method: "POST", body: { email } },
    ),
  );
  return response.message;
}

export async function confirmPasswordReset(
  token: string,
  newPassword: string,
): Promise<AuthResponse> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<AuthResponse>>(
      "/v1/auth/confirm-password-reset",
      { method: "POST", body: { token, newPassword } },
    ),
  );
}

export async function getMe(token: string): Promise<AuthMe> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<AuthMe>>("/v1/auth/me", {
      token,
    }),
  );
}

export async function listPrivateLeagues(
  token: string,
  search?: string,
): Promise<PaginatedResult<PrivateLeagueSummary>> {
  const query = new URLSearchParams({ page: "1", limit: "25" });
  if (search?.trim()) query.set("search", search.trim());
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly PrivateLeagueSummary[]>>(
      `/v1/private-leagues?${query.toString()}`,
      { token },
    ),
  );
}

export async function getPrivateLeague(
  token: string,
  id: string,
): Promise<PrivateLeagueDetail> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<PrivateLeagueDetail>>(
      `/v1/private-leagues/${id}`,
      { token },
    ),
  );
}

export async function joinPrivateLeague(
  token: string,
  inviteCode: string,
): Promise<PrivateLeagueDetail> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<PrivateLeagueDetail>>(
      "/v1/private-leagues/join",
      {
        method: "POST",
        token,
        body: { inviteCode },
      },
    ),
  );
}

export async function savePrediction(
  token: string,
  fixtureId: string,
  homeScore: number,
  awayScore: number,
  mode: "create" | "update",
): Promise<Prediction> {
  return unwrap(
    await apiRequest<ApiSuccessResponse<Prediction>>("/v1/predictions", {
      method: mode === "create" ? "POST" : "PATCH",
      token,
      body: { fixtureId, homeScore, awayScore },
    }),
  );
}

export async function listMyPredictions(
  token: string,
): Promise<PaginatedResult<Prediction>> {
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly Prediction[]>>(
      "/v1/predictions/me?page=1&limit=100",
      { token },
    ),
  );
}

export async function listMyLeaderboards(
  token: string,
  filters: {
    readonly competitionId?: string;
    readonly privateLeagueId?: string;
  } = {},
): Promise<PaginatedResult<LeaderboardEntry>> {
  const search = new URLSearchParams({ page: "1", limit: "25" });
  if (filters.competitionId) search.set("competitionId", filters.competitionId);
  if (filters.privateLeagueId) {
    search.set("privateLeagueId", filters.privateLeagueId);
  }
  return unwrapPaginated(
    await apiRequest<ApiSuccessResponse<readonly LeaderboardEntry[]>>(
      `/v1/predictions/leaderboards?${search.toString()}`,
      { token },
    ),
  );
}
