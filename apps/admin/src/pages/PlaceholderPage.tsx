type PlaceholderPageProps = {
  readonly title: string;
};

export function PlaceholderPage({ title }: PlaceholderPageProps) {
  return (
    <section aria-labelledby="placeholder-title">
      <div className="page-heading">
        <h2 id="placeholder-title">{title}</h2>
        <p>{title} admin tools will be added in a later PR.</p>
      </div>

      <div className="state-panel">
        <strong>{title} screen placeholder</strong>
        <span>This admin module is not functional yet.</span>
      </div>
    </section>
  );
}
