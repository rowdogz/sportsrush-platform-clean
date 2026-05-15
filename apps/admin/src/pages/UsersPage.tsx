import { useCallback, useEffect, useState, type FormEvent } from "react";
import { listUsers } from "../features/users/api";
import type {
  AdminUser,
  UserListFilters,
  UserRole,
} from "../features/users/types";
import { userRoles } from "../features/users/types";
import { ApiError } from "../lib/apiClient";

type UsersState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly users: readonly AdminUser[];
    }
  | { readonly status: "error"; readonly message: string };

type FilterValues = {
  readonly search: string;
  readonly role: string;
  readonly status: string;
};

const emptyFilterValues: FilterValues = {
  search: "",
  role: "",
  status: "",
};

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load users.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function toFilters(values: FilterValues): UserListFilters {
  return {
    search: toOptionalValue(values.search),
    role: toOptionalValue(values.role) as UserRole | null,
    isActive:
      values.status === "active"
        ? true
        : values.status === "inactive"
          ? false
          : null,
  };
}

export function UsersPage() {
  const [state, setState] = useState<UsersState>({ status: "loading" });
  const [filters, setFilters] = useState<FilterValues>(emptyFilterValues);

  const loadUsers = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }

      try {
        const response = await listUsers(toFilters(nextFilters));
        setState({ status: "success", users: response.data });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadUsers(emptyFilterValues, { showLoading: true });
  }, [loadUsers]);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await loadUsers(filters, { showLoading: true });
  }

  return (
    <section aria-labelledby="users-title">
      <div className="page-heading">
        <h2 id="users-title">Users</h2>
        <p>Review registered SportsRush users and admin access roles.</p>
      </div>

      <form className="admin-form" onSubmit={handleFilterSubmit}>
        <div className="form-heading">
          <h3>Filter users</h3>
        </div>
        <div className="form-grid">
          <label>
            Search
            <input
              value={filters.search}
              onChange={(event) =>
                setFilters({ ...filters, search: event.target.value })
              }
            />
          </label>
          <label>
            Role
            <select
              value={filters.role}
              onChange={(event) =>
                setFilters({ ...filters, role: event.target.value })
              }
            >
              <option value="">All roles</option>
              {userRoles.map((role) => (
                <option key={role} value={role}>
                  {role}
                </option>
              ))}
            </select>
          </label>
          <label>
            Status
            <select
              value={filters.status}
              onChange={(event) =>
                setFilters({ ...filters, status: event.target.value })
              }
            >
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </label>
        </div>
        <button className="secondary-button" type="submit">
          Apply filters
        </button>
      </form>

      <div className="state-panel">
        <strong>Read-only user management</strong>
        <span>
          Create, archive, suspend, and role-change actions are deferred until
          matching admin API endpoints exist.
        </span>
      </div>

      {state.status === "loading" ? (
        <div className="state-panel" role="status">
          Loading users…
        </div>
      ) : null}

      {state.status === "error" ? (
        <div className="state-panel error-panel" role="alert">
          <strong>Unable to load users</strong>
          <span>{state.message}</span>
        </div>
      ) : null}

      {state.status === "success" && state.users.length === 0 ? (
        <div className="state-panel">
          <strong>No users found</strong>
          <span>Users will appear here after they register.</span>
        </div>
      ) : null}

      {state.status === "success" && state.users.length > 0 ? (
        <div className="admin-table-wrapper">
          <table className="admin-table">
            <thead>
              <tr>
                <th scope="col">User</th>
                <th scope="col">Verification</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Email</th>
                <th scope="col">Created</th>
                <th scope="col">Updated</th>
                <th scope="col">Legacy</th>
              </tr>
            </thead>
            <tbody>
              {state.users.map((user) => (
                <tr key={user.id}>
                  <td>{user.displayName ?? "—"}</td>
                  <td>{user.email}</td>
                  <td>{user.role}</td>
                  <td>
                    <span
                      className={
                        user.isActive
                          ? "status-pill status-pill-active"
                          : "status-pill status-pill-inactive"
                      }
                    >
                      {user.isActive ? "active" : "inactive"}
                    </span>
                  </td>
                  <td>{user.emailVerifiedAt ? "verified" : "unverified"}</td>
                  <td>{user.createdAt}</td>
                  <td>{user.profileUpdatedAt ?? user.updatedAt}</td>
                  <td>{user.legacyWpUserId ?? "—"}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </section>
  );
}
