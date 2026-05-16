type DiffStatus = "added" | "removed" | "changed" | "unchanged";

type DiffValueProps = {
  readonly label: string;
  readonly before: unknown;
  readonly after: unknown;
  readonly beforeExists: boolean;
  readonly afterExists: boolean;
  readonly depth?: number;
};

export function AuditDiff({
  before,
  after,
}: {
  readonly before: unknown;
  readonly after: unknown;
}) {
  return (
    <div className="audit-diff" aria-label="Audit metadata diff">
      <DiffValue
        label="Metadata"
        before={before}
        after={after}
        beforeExists={true}
        afterExists={true}
      />
    </div>
  );
}

function DiffValue({
  label,
  before,
  after,
  beforeExists,
  afterExists,
  depth = 0,
}: DiffValueProps) {
  const status = getStatus(before, after, beforeExists, afterExists);

  if (isPlainObject(before) || isPlainObject(after)) {
    const beforeObject = isPlainObject(before) ? before : {};
    const afterObject = isPlainObject(after) ? after : {};
    const keys = Array.from(
      new Set([...Object.keys(beforeObject), ...Object.keys(afterObject)]),
    ).sort();

    return (
      <details
        className={`audit-diff-node audit-diff-${status}`}
        open={status !== "unchanged" || depth < 1}
      >
        <summary>
          <span>{label}</span>
          <StatusBadge status={status} />
        </summary>
        <div className="audit-diff-children">
          {keys.length > 0 ? (
            keys.map((key) => (
              <DiffValue
                key={key}
                label={key}
                before={beforeObject[key]}
                after={afterObject[key]}
                beforeExists={Object.prototype.hasOwnProperty.call(
                  beforeObject,
                  key,
                )}
                afterExists={Object.prototype.hasOwnProperty.call(
                  afterObject,
                  key,
                )}
                depth={depth + 1}
              />
            ))
          ) : (
            <div className="audit-diff-row audit-diff-unchanged">
              <span className="audit-diff-label">Empty object</span>
              <span className="audit-diff-value">{"{}"}</span>
            </div>
          )}
        </div>
      </details>
    );
  }

  if (Array.isArray(before) || Array.isArray(after)) {
    return (
      <div className={`audit-diff-row audit-diff-${status}`}>
        <div className="audit-diff-row-heading">
          <span className="audit-diff-label">{label}</span>
          <StatusBadge status={status} />
        </div>
        <div className="audit-diff-comparison">
          {beforeExists ? (
            <ValueBlock label="Before" value={before} />
          ) : (
            <ValueBlock label="Before" value={undefined} />
          )}
          {afterExists ? (
            <ValueBlock label="After" value={after} />
          ) : (
            <ValueBlock label="After" value={undefined} />
          )}
        </div>
      </div>
    );
  }

  return (
    <div className={`audit-diff-row audit-diff-${status}`}>
      <div className="audit-diff-row-heading">
        <span className="audit-diff-label">{label}</span>
        <StatusBadge status={status} />
      </div>
      {status === "changed" ? (
        <div className="audit-diff-inline-change">
          <ValuePill value={before} />
          <span aria-label="changed to">→</span>
          <ValuePill value={after} />
        </div>
      ) : (
        <span className="audit-diff-value">
          {formatValue(afterExists ? after : before)}
        </span>
      )}
    </div>
  );
}

function StatusBadge({ status }: { readonly status: DiffStatus }) {
  return (
    <span className={`audit-diff-badge audit-diff-badge-${status}`}>
      {status}
    </span>
  );
}

function ValuePill({ value }: { readonly value: unknown }) {
  return <code className="audit-diff-pill">{formatValue(value)}</code>;
}

function ValueBlock({
  label,
  value,
}: {
  readonly label: string;
  readonly value: unknown;
}) {
  return (
    <div>
      <strong>{label}</strong>
      <pre>{formatValue(value)}</pre>
    </div>
  );
}

function getStatus(
  before: unknown,
  after: unknown,
  beforeExists: boolean,
  afterExists: boolean,
): DiffStatus {
  if (!beforeExists && afterExists) return "added";
  if (beforeExists && !afterExists) return "removed";
  return valuesEqual(before, after) ? "unchanged" : "changed";
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value);
}

function valuesEqual(before: unknown, after: unknown): boolean {
  return JSON.stringify(before) === JSON.stringify(after);
}

function formatValue(value: unknown): string {
  if (value === undefined) return "undefined";
  if (value === null) return "null";
  if (value === "") return '""';
  if (typeof value === "string") return value;
  if (typeof value === "number" || typeof value === "boolean") {
    return String(value);
  }
  return JSON.stringify(value, null, 2) ?? String(value);
}
