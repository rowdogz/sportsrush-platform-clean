#!/opt/alt/python311/bin/python3

import os
from bs4 import BeautifulSoup

HTML_FILE = "flashscore_debug.html"

def extract_fixtures_by_round(html_content):
    soup = BeautifulSoup(html_content, "html.parser")
    fixtures = []

    current_round = None
    for element in soup.find_all(['div']):
        if "event__round" in element.get("class", []):
            current_round = element.text.strip()
        elif "event__match" in element.get("class", []):
            home_team = element.find("div", class_="event__participant--home")
            away_team = element.find("div", class_="event__participant--away")
            time = element.find("div", class_="event__time")
            if home_team and away_team and time and current_round:
                fixtures.append({
                    "round": current_round,
                    "home": home_team.text.strip(),
                    "away": away_team.text.strip(),
                    "time": time.text.strip()
                })
    return fixtures

def main():
    print("📋 Flashscore Super League Fixtures by Round")
    with open(HTML_FILE, "r", encoding="utf-8") as file:
        html_content = file.read()

    fixtures = extract_fixtures_by_round(html_content)

    if fixtures:
        for f in fixtures:
            print(f"{f['round']}: {f['home']} vs {f['away']} at {f['time']}")
    else:
        print("⚠️ No fixtures found.")

if __name__ == "__main__":
    main()