export type ApiEnvelope<T> = {
  readonly data: T;
  readonly meta?: unknown;
};

export type ApiClient = {
  readonly get: <T>(path: string) => Promise<ApiEnvelope<T>>;
  readonly post: <T>(path: string, body?: unknown) => Promise<ApiEnvelope<T>>;
  readonly patch: <T>(path: string, body?: unknown) => Promise<ApiEnvelope<T>>;
};

export type TokenProvider = {
  readonly getToken: () => string | null;
};

const DEFAULT_API_BASE_URL = "http://localhost:8787";

export function getApiBaseUrl(): string {
  return import.meta.env.VITE_ADMIN_API_BASE_URL ?? DEFAULT_API_BASE_URL;
}

export function createApiClient(
  tokenProvider: TokenProvider,
  baseUrl = getApiBaseUrl(),
): ApiClient {
  async function request<T>(
    method: "GET" | "POST" | "PATCH",
    path: string,
    body?: unknown,
  ): Promise<ApiEnvelope<T>> {
    const token = tokenProvider.getToken();
    const headers = new Headers({ accept: "application/json" });
    if (token !== null) headers.set("authorization", `Bearer ${token}`);
    if (body !== undefined) headers.set("content-type", "application/json");

    const response = await fetch(`${baseUrl}${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    const payload = (await response.json()) as ApiEnvelope<T> & {
      readonly error?: { readonly message?: string };
    };

    if (!response.ok) {
      throw new Error(payload.error?.message ?? `Request failed with ${response.status}`);
    }

    return payload;
  }

  return {
    get: (path) => request("GET", path),
    post: (path, body) => request("POST", path, body),
    patch: (path, body) => request("PATCH", path, body),
  };
}
