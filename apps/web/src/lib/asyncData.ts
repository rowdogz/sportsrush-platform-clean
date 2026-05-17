import { useEffect, useState } from "react";
import { ApiError } from "./apiClient";

export type AsyncState<T> =
  | { readonly status: "loading" }
  | { readonly status: "error"; readonly message: string }
  | { readonly status: "ready"; readonly data: T };

export function errorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.message;
  if (error instanceof Error) return error.message;
  return "Something went wrong.";
}

export function useAsyncData<T>(
  load: () => Promise<T>,
  deps: readonly unknown[],
): readonly [AsyncState<T>, () => void] {
  const [reloadKey, setReloadKey] = useState(0);
  const [state, setState] = useState<AsyncState<T>>({ status: "loading" });

  useEffect(() => {
    let active = true;
    setState({ status: "loading" });
    load()
      .then((data) => {
        if (active) setState({ status: "ready", data });
      })
      .catch((error: unknown) => {
        if (active) setState({ status: "error", message: errorMessage(error) });
      });
    return () => {
      active = false;
    };
  }, [reloadKey, ...deps]);

  return [state, () => setReloadKey((value) => value + 1)];
}
