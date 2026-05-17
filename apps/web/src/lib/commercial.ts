export type AnalyticsEvent = {
  readonly name: string;
  readonly properties?: Record<string, string | number | boolean | null>;
};

export type FeatureFlags = {
  readonly premiumLeagues: boolean;
  readonly sponsorshipSlots: boolean;
  readonly ads: boolean;
};

export const defaultFeatureFlags: FeatureFlags = {
  premiumLeagues: true,
  sponsorshipSlots: true,
  ads: true,
};

export function trackEvent(event: AnalyticsEvent): void {
  if (typeof window === "undefined") return;
  window.dispatchEvent(
    new CustomEvent("sportsrush:analytics", { detail: event }),
  );
}
