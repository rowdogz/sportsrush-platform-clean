import type { ReactNode } from "react";

type ToastProviderProps = {
  readonly children: ReactNode;
};

export function ToastProvider({ children }: ToastProviderProps) {
  return <>{children}</>;
}
