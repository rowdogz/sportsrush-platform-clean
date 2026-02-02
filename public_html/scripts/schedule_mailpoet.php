<?php
// Load WordPress environment
require_once('/home/u108848352/domains/sportsrush.co.uk/public_html/wp-load.php');

global $wpdb;

// 1️⃣ Define the correct table name (your database uses 'wpkl_' prefix)
$table_name = "wpkl_mailpoet_newsletters"; 

// 2️⃣ Define the hash of the template to duplicate
$template_hash = "f761cd0d7113"; 

// 3️⃣ Fetch the email using the correct query format
$latest_email = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM $table_name 
    WHERE hash = %s LIMIT 1", 
    $template_hash
), ARRAY_A);

if (!$latest_email) {
    die("❌ No email found with hash: $template_hash in table: $table_name");
} else {
    echo "✅ Email found! Subject: " . $latest_email['subject'];
}

// 2️⃣ Determine the next Wednesday at 10:00 AM
$now = new DateTime('now', new DateTimeZone('Europe/London'));
$next_wednesday = new DateTime('next wednesday 10:00', new DateTimeZone('Europe/London'));

// Stop scheduling after October 2025
$end_date = new DateTime('2025-10-01', new DateTimeZone('Europe/London'));
if ($next_wednesday > $end_date) {
    die("✅ Automation ended. No further emails scheduled.");
}

// 3️⃣ Duplicate the email and schedule it
$result = $wpdb->insert(
    "wpkl_mailpoet_newsletters", // ✅ Explicitly define the table
    [
        'subject' => $latest_email['subject'],
        'content' => $latest_email['content'],
        'status' => 'scheduled',
        'scheduled_at' => $next_wednesday->format('Y-m-d H:i:s'),
        'is_scheduled' => 1,
        'created_at' => current_time('mysql', 1),
        'updated_at' => current_time('mysql', 1)
    ]
);

if ($result) {
    echo "✅ Successfully scheduled email for " . $next_wednesday->format('d M Y, H:i');
} else {
    echo "❌ Failed to schedule email. Error: " . $wpdb->last_error;
}
?>