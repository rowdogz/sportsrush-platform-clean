import { API_VERSION } from "./version";

const paginatedPublicResponse = (itemRef: string) => ({
  type: "object",
  required: ["data", "meta"],
  properties: {
    data: { type: "array", items: { $ref: itemRef } },
    meta: {
      type: "object",
      required: ["page", "limit", "total", "hasMore"],
      properties: {
        page: { type: "integer", minimum: 1 },
        limit: { type: "integer", minimum: 1, maximum: 100 },
        total: { type: "integer", minimum: 0 },
        hasMore: { type: "boolean" },
      },
    },
  },
});

const publicListParameters = [
  { name: "page", in: "query", schema: { type: "integer", minimum: 1 } },
  {
    name: "limit",
    in: "query",
    schema: { type: "integer", minimum: 1, maximum: 100 },
  },
];

const publicJsonResponse = (schema: unknown) => ({
  description: "OK",
  content: { "application/json": { schema } },
});

/**
 * OpenAPI 3.0 specification skeleton for the SportsRush API.
 *
 * This is a static TypeScript object — intentionally not generated from route
 * decorators at this stage to keep the API shell dependency-free.
 *
 * Served at: GET /openapi.json
 * UI (future): GET /docs  →  Swagger UI or Scalar
 *
 * As endpoints are implemented in later PRs, add their path objects here.
 */
export function buildOpenApiSpec(baseUrl: string) {
  return {
    openapi: "3.0.3",
    info: {
      title: "SportsRush API",
      version: API_VERSION,
      description:
        "The SportsRush 2.0 platform API. Built on Cloudflare Workers + Hono.",
      contact: { name: "SportsRush Engineering" },
      license: { name: "Proprietary" },
    },
    servers: [{ url: baseUrl, description: "Current environment" }],
    components: {
      securitySchemes: {
        bearerAuth: {
          type: "http",
          scheme: "bearer",
          bearerFormat: "JWT",
          description:
            "HS256 JWT access token. Obtain from POST /v1/auth/login (implemented in PR-06).",
        },
      },
      schemas: {
        ErrorResponse: {
          type: "object",
          required: ["error"],
          properties: {
            error: {
              type: "object",
              required: ["code", "message"],
              properties: {
                code: { type: "string", example: "NOT_FOUND" },
                message: { type: "string", example: "Resource not found" },
                correlationId: { type: "string", format: "uuid" },
                details: {},
              },
            },
          },
        },
        HealthResponse: {
          type: "object",
          required: ["data"],
          properties: {
            data: {
              type: "object",
              required: ["status", "service", "version", "environment"],
              properties: {
                status: { type: "string", enum: ["ok"] },
                service: { type: "string", example: "sportsrush-api" },
                version: { type: "string", example: API_VERSION },
                environment: {
                  type: "string",
                  enum: ["development", "staging", "production"],
                },
              },
            },
          },
        },
        PublicCompetition: {
          type: "object",
          required: [
            "id",
            "sportId",
            "slug",
            "name",
            "shortName",
            "countryCode",
          ],
          properties: {
            id: { type: "string" },
            sportId: { type: "string" },
            slug: { type: "string" },
            name: { type: "string" },
            shortName: { type: "string", nullable: true },
            countryCode: { type: "string", nullable: true },
          },
        },
        PublicSeason: {
          type: "object",
          required: [
            "id",
            "competitionId",
            "slug",
            "name",
            "startsOn",
            "endsOn",
            "competition",
          ],
          properties: {
            id: { type: "string" },
            competitionId: { type: "string" },
            slug: { type: "string" },
            name: { type: "string" },
            startsOn: { type: "string", nullable: true },
            endsOn: { type: "string", nullable: true },
            competition: { type: "object" },
          },
        },
        PublicRound: {
          type: "object",
          required: [
            "id",
            "seasonId",
            "round",
            "name",
            "displayOrder",
            "startsAt",
            "endsAt",
            "season",
            "competition",
          ],
          properties: {
            id: { type: "string" },
            seasonId: { type: "string" },
            round: { type: "string" },
            name: { type: "string" },
            displayOrder: { type: "integer" },
            startsAt: { type: "string", nullable: true },
            endsAt: { type: "string", nullable: true },
            season: { type: "object" },
            competition: { type: "object" },
          },
        },
        PublicFixture: {
          type: "object",
          required: [
            "id",
            "kickoffTime",
            "venue",
            "status",
            "homeScore",
            "awayScore",
            "homeTeam",
            "awayTeam",
            "round",
            "season",
            "competition",
          ],
          properties: {
            id: { type: "string" },
            kickoffTime: { type: "string", format: "date-time" },
            venue: { type: "string", nullable: true },
            status: {
              type: "string",
              enum: [
                "scheduled",
                "live",
                "completed",
                "postponed",
                "cancelled",
                "abandoned",
              ],
            },
            homeScore: { type: "integer", nullable: true },
            awayScore: { type: "integer", nullable: true },
            homeTeam: { type: "object" },
            awayTeam: { type: "object" },
            round: { type: "object" },
            season: { type: "object" },
            competition: { type: "object" },
          },
        },
      },
    },
    paths: {
      "/health": {
        get: {
          operationId: "getHealth",
          summary: "Health check",
          description:
            "Always returns 200. Use /ready for a readiness probe that checks downstream dependencies.",
          tags: ["System"],
          responses: {
            "200": publicJsonResponse({
              $ref: "#/components/schemas/HealthResponse",
            }),
          },
        },
      },
      "/version": {
        get: {
          operationId: "getVersion",
          summary: "Version info",
          description: "Returns the deployed API version and environment.",
          tags: ["System"],
          responses: { "200": publicJsonResponse({}) },
        },
      },
      "/ready": {
        get: {
          operationId: "getReady",
          summary: "Readiness probe",
          description:
            "Returns 200 when all downstream dependencies (D1, KV) are reachable. Returns 503 if any dependency is unavailable. D1 check is added in PR-05.",
          tags: ["System"],
          responses: {
            "200": publicJsonResponse({}),
            "503": publicJsonResponse({
              $ref: "#/components/schemas/ErrorResponse",
            }),
          },
        },
      },
      "/openapi.json": {
        get: {
          operationId: "getOpenApiSpec",
          summary: "OpenAPI specification",
          description: "Returns this OpenAPI 3.0 JSON specification.",
          tags: ["System"],
          responses: {
            "200": { description: "OpenAPI specification document" },
          },
        },
      },
      "/v1/public/competitions": {
        get: {
          operationId: "listPublicCompetitions",
          summary: "List public competitions",
          tags: ["Public"],
          parameters: publicListParameters,
          responses: {
            "200": publicJsonResponse(
              paginatedPublicResponse("#/components/schemas/PublicCompetition"),
            ),
          },
        },
      },
      "/v1/public/seasons": {
        get: {
          operationId: "listPublicSeasons",
          summary: "List public seasons",
          tags: ["Public"],
          parameters: [
            ...publicListParameters,
            { name: "competitionId", in: "query", schema: { type: "string" } },
          ],
          responses: {
            "200": publicJsonResponse(
              paginatedPublicResponse("#/components/schemas/PublicSeason"),
            ),
          },
        },
      },
      "/v1/public/rounds": {
        get: {
          operationId: "listPublicRounds",
          summary: "List public rounds",
          tags: ["Public"],
          parameters: [
            ...publicListParameters,
            { name: "competitionId", in: "query", schema: { type: "string" } },
            { name: "seasonId", in: "query", schema: { type: "string" } },
          ],
          responses: {
            "200": publicJsonResponse(
              paginatedPublicResponse("#/components/schemas/PublicRound"),
            ),
          },
        },
      },
      "/v1/public/fixtures": {
        get: {
          operationId: "listPublicFixtures",
          summary: "List public fixtures",
          tags: ["Public"],
          parameters: [
            ...publicListParameters,
            { name: "competitionId", in: "query", schema: { type: "string" } },
            { name: "seasonId", in: "query", schema: { type: "string" } },
            { name: "roundId", in: "query", schema: { type: "string" } },
            {
              name: "status",
              in: "query",
              schema: {
                type: "string",
                enum: [
                  "scheduled",
                  "live",
                  "completed",
                  "postponed",
                  "cancelled",
                  "abandoned",
                ],
              },
            },
            {
              name: "fromDate",
              in: "query",
              schema: { type: "string", format: "date-time" },
            },
            {
              name: "toDate",
              in: "query",
              schema: { type: "string", format: "date-time" },
            },
          ],
          responses: {
            "200": publicJsonResponse(
              paginatedPublicResponse("#/components/schemas/PublicFixture"),
            ),
          },
        },
      },
      "/v1/public/fixtures/{id}": {
        get: {
          operationId: "getPublicFixture",
          summary: "Get public fixture by ID",
          tags: ["Public"],
          parameters: [
            {
              name: "id",
              in: "path",
              required: true,
              schema: { type: "string" },
            },
          ],
          responses: {
            "200": publicJsonResponse({
              type: "object",
              required: ["data"],
              properties: {
                data: { $ref: "#/components/schemas/PublicFixture" },
              },
            }),
            "404": publicJsonResponse({
              $ref: "#/components/schemas/ErrorResponse",
            }),
          },
        },
      },
    },
  } as const;
}

export type OpenApiSpec = ReturnType<typeof buildOpenApiSpec>;
