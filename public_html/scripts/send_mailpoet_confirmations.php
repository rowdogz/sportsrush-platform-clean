<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // Ensure this is the correct path

// Database credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u108848352_KDqxs');
define('DB_USER', 'u108848352_Ewka1');
define('DB_PASS', 'WhuiMoFs0X');

// Email sending rate limit
$emails_per_minute = 10;
$sleep_seconds = 60 / $emails_per_minute;

// Connect to MySQL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully.\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Get unconfirmed users
$sql = "SELECT id, first_name, email, link_token FROM wpkl_mailpoet_subscribers WHERE status = 'unconfirmed'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$users) {
    echo "✅ No unconfirmed users found.\n";
    exit;
}

echo "📬 Found " . count($users) . " unconfirmed users. Sending emails...\n";

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.hostinger.com'; // Your SMTP server
$mail->SMTPAuth = true;
$mail->Username = 'admin@sportsrush.co.uk'; // Your SMTP username
$mail->Password = 'gyqnuxqonsehMibge1!'; // Your SMTP password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
$mail->Port = 587; // SMTP port

$mail->setFrom('admin@sportsrush.co.uk', 'SportsRush');
$mail->isHTML(true);

$sent_count = 0;
foreach ($users as $user) {
    $email = $user['email'];
    $first_name = $user['first_name'] ?: 'Subscriber';
    $token = $user['link_token'];

    // Ensure token exists
    if (!$token) {
        echo "⚠️ Skipping $email (missing token)\n";
        continue;
    }

    // ✅ **Generate the correct MailPoet confirmation link using Base64 encoding**
    $confirmation_data = json_encode(["token" => $token, "email" => $email]);
    $encoded_data = base64_encode($confirmation_data);
    $confirmation_link = "https://sportsrush.co.uk/?mailpoet_page=subscriptions&mailpoet_router&endpoint=subscription&action=confirm&data=$encoded_data";

    // Email content
    $subject = "Confirm Your Subscription to Sportsrush";
    $body = "
        <h2>Sportsrush</h2>
        <p>Hello {$first_name},</p>
        <p>You've received this message because you subscribed to Sportsrush. Please confirm your subscription to receive emails from us:</p>
        <p><a href='{$confirmation_link}' style='display:inline-block;padding:10px 20px;background-color:#007BFF;color:white;text-decoration:none;border-radius:5px;'>Click here to confirm your subscription</a></p>
        <p>Thank you,<br>Sportsrush</p>
    ";

    try {
        $mail->clearAddresses();
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body = $body;

        if ($mail->send()) {
            echo "✅ Confirmation email sent to: $email\n";
        } else {
            echo "❌ Failed to send email to: $email - " . $mail->ErrorInfo . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Error sending email to $email: " . $mail->ErrorInfo . "\n";
    }

    $sent_count++;

    // Apply rate limiting
    if ($sent_count % $emails_per_minute === 0) {
        echo "⏳ Pausing for $sleep_seconds seconds to avoid rate limits...\n";
        sleep($sleep_seconds);
    }
}

echo "🎉 Email sending process complete.\n";
?>