<?php
namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $fromEmail = 'machariaallan881@gmail.com';
    private $fromName = 'Alma101';

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP() {
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->fromEmail;
        $this->mailer->Password = 'elew jpeg jjpj uymd'; // Add your Gmail app password here
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
        $this->mailer->isHTML(true);
    }

    private function getEmailTemplate($type, $data) {
        // Get the server's domain and protocol
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $logo = $protocol . $domain . '/sentinel-learn-lab/public/Alma101.jpg';
        
        $baseTemplate = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; }
                .logo { max-width: 150px; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 8px; }
                .footer { text-align: center; padding: 20px 0; font-size: 12px; color: #666; }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background: #4F46E5;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin: 20px 0;
                }
                .code {
                    font-size: 24px;
                    font-weight: bold;
                    letter-spacing: 4px;
                    text-align: center;
                    padding: 15px;
                    background: #e9ecef;
                    border-radius: 4px;
                    margin: 15px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="$logo" alt="Alma101" class="logo">
                </div>
                <div class="content">
                    {CONTENT}
                </div>
                <div class="footer">
                    <p>© 2025 Alma101. All rights reserved.</p>
                    <p>If you didn't request this email, please ignore it or contact support.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        switch ($type) {
            case 'password_reset':
                $content = <<<HTML
                <h2>Password Reset Request</h2>
                <p>Hello,</p>
                <p>We received a request to reset your password for your Alma101 account. Use the following code to complete your password reset:</p>
                <div class="code">{$data['otp']}</div>
                <p>This code will expire in 15 minutes for security reasons.</p>
                <p><strong>Note:</strong> If you didn't request this reset, please secure your account and contact us immediately.</p>
                HTML;
                break;

            case 'welcome':
                $content = <<<HTML
                <h2>Welcome to Alma101!</h2>
                <p>Hello {$data['username']},</p>
                <p>Welcome to Alma101! You're now part of our community of cybersecurity enthusiasts.</p>
                <p>Get started by exploring our:</p>
                <ul>
                    <li>Training Videos</li>
                    <li>Security Tools</li>
                    <li>Technical Blogs</li>
                    <li>Writeups</li>
                </ul>
                <a href="{$data['loginUrl']}" class="button">Access Your Account</a>
                HTML;
                break;

            case 'login_alert':
                $content = <<<HTML
                <h2>New Login Detected</h2>
                <p>Hello,</p>
                <p>We detected a new login to your AlmaTech Security account from:</p>
                <ul>
                    <li>Device: {$data['device']}</li>
                    <li>Location: {$data['location']}</li>
                    <li>Time: {$data['time']}</li>
                </ul>
                <p>If this wasn't you, please secure your account immediately by resetting your password.</p>
                HTML;
                break;

            case 'password_changed':
                $content = <<<HTML
                <h2>Password Successfully Changed</h2>
                <p>Hello,</p>
                <p>Your password was successfully changed on {$data['time']}.</p>
                <p>If you did not make this change, please contact us immediately.</p>
                HTML;
                break;
        }

        return str_replace('{CONTENT}', $content, $baseTemplate);
    }

    public function sendPasswordResetCode($to, $otp) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = 'Password Reset Code - Alma101';
            $this->mailer->Body = $this->getEmailTemplate('password_reset', ['otp' => $otp]);
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }

    public function sendWelcomeEmail($to, $username) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = 'Welcome to Alma101!';
            $loginUrl = 'https://your-domain.com/auth'; // Update with your domain
            $this->mailer->Body = $this->getEmailTemplate('welcome', [
                'username' => $username,
                'loginUrl' => $loginUrl
            ]);
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }

    public function sendLoginAlert($to, $deviceInfo) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = 'New Login Detected - Alma101';
            $this->mailer->Body = $this->getEmailTemplate('login_alert', $deviceInfo);
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }

    public function sendPasswordChangedNotification($to) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = 'Password Changed - Alma101';
            $this->mailer->Body = $this->getEmailTemplate('password_changed', [
                'time' => date('Y-m-d H:i:s')
            ]);
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }
}