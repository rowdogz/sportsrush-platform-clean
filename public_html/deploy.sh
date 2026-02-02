#!/bin/bash
echo "Deploying WordPress site to Hostinger..."

# Define SSH and Remote Path
USER="u108848352"
HOST="194.36.184.145"
PORT="65002"
REMOTE_DIR="/home/u108848352/domains/sportsrush.co.uk/public_html"

# Rsync files to the live server (only from the sportsrush directory)
rsync -avz -e "ssh -p $PORT" \
    --exclude ".git" \
    --exclude "wp-config.php" \
    --exclude "node_modules" \
    --exclude ".DS_Store" \
    --exclude "uploads" \
    ~/sites/sportsrush/ $USER@$HOST:$REMOTE_DIR

echo "Deployment complete!"
