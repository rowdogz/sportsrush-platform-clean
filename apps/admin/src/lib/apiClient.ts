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
type UnauthorizedHandler = () => void;

export type ApiRequestOptions = {
  readonly method?: string;
  readonly body?: unknown;
  readonly headers?: HeadersInit;
};

export type ApiTextResponse = {
  readonly text: string;
  readonly headers: Headers;
};

let accessTokenProvider: AccessTokenProvider = () => null;
let unauthorizedHandler: UnauthorizedHandler | null = null;

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
  if (import.meta.env.MODE === "test") return "";
  return (import.meta.env.VITE_API_BASE_URL ?? "").trim().replace(/\/$/, "");
}

export function setAccessTokenProvider(provider: AccessTokenProvider): void {
  accessTokenProvider = provider;
}

export function setUnauthorizedHandler(
  handler: UnauthorizedHandler | null,
): void {
  unauthorizedHandler = handler;
}

function getAccessToken(): string | null {
  return accessTokenProvider();
}

function buildUrl(path: string): string {
  const baseUrl = getApiBaseUrl();
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${baseUrl}${normalizedPath}`;
}

function shouldHandleUnauthorized(path: string): boolean {
  return !path.startsWith("/v1/auth/login");
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
  const response = await sendApiRequest(path, options, "application/json");
  const payload = await readJson(response);

  if (!response.ok) {
    handleAuthFailure(path, response.status);
    throwApiError(response, payload);
  }

  return payload as T;
}

export async function apiTextRequest(
  path: string,
  options: ApiRequestOptions = {},
): Promise<ApiTextResponse> {
  const response = await sendApiRequest(path, options, "text/csv");
  const text = await response.text();

  if (!response.ok) {
    let payload: unknown = null;
    try {
      payload = text ? (JSON.parse(text) as unknown) : null;
    } catch {
      payload = null;
    }
    handleAuthFailure(path, response.status);
    throwApiError(response, payload);
  }

  return { text, headers: response.headers };
}

async function sendApiRequest(
  path: string,
  options: ApiRequestOptions,
  accept: string,
): Promise<Response> {
  const token = getAccessToken();
  const headers = new Headers({ Accept: accept });

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

  try {
    return await fetch(buildUrl(path), requestInit);
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
}

function handleAuthFailure(path: string, status: number): void {
  if (status === 401 && shouldHandleUnauthorized(path)) {
    unauthorizedHandler?.();
  }
}

function throwApiError(response: Response, payload: unknown): never {
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
