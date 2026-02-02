#!/opt/alt/python311/bin/python3

import requests

API_KEY = "m7ukR5qJrPPf9P9ahy6bZ819tINRYAP1Ks8kGtHx"
BASE_URL = "https://api.sportradar.com/rugby-league/trial/v3/en"

# Example: List all tournaments (like Super League)
def list_tournaments():
    url = f"{BASE_URL}/competitions.json?api_key={API_KEY}"
    response = requests.get(url)

    if response.status_code == 200:
        data = response.json()
        print("📋 Available Rugby League Competitions:\n")
        for comp in data.get("competitions", []):
            print(f"🔹 ID: {comp['id']} | Name: {comp['name']} | Category: {comp['category']['name']}")
    else:
        print(f"❌ Failed to fetch competitions: {response.status_code} - {response.text}")

if __name__ == "__main__":
    list_tournaments()