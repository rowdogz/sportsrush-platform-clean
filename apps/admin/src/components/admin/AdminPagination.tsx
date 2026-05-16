export type AdminPaginationMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export const adminPageSizeOptions = [25, 50, 100] as const;

export function normalizeAdminPageSize(value: number): number {
  return adminPageSizeOptions.includes(
    value as (typeof adminPageSizeOptions)[number],
  )
    ? value
    : 50;
}

export function AdminPagination({
  label,
  meta,
  pageSize,
  onPageChange,
  onPageSizeChange,
}: {
  readonly label: string;
  readonly meta: AdminPaginationMeta;
  readonly pageSize: number;
  readonly onPageChange: (page: number) => void;
  readonly onPageSizeChange: (pageSize: number) => void;
}) {
  const firstItem = meta.total === 0 ? 0 : (meta.page - 1) * meta.limit + 1;
  const lastItem = Math.min(meta.page * meta.limit, meta.total);

  return (
    <div className="pagination-bar" aria-label={label}>
      <div>
        <strong>Page {meta.page}</strong>
        <span>
          Showing {firstItem}–{lastItem} of {meta.total}
        </span>
      </div>
      <label>
        Page size
        <select
          value={pageSize}
          onChange={(event) => onPageSizeChange(Number(event.target.value))}
        >
          {adminPageSizeOptions.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      </label>
      <div className="row-actions">
        <button
          className="secondary-button"
          type="button"
          onClick={() => onPageChange(meta.page - 1)}
          disabled={meta.page <= 1}
        >
          Previous
        </button>
        <button
          className="secondary-button"
          type="button"
          onClick={() => onPageChange(meta.page + 1)}
          disabled={!meta.hasMore}
        >
          Next
        </button>
      </div>
    </div>
  );
}
