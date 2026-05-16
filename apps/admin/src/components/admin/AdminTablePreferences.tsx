import { useMemo } from "react";
import { mergeStoredObject } from "../../hooks/usePersistedAdminState";
import { usePersistedAdminState } from "../../hooks/usePersistedAdminState";

export type AdminTableDensity = "comfortable" | "compact";

export type AdminTableColumn = {
  readonly id: string;
  readonly label: string;
  readonly optional?: boolean;
};

export type AdminTablePreferenceState = {
  readonly density: AdminTableDensity;
  readonly hiddenColumns: readonly string[];
};

const defaultPreferences: AdminTablePreferenceState = {
  density: "comfortable",
  hiddenColumns: [],
};

function sanitizePreferences(
  value: AdminTablePreferenceState,
  columns: readonly AdminTableColumn[],
): AdminTablePreferenceState {
  const optionalColumnIds = new Set(
    columns.filter((column) => column.optional).map((column) => column.id),
  );

  return {
    density: value.density === "compact" ? "compact" : "comfortable",
    hiddenColumns: value.hiddenColumns.filter((columnId) =>
      optionalColumnIds.has(columnId),
    ),
  };
}

export function useAdminTablePreferences(
  storageKey: string,
  columns: readonly AdminTableColumn[],
) {
  const [storedPreferences, setStoredPreferences] = usePersistedAdminState(
    storageKey,
    defaultPreferences,
    mergeStoredObject(defaultPreferences),
  );
  const preferences = useMemo(
    () => sanitizePreferences(storedPreferences, columns),
    [columns, storedPreferences],
  );
  const hiddenColumnSet = useMemo(
    () => new Set(preferences.hiddenColumns),
    [preferences.hiddenColumns],
  );

  function setDensity(density: AdminTableDensity) {
    setStoredPreferences(
      sanitizePreferences({ ...preferences, density }, columns),
    );
  }

  function setColumnVisible(columnId: string, isVisible: boolean) {
    const column = columns.find((candidate) => candidate.id === columnId);
    if (!column?.optional) {
      return;
    }

    const nextHiddenColumns = isVisible
      ? preferences.hiddenColumns.filter((hiddenId) => hiddenId !== columnId)
      : [...preferences.hiddenColumns, columnId];

    setStoredPreferences(
      sanitizePreferences(
        { ...preferences, hiddenColumns: nextHiddenColumns },
        columns,
      ),
    );
  }

  function isColumnVisible(columnId: string): boolean {
    return !hiddenColumnSet.has(columnId);
  }

  return {
    density: preferences.density,
    hiddenColumns: preferences.hiddenColumns,
    tableClassName: `admin-table admin-table-${preferences.density}`,
    isColumnVisible,
    setColumnVisible,
    setDensity,
  };
}

export function AdminTablePreferences({
  columns,
  density,
  hiddenColumns,
  onColumnVisibleChange,
  onDensityChange,
}: {
  readonly columns: readonly AdminTableColumn[];
  readonly density: AdminTableDensity;
  readonly hiddenColumns: readonly string[];
  readonly onColumnVisibleChange: (
    columnId: string,
    isVisible: boolean,
  ) => void;
  readonly onDensityChange: (density: AdminTableDensity) => void;
}) {
  const hiddenColumnSet = new Set(hiddenColumns);
  const optionalColumns = columns.filter((column) => column.optional);

  return (
    <details className="table-preferences">
      <summary>Table preferences</summary>
      <div className="table-preferences-panel">
        <fieldset>
          <legend>Density</legend>
          <label>
            <input
              type="radio"
              name="table-density"
              value="comfortable"
              checked={density === "comfortable"}
              onChange={() => onDensityChange("comfortable")}
            />
            Comfortable
          </label>
          <label>
            <input
              type="radio"
              name="table-density"
              value="compact"
              checked={density === "compact"}
              onChange={() => onDensityChange("compact")}
            />
            Compact
          </label>
        </fieldset>

        <fieldset>
          <legend>Optional columns</legend>
          {optionalColumns.map((column) => (
            <label key={column.id}>
              <input
                type="checkbox"
                aria-label={`Show ${column.label} column`}
                checked={!hiddenColumnSet.has(column.id)}
                onChange={(event) =>
                  onColumnVisibleChange(column.id, event.target.checked)
                }
              />
              Show {column.label}
            </label>
          ))}
        </fieldset>
      </div>
    </details>
  );
}
