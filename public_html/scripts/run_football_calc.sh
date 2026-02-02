#!/bin/bash

# Path to your WordPress installation
WP_PATH="/home3/editor/public_html"

# Run the WP-CLI football-pool calc command
wp --path=$WP_PATH football-pool calc
