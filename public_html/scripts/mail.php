<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('ABSPATH')) {
    echo "Error: Not running inside WordPress. Exiting...\n";
    exit;
}

// Check if MailPoet API is available
if (!class_exists(\MailPoet\API\API::class)) {
    echo "Error: MailPoet API not found. Ensure MailPoet is installed and activated.\n";
    exit;
} else {
    echo "✅ MailPoet API loaded successfully.\n";
}

$mailpoet_api = \MailPoet\API\API::MP('v1');
echo "🔍 Fetching unconfirmed subscribers...\n";

// Fetch all unconfirmed subscribers
$unconfirmed_subscribers = $mailpoet_api->getSubscribers([
    'status' => 'unconfirmed',
    'limit' => 1000,
    'offset' => 0,
]);

echo "📋 Total unconfirmed subscribers: " . count($unconfirmed_subscribers) . "\n";

// Loop through each unconfirmed subscriber
foreach ($unconfirmed_subscribers as $subscriber) {
    // Debug: Print raw subscriber data
    echo "🔹 Checking subscriber data: " . print_r($subscriber, true) . "\n";

    // Ensure 'email' field exists and is a string
    if (!isset($subscriber['email']) || !is_string($subscriber['email'])) {
        echo "⚠️ Skipping invalid subscriber (no valid email found): " . print_r($subscriber, true) . "\n";
        continue; // Skip this subscriber
    }

    // 🔧 Ensure the email is properly formatted
    $email = trim((string)$subscriber['email']); // Convert to string & trim

    // 🔧 Ensure the subscriber ID is a string
    $subscriber_id = (string)$subscriber['id'];

    try {
        echo "📨 Resending confirmation to: " . $email . "\n";

        // Send confirmation email
        $mailpoet_api->subscribeToList(
            [$subscriber_id], // ID must be a string
            [], // List IDs (leave empty for default)
            ['send_confirmation_email' => true]
        );

        echo "✅ Success: Email resent to " . $email . "\n";
    } catch (Exception $e) {
        echo "❌ Error sending to " . $email . ": " . $e->getMessage() . "\n";
    }
}

echo "🎉 Script execution completed.\n";
?>