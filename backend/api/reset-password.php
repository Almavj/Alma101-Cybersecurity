<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/ErrorHandler.php';
require_once __DIR__ . '/../middleware/Cors.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Initialize database connection
$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        exit;
    }

    // Generate OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store OTP in database
    $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = ?, expires_at = ?");
    $stmt->bind_param("sssss", $email, $otp, $expiry, $otp, $expiry);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate reset code']);
        exit;
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Update with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'machariaallan881@gmail.com'; // Your AlmaTech email
        $mail->Password = ''; // Your email password or app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('machariaallan881@gmail.com', 'AlmaTech Support');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - AlmaTech';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2>Password Reset Code</h2>
                <p>You requested a password reset for your AlmaTech account. Use the following code to reset your password:</p>
                <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                    <strong>{$otp}</strong>
                </div>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <p>Best regards,<br>AlmaTech Support Team</p>
            </div>
        ";

        $mail->send();
        echo json_encode(['message' => 'Reset code sent successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send reset code email']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $otp = $data['otp'] ?? '';
    $newPassword = $data['newPassword'] ?? '';

    if (!$email || !$otp || !$newPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Verify OTP
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired reset code']);
        exit;
    }

    // Update password in Supabase via API
    $supabaseUrl = 'YOUR_SUPABASE_URL';
    $supabaseKey = 'YOUR_SUPABASE_SERVICE_KEY';

    $ch = curl_init("{$supabaseUrl}/auth/v1/user");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $newPassword
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
        exit;
    }

    // Mark OTP as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND otp = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();

    echo json_encode(['message' => 'Password updated successfully']);
}
?>