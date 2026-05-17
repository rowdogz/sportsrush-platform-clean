import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  SafeAreaView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import {
  listFixtures,
  listLeaderboards,
  savePrediction,
} from "./src/api/client";
import type { LeaderboardEntry, PublicFixture } from "./src/api/types";
import { readSession } from "./src/auth/session";
import { CommercialSlot } from "./src/commercial/CommercialSlot";

type Screen = "fixtures" | "rankings" | "predict";

export default function App() {
  const [screen, setScreen] = useState<Screen>("fixtures");
  const [fixtures, setFixtures] = useState<readonly PublicFixture[]>([]);
  const [leaders, setLeaders] = useState<readonly LeaderboardEntry[]>([]);
  const [selectedFixture, setSelectedFixture] = useState<PublicFixture | null>(
    null,
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  async function load(): Promise<void> {
    setLoading(true);
    setError(null);
    try {
      const [fixtureResult, rankingResult] = await Promise.all([
        listFixtures(),
        listLeaderboards(),
      ]);
      setFixtures(fixtureResult.data);
      setLeaders(rankingResult.data);
    } catch (loadError) {
      setError(
        loadError instanceof Error
          ? loadError.message
          : "Unable to load SportsRush.",
      );
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void load();
  }, []);

  return (
    <SafeAreaView style={styles.shell}>
      <View style={styles.header}>
        <Text style={styles.brand}>SportsRush</Text>
        <Text style={styles.subtitle}>Fixtures, predictions and rankings</Text>
      </View>
      <View style={styles.nav}>
        <NavButton
          active={screen === "fixtures"}
          label="Fixtures"
          onPress={() => setScreen("fixtures")}
        />
        <NavButton
          active={screen === "rankings"}
          label="Rankings"
          onPress={() => setScreen("rankings")}
        />
      </View>
      {loading ? (
        <ActivityIndicator accessibilityLabel="Loading SportsRush" />
      ) : null}
      {error ? (
        <View style={styles.panel}>
          <Text style={styles.error}>{error}</Text>
          <Pressable style={styles.button} onPress={() => void load()}>
            <Text style={styles.buttonText}>Retry</Text>
          </Pressable>
        </View>
      ) : null}
      {!loading && !error && screen === "fixtures" ? (
        <FlatList
          data={fixtures}
          keyExtractor={(item) => item.id}
          ListHeaderComponent={
            <CommercialSlot label="Mobile fixture sponsor" />
          }
          ListEmptyComponent={
            <Text style={styles.muted}>No fixtures yet.</Text>
          }
          renderItem={({ item }) => (
            <FixtureRow
              fixture={item}
              onPredict={() => {
                setSelectedFixture(item);
                setScreen("predict");
              }}
            />
          )}
        />
      ) : null}
      {!loading && !error && screen === "rankings" ? (
        <FlatList
          data={leaders}
          keyExtractor={(item) => item.userId}
          ListEmptyComponent={
            <Text style={styles.muted}>No rankings yet.</Text>
          }
          renderItem={({ item }) => (
            <View style={styles.panel}>
              <Text style={styles.title}>
                #{item.rank} {item.displayName ?? item.email ?? item.userId}
              </Text>
              <Text style={styles.muted}>
                {item.totalPoints} points · {item.exactScores} exact
              </Text>
            </View>
          )}
        />
      ) : null}
      {!loading && !error && screen === "predict" && selectedFixture ? (
        <PredictionPanel
          fixture={selectedFixture}
          onDone={() => setScreen("fixtures")}
        />
      ) : null}
    </SafeAreaView>
  );
}

function NavButton({
  active,
  label,
  onPress,
}: {
  readonly active: boolean;
  readonly label: string;
  readonly onPress: () => void;
}) {
  return (
    <Pressable
      style={[styles.navButton, active ? styles.navButtonActive : null]}
      onPress={onPress}
    >
      <Text style={[styles.navText, active ? styles.navTextActive : null]}>
        {label}
      </Text>
    </Pressable>
  );
}

function FixtureRow({
  fixture,
  onPredict,
}: {
  readonly fixture: PublicFixture;
  readonly onPredict: () => void;
}) {
  return (
    <View style={styles.panel}>
      <Text style={styles.title}>
        {fixture.homeTeam.displayName ?? fixture.homeTeam.name} vs{" "}
        {fixture.awayTeam.displayName ?? fixture.awayTeam.name}
      </Text>
      <Text style={styles.muted}>
        {fixture.competition.shortName ?? fixture.competition.name} ·{" "}
        {fixture.status}
      </Text>
      <Pressable style={styles.button} onPress={onPredict}>
        <Text style={styles.buttonText}>Predict score</Text>
      </Pressable>
    </View>
  );
}

function PredictionPanel({
  fixture,
  onDone,
}: {
  readonly fixture: PublicFixture;
  readonly onDone: () => void;
}) {
  const [homeScore, setHomeScore] = useState("");
  const [awayScore, setAwayScore] = useState("");
  const [message, setMessage] = useState<string | null>(null);

  async function submit(): Promise<void> {
    const session = await readSession();
    if (!session.accessToken) {
      setMessage(
        "Login support is wired for token persistence; sign-in UI follows the same API client.",
      );
      return;
    }
    await savePrediction(
      session.accessToken,
      fixture.id,
      Number(homeScore),
      Number(awayScore),
    );
    setMessage("Prediction saved.");
  }

  return (
    <View style={styles.panel}>
      <Text style={styles.title}>
        Predict {fixture.homeTeam.name} vs {fixture.awayTeam.name}
      </Text>
      <TextInput
        keyboardType="number-pad"
        placeholder="Home score"
        style={styles.input}
        value={homeScore}
        onChangeText={setHomeScore}
      />
      <TextInput
        keyboardType="number-pad"
        placeholder="Away score"
        style={styles.input}
        value={awayScore}
        onChangeText={setAwayScore}
      />
      {message ? <Text style={styles.muted}>{message}</Text> : null}
      <Pressable style={styles.button} onPress={() => void submit()}>
        <Text style={styles.buttonText}>Save prediction</Text>
      </Pressable>
      <Pressable onPress={onDone}>
        <Text style={styles.link}>Back to fixtures</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  shell: { flex: 1, backgroundColor: "#f6f7fb", padding: 16 },
  header: { paddingVertical: 16 },
  brand: { color: "#0949ae", fontSize: 30, fontWeight: "900" },
  subtitle: { color: "#667085", marginTop: 4 },
  nav: { flexDirection: "row", gap: 8, marginBottom: 12 },
  navButton: {
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: "#ffffff",
  },
  navButtonActive: { backgroundColor: "#0f6bff" },
  navText: { color: "#344054", fontWeight: "800" },
  navTextActive: { color: "#ffffff" },
  panel: {
    borderRadius: 18,
    backgroundColor: "#ffffff",
    padding: 16,
    marginBottom: 12,
  },
  title: { color: "#101828", fontSize: 16, fontWeight: "900", marginBottom: 6 },
  muted: { color: "#667085" },
  error: { color: "#b42318", fontWeight: "800", marginBottom: 10 },
  button: {
    alignSelf: "flex-start",
    borderRadius: 12,
    backgroundColor: "#0f6bff",
    paddingHorizontal: 14,
    paddingVertical: 10,
    marginTop: 12,
  },
  buttonText: { color: "#ffffff", fontWeight: "900" },
  input: {
    borderColor: "#d9e0eb",
    borderRadius: 12,
    borderWidth: 1,
    padding: 12,
    marginTop: 10,
  },
  link: { color: "#0949ae", fontWeight: "800", marginTop: 12 },
});
