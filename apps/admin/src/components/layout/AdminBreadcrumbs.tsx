export function AdminBreadcrumbs({
  currentLabel,
}: {
  readonly currentLabel: string;
}) {
  return (
    <nav aria-label="Breadcrumb" className="admin-breadcrumbs">
      <span>SportsRush</span>
      <span aria-hidden="true">/</span>
      <span>Admin</span>
      <span aria-hidden="true">/</span>
      <strong>{currentLabel}</strong>
    </nav>
  );
}
