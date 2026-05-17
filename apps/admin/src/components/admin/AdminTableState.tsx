type AdminTableLoadingProps = {
  readonly message: string;
};

type AdminTableEmptyProps = {
  readonly title: string;
  readonly message: string;
  readonly actionLabel?: string;
  readonly onAction?: () => void;
};

type AdminTableErrorProps = {
  readonly title: string;
  readonly message: string;
  readonly retryLabel?: string;
  readonly onRetry?: () => void;
};

export function AdminTableLoading({ message }: AdminTableLoadingProps) {
  return (
    <div className="state-panel" role="status" aria-live="polite">
      <span className="loading-dot" aria-hidden="true" />
      <span>{message}</span>
    </div>
  );
}

export function AdminTableEmpty({
  title,
  message,
  actionLabel,
  onAction,
}: AdminTableEmptyProps) {
  return (
    <div className="state-panel">
      <strong>{title}</strong>
      <span>{message}</span>
      {actionLabel && onAction ? (
        <button
          className="secondary-button compact-button"
          type="button"
          onClick={onAction}
        >
          {actionLabel}
        </button>
      ) : null}
    </div>
  );
}

export function AdminTableError({
  title,
  message,
  retryLabel = "Retry",
  onRetry,
}: AdminTableErrorProps) {
  return (
    <div className="state-panel error-panel" role="alert">
      <strong>{title}</strong>
      <span>{message}</span>
      {onRetry ? (
        <button
          className="secondary-button compact-button"
          type="button"
          onClick={onRetry}
        >
          {retryLabel}
        </button>
      ) : null}
    </div>
  );
}
