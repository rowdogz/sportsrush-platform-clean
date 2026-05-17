import type { ReactNode } from "react";

export function FixtureResultGroup({
  title,
  subtitle,
  children,
}: {
  readonly title: string;
  readonly subtitle?: string;
  readonly children: ReactNode;
}) {
  return (
    <section className="result-group">
      <div className="result-group-heading">
        <div>
          <h2>{title}</h2>
          {subtitle ? <p>{subtitle}</p> : null}
        </div>
      </div>
      <div className="result-group-list">{children}</div>
    </section>
  );
}
