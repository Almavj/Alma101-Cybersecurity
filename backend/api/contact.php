<?php
require_once __DIR__ . '/../middleware/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON']);
    exit();
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required fields']);
    exit();
}

// insert into Supabase contact_submissions using service role key
$db = new Database();
$client = $db->getClient();

try {
    $resp = $client->post('/rest/v1/contact_submissions', [
        'json' => ['name' => $name, 'email' => $email, 'message' => $message],
        'headers' => [
            'Prefer' => 'return=representation'
        ]
    ]);

    // ignore response body for now, but capture status
    $status = $resp->getStatusCode();
    if ($status < 200 || $status >= 300) {
        // log but continue to attempt sending email
        error_log('Failed to insert contact submission: ' . (string)$resp->getBody());
    }
} catch (Exception $e) {
    error_log('Contact DB insert error: ' . $e->getMessage());
}

// send email to admin
try {
    $adminEmail = $_ENV['SUPABASE_ADMIN_EMAIL'] ?? 'machariaallan881@gmail.com';
    $mailer = new Services\EmailService();
    $html = "<h2>New Contact Message</h2>";
    $html .= "<p><strong>From:</strong> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")</p>";
    $html .= "<p><strong>Message:</strong></p>";
    $html .= "<div style='white-space:pre-wrap;border:1px solid #eee;padding:12px;border-radius:6px;'>" . nl2br(htmlspecialchars($message)) . "</div>";

    $sent = $mailer->sendContactEmail($adminEmail, $name, $email, $html);

    if ($sent) {
        http_response_code(200);
        echo json_encode(['message' => 'Message sent']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to send email']);
        exit();
    }
} catch (Exception $e) {
    error_log('Contact email error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Internal server error']);
    exit();
}
