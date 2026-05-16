import { describe, expect, it } from "vitest";
import {
  appendNumberParam,
  appendStringParam,
  readDateParam,
  readEnumParam,
  readPageSizeParam,
  readPositiveIntParam,
  readStringParam,
} from "./useAdminSearchParams";

describe("admin search param utilities", () => {
  it("parses strings and ignores empty values", () => {
    const params = new URLSearchParams("search=warriors&teamId=");

    expect(readStringParam(params, "search", "")).toBe("warriors");
    expect(readStringParam(params, "teamId", "team-1")).toBe("team-1");
  });

  it("ignores invalid enum values", () => {
    const params = new URLSearchParams("status=unknown");

    expect(readEnumParam(params, "status", "", ["", "active"])).toBe("");
  });

  it("clamps invalid page and page size values", () => {
    const params = new URLSearchParams("page=-1&pageSize=999");

    expect(readPositiveIntParam(params, "page", 1)).toBe(1);
    expect(readPageSizeParam(params, "pageSize", 50, [25, 50, 100])).toBe(50);
  });

  it("ignores invalid date values", () => {
    const params = new URLSearchParams("dateFrom=not-a-date");

    expect(readDateParam(params, "dateFrom", "")).toBe("");
  });

  it("serialises non-default values and removes defaults", () => {
    const params = new URLSearchParams();

    appendStringParam(params, "search", "warriors");
    appendStringParam(params, "status", "", "");
    appendNumberParam(params, "page", 2, 1);
    appendNumberParam(params, "pageSize", 50, 50);

    expect(params.toString()).toBe("search=warriors&page=2");
  });
});
