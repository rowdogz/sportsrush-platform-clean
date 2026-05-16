type AdminTableLoadingProps = {
  readonly message: string;
};

type AdminTableEmptyProps = {
  readonly title: string;
  readonly message: string;
};

type AdminTableErrorProps = {
  readonly title: string;
  readonly message: string;
};

export function AdminTableLoading({ message }: AdminTableLoadingProps) {
  return (
    <div className="state-panel" role="status">
      {message}
    </div>
  );
}

export function AdminTableEmpty({ title, message }: AdminTableEmptyProps) {
  return (
    <div className="state-panel">
      <strong>{title}</strong>
      <span>{message}</span>
    </div>
  );
}

export function AdminTableError({ title, message }: AdminTableErrorProps) {
  return (
    <div className="state-panel error-panel" role="alert">
      <strong>{title}</strong>
      <span>{message}</span>
    </div>
  );
}
