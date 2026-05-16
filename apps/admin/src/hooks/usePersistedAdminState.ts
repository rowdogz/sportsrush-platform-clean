import { useEffect, useState } from "react";

type ValidatePersistedState<T> = (value: unknown) => T | null;

function readPersistedState<T>(
  storageKey: string,
  defaultValue: T,
  validate?: ValidatePersistedState<T>,
): T {
  try {
    const storedValue = window.localStorage.getItem(storageKey);
    if (!storedValue) {
      return defaultValue;
    }

    const parsedValue: unknown = JSON.parse(storedValue);
    return validate?.(parsedValue) ?? (parsedValue as T);
  } catch {
    window.localStorage.removeItem(storageKey);
    return defaultValue;
  }
}

export function usePersistedAdminState<T>(
  storageKey: string,
  defaultValue: T,
  validate?: ValidatePersistedState<T>,
) {
  const [value, setValue] = useState<T>(() =>
    readPersistedState(storageKey, defaultValue, validate),
  );

  useEffect(() => {
    try {
      window.localStorage.setItem(storageKey, JSON.stringify(value));
    } catch {
      window.localStorage.removeItem(storageKey);
    }
  }, [storageKey, value]);

  return [value, setValue] as const;
}

export function mergeStoredObject<T extends Record<string, unknown>>(
  defaultValue: T,
): ValidatePersistedState<T> {
  return (value: unknown) => {
    if (!value || typeof value !== "object" || Array.isArray(value)) {
      return null;
    }

    return { ...defaultValue, ...(value as Partial<T>) };
  };
}

export function persistedPositiveNumber(defaultValue: number) {
  return (value: unknown) =>
    typeof value === "number" && Number.isInteger(value) && value > 0
      ? value
      : defaultValue;
}
