import { Text, View } from "react-native";

export function CommercialSlot({ label }: { readonly label: string }) {
  return (
    <View
      accessibilityLabel={`Sponsorship slot: ${label}`}
      style={{
        backgroundColor: "#eef4ff",
        borderRadius: 16,
        marginBottom: 12,
        padding: 14,
      }}
    >
      <Text style={{ color: "#0949ae", fontWeight: "900" }}>Sponsored</Text>
      <Text style={{ color: "#344054" }}>{label}</Text>
    </View>
  );
}
