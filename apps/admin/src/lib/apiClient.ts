type ApiErrorBody = {
  readonly code?: string;
  readonly message?: string;
  readonly details?: unknown;
  readonly correlationId?: string;
};

type ApiErrorResponse = {
  readonly error?: ApiErrorBody;
};

type AccessTokenProvider = () => string | null;

export type ApiRequestOptions = {
  readonly method?: string;
  readonly body?: unknown;
  readonly headers?: HeadersInit;
};

let accessTokenProvider: AccessTokenProvider = () => null;

export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly details: unknown;
  readonly correlationId: string | undefined;

  constructor({
    status,
    code,
    message,
    details,
    correlationId,
  }: {
    readonly status: number;
    readonly code: string;
    readonly message: string;
    readonly details?: unknown;
    readonly correlationId?: string;
  }) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
    this.details = details;
    this.correlationId = correlationId;
  }
}

function getApiBaseUrl(): string {
  return (import.meta.env.VITE_API_BASE_URL ?? "").trim().replace(/\/$/, "");
}

export function setAccessTokenProvider(provider: AccessTokenProvider): void {
  accessTokenProvider = provider;
}

function getAccessToken(): string | null {
  return accessTokenProvider();
}

function buildUrl(path: string): string {
  const baseUrl = getApiBaseUrl();
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${baseUrl}${normalizedPath}`;
}

async function readJson(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) return null;

  try {
    return JSON.parse(text) as unknown;
  } catch {
    throw new ApiError({
      status: response.status,
      code: "invalid_json",
      message: "The API returned an invalid JSON response.",
    });
  }
}

function getErrorBody(payload: unknown): ApiErrorBody | undefined {
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
  const token = getAccessToken();
  const headers = new Headers({ Accept: "application/json" });

  if (options.headers) {
    new Headers(options.headers).forEach((value, key) => {
      headers.set(key, value);
    });
  }

  let body: BodyInit | undefined;
  if (options.body !== undefined) {
    headers.set("Content-Type", "application/json");
    body = JSON.stringify(options.body);
  }

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const requestInit: RequestInit = { headers };
  if (options.method) {
    requestInit.method = options.method;
  }
  if (body !== undefined) {
    requestInit.body = body;
  }

  let response: Response;
  try {
    response = await fetch(buildUrl(path), requestInit);
  } catch (error) {
    throw new ApiError({
      status: 0,
      code: "network_error",
      message:
        error instanceof Error
          ? error.message
          : "Unable to reach the SportsRush API.",
    });
  }

  const payload = await readJson(response);

  if (!response.ok) {
    const errorBody = getErrorBody(payload);
    throw new ApiError({
      status: response.status,
      code: errorBody?.code ?? "http_error",
      message: errorBody?.message ?? `Request failed with ${response.status}`,
      ...(errorBody && "details" in errorBody
        ? { details: errorBody.details }
        : {}),
      ...(errorBody?.correlationId
        ? { correlationId: errorBody.correlationId }
        : {}),
    });
  }

  return payload as T;
}
