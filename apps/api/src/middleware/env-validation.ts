import type { MiddlewareHandler } from "hono";
import type { HonoEnv } from "../env";
import { validateEnv } from "../env";
import { InternalError } from "../lib/errors";

export function makeEnvValidationMiddleware(): MiddlewareHandler<HonoEnv> {
  return async (c, next) => {
    const result = validateEnv(c.env ?? {});
    if (!result.ok) {
      console.error(
        JSON.stringify({
          level: "error",
          correlationId: c.var.correlationId ?? "unknown",
          message: "Invalid API environment configuration.",
          issues: result.issues,
        }),
      );
      throw new InternalError("Invalid API environment configuration.");
    }

    await next();
  };
}
