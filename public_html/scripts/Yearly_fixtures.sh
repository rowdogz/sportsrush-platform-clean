#!/bin/bash

# Start and end days
START_DAYS=3
END_DAYS=352

# Iterate through the range of days
for ((i = START_DAYS; i <= END_DAYS; i++)); do
    /home/u108848352/domains/sportsrush.co.uk/public_html/scripts/real-score-updater.py $(date -I -d "+$i days")
done