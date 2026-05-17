export type SaveState =
  | { readonly status: "idle" }
  | { readonly status: "saving"; readonly message: string }
  | { readonly status: "saved"; readonly message: string }
  | { readonly status: "error"; readonly message: string };

export function SaveStateIndicator({ state }: { readonly state: SaveState }) {
  if (state.status === "idle") return null;
  if (state.status === "saving") {
    return (
      <span className="prediction-save-state prediction-save-state-saving">
        <span className="spinner" />
        {state.message}
      </span>
    );
  }
  if (state.status === "saved") {
    return (
      <span className="prediction-save-state prediction-save-state-saved">
        {state.message}
      </span>
    );
  }
  return (
    <span
      className="prediction-save-state prediction-save-state-error"
      role="alert"
    >
      {state.message}
    </span>
  );
}
