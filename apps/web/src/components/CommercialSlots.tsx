import { defaultFeatureFlags, trackEvent } from "../lib/commercial";

export function AdSlot({ placement }: { readonly placement: string }) {
  if (!defaultFeatureFlags.ads) return null;
  return (
    <aside
      className="commercial-slot"
      aria-label={`Advertisement slot: ${placement}`}
    >
      <span>Ad slot</span>
      <strong>{placement}</strong>
    </aside>
  );
}

export function SponsorshipSlot({ label }: { readonly label: string }) {
  if (!defaultFeatureFlags.sponsorshipSlots) return null;
  return (
    <section className="sponsor-slot" aria-label="Sponsorship">
      <span>Sponsored placement</span>
      <strong>{label}</strong>
    </section>
  );
}

export function PremiumHook() {
  if (!defaultFeatureFlags.premiumLeagues) return null;
  return (
    <button
      className="button premium"
      type="button"
      onClick={() => trackEvent({ name: "premium_hook_clicked" })}
    >
      Private league premium tools coming soon
    </button>
  );
}
