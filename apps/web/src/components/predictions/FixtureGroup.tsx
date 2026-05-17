import type { ReactNode } from "react";

export function FixtureGroup({
  title,
  subtitle,
  children,
}: {
  readonly title: string;
  readonly subtitle?: string;
  readonly children: ReactNode;
}) {
  return (
    <section className="prediction-group">
      <div className="prediction-group-heading">
        <div>
          <h2>{title}</h2>
          {subtitle ? <p>{subtitle}</p> : null}
        </div>
      </div>
      <div className="prediction-group-list">{children}</div>
    </section>
  );
}
