import { API_VERSION } from "./version";

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
 * The paths object uses the standard OpenAPI 3.0 path item structure.
 */
export function buildOpenApiSpec(baseUrl: string) {
  return {
    openapi: "3.0.3",
    info: {
      title: "SportsRush API",
      version: API_VERSION,
      description:
        "The SportsRush 2.0 platform API. Built on Cloudflare Workers + Hono.",
      contact: {
        name: "SportsRush Engineering",
      },
      license: {
        name: "Proprietary",
      },
    },
    servers: [
      {
        url: baseUrl,
        description: "Current environment",
      },
    ],
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
            "200": {
              description: "Service is healthy",
              content: {
                "application/json": {
                  schema: { $ref: "#/components/schemas/HealthResponse" },
                },
              },
            },
          },
        },
      },
      "/version": {
        get: {
          operationId: "getVersion",
          summary: "Version info",
          description: "Returns the deployed API version and environment.",
          tags: ["System"],
          responses: {
            "200": {
              description: "Version information",
              content: { "application/json": { schema: {} } },
            },
          },
        },
      },
      "/ready": {
        get: {
          operationId: "getReady",
          summary: "Readiness probe",
          description:
            "Returns 200 when all downstream dependencies (D1, KV) are reachable. " +
            "Returns 503 if any dependency is unavailable. " +
            "D1 check is added in PR-05.",
          tags: ["System"],
          responses: {
            "200": {
              description: "All dependencies reachable",
              content: { "application/json": { schema: {} } },
            },
            "503": {
              description: "One or more dependencies unavailable",
              content: {
                "application/json": {
                  schema: { $ref: "#/components/schemas/ErrorResponse" },
                },
              },
            },
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
    },
  } as const;
}

export type OpenApiSpec = ReturnType<typeof buildOpenApiSpec>;
