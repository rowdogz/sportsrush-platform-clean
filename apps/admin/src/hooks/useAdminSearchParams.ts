import { useEffect, useRef, useState } from "react";

type SearchParamStateOptions<T extends Record<string, unknown>> = {
  readonly storageKey: string;
  readonly defaults: T;
  readonly paramKeys: readonly string[];
  readonly parse: (params: URLSearchParams, fallback: T) => T;
  readonly serialize: (value: T, defaults: T) => URLSearchParams;
  readonly mode?: "replace" | "push";
};

function readStoredState<T extends Record<string, unknown>>(
  storageKey: string,
  defaults: T,
): T {
  try {
    const storedValue = window.localStorage.getItem(storageKey);
    if (!storedValue) {
      return defaults;
    }

    const parsedValue: unknown = JSON.parse(storedValue);
    if (
      !parsedValue ||
      typeof parsedValue !== "object" ||
      Array.isArray(parsedValue)
    ) {
      return defaults;
    }

    return { ...defaults, ...(parsedValue as Partial<T>) };
  } catch {
    window.localStorage.removeItem(storageKey);
    return defaults;
  }
}

function getCurrentUrlParams(): URLSearchParams {
  return new URLSearchParams(window.location.search);
}

function updateUrl(params: URLSearchParams, mode: "replace" | "push") {
  const nextSearch = params.toString();
  const nextUrl = `${window.location.pathname}${nextSearch ? `?${nextSearch}` : ""}${window.location.hash}`;
  const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;

  if (nextUrl === currentUrl) {
    return;
  }

  if (mode === "push") {
    window.history.pushState(null, "", nextUrl);
  } else {
    window.history.replaceState(null, "", nextUrl);
  }
}

export function useAdminSearchParams<T extends Record<string, unknown>>({
  storageKey,
  defaults,
  paramKeys,
  parse,
  serialize,
  mode = "replace",
}: SearchParamStateOptions<T>) {
  const optionsRef = useRef({
    storageKey,
    defaults,
    paramKeys,
    parse,
    serialize,
    mode,
  });
  optionsRef.current = {
    storageKey,
    defaults,
    paramKeys,
    parse,
    serialize,
    mode,
  };

  const [value, setValue] = useState<T>(() => {
    const persistedValue = readStoredState(storageKey, defaults);
    return parse(getCurrentUrlParams(), persistedValue);
  });

  useEffect(() => {
    const currentOptions = optionsRef.current;
    const nextParams = getCurrentUrlParams();
    for (const key of currentOptions.paramKeys) {
      nextParams.delete(key);
    }
    const serializedParams = currentOptions.serialize(
      value,
      currentOptions.defaults,
    );
    serializedParams.forEach((paramValue, key) => {
      nextParams.set(key, paramValue);
    });
    updateUrl(nextParams, currentOptions.mode);

    try {
      window.localStorage.setItem(
        currentOptions.storageKey,
        JSON.stringify(value),
      );
    } catch {
      window.localStorage.removeItem(currentOptions.storageKey);
    }
  }, [value]);

  useEffect(() => {
    function handlePopState() {
      const currentOptions = optionsRef.current;
      setValue(
        currentOptions.parse(
          getCurrentUrlParams(),
          readStoredState(currentOptions.storageKey, currentOptions.defaults),
        ),
      );
    }

    window.addEventListener("popstate", handlePopState);
    return () => window.removeEventListener("popstate", handlePopState);
  }, []);

  return [value, setValue] as const;
}

export function readStringParam(
  params: URLSearchParams,
  key: string,
  fallback: string,
): string {
  const value = params.get(key);
  return value?.trim() ? value.trim() : fallback;
}

export function readEnumParam<T extends string>(
  params: URLSearchParams,
  key: string,
  fallback: T,
  allowedValues: readonly T[],
): T {
  const value = params.get(key);
  return allowedValues.includes(value as T) ? (value as T) : fallback;
}

export function readPositiveIntParam(
  params: URLSearchParams,
  key: string,
  fallback: number,
): number {
  const value = params.get(key);
  if (!value) {
    return fallback;
  }

  const parsedValue = Number(value);
  return Number.isInteger(parsedValue) && parsedValue > 0
    ? parsedValue
    : fallback;
}

export function readPageSizeParam(
  params: URLSearchParams,
  key: string,
  fallback: number,
  allowedValues: readonly number[],
): number {
  const value = readPositiveIntParam(params, key, fallback);
  return allowedValues.includes(value) ? value : fallback;
}

export function readDateParam(
  params: URLSearchParams,
  key: string,
  fallback: string,
): string {
  const value = params.get(key)?.trim();
  if (!value) {
    return fallback;
  }

  return Number.isNaN(Date.parse(value)) ? fallback : value;
}

export function appendStringParam(
  params: URLSearchParams,
  key: string,
  value: string,
  defaultValue = "",
) {
  const trimmedValue = value.trim();
  if (trimmedValue && trimmedValue !== defaultValue) {
    params.set(key, trimmedValue);
  }
}

export function appendNumberParam(
  params: URLSearchParams,
  key: string,
  value: number,
  defaultValue: number,
) {
  if (value !== defaultValue) {
    params.set(key, String(value));
  }
}
