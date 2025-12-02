<?php
namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use GuzzleHttp\Client as GuzzleClient;

class EmailService {
    private $mailer;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        // initialize from env where possible
        $this->fromEmail = $_ENV['SUPABASE_ADMIN_EMAIL'] ?? ($_ENV['EMAIL_FROM'] ?? 'machariaallan881@gmail.com');
        $this->fromName = $_ENV['EMAIL_FROM_NAME'] ?? 'Alma101';
        $this->configureSMTP();
    }

    private function configureSMTP() {
        $this->mailer->isSMTP();

        // Read SMTP configuration from environment variables with sensible defaults
        $host = $_ENV['EMAIL_SMTP_HOST'] ?? 'smtp.gmail.com';
        $user = $_ENV['EMAIL_SMTP_USER'] ?? $this->fromEmail;
        $pass = $_ENV['EMAIL_SMTP_PASS'] ?? '';
        $port = isset($_ENV['EMAIL_SMTP_PORT']) ? (int)$_ENV['EMAIL_SMTP_PORT'] : 587;
        $secure = $_ENV['EMAIL_SMTP_SECURE'] ?? 'tls'; // tls, ssl, or leave empty for none

        $this->mailer->Host = $host;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $user;
        $this->mailer->Password = $pass;

        // Configure encryption
        if (strtolower($secure) === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (strtolower($secure) === 'tls' || $secure === '') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $this->mailer->Port = $port;
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
        $this->mailer->isHTML(true);

        // Debugging control
        $debug = isset($_ENV['EMAIL_DEBUG']) && ($_ENV['EMAIL_DEBUG'] === '1' || strtolower($_ENV['EMAIL_DEBUG']) === 'true');
        $this->mailer->SMTPDebug = $debug ? 2 : 0;
        $this->mailer->Debugoutput = $debug ? 'html' : 'error_log';
    }

    private function getEmailTemplate($type, $data) {
        // Get the server's domain and protocol
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $logo = $protocol . $domain . '/Alma101-security/public/Alma101.jpg';
        
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
                <a href="{$data['resetLink']}" class="button" style="display:inline-block;padding:12px 24px;background:#4F46E5;color:white;text-decoration:none;border-radius:4px;margin:20px 0;">Reset Password</a>
                <p>This link will expire in 15 minutes for security reasons.</p>
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

    public function sendPasswordResetCode($to, $resetLink) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = 'Reset Your Password - Alma101';
            $this->mailer->Body = $this->getEmailTemplate('password_reset', ['resetLink' => $resetLink]);
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
            $loginUrl = 'https://alma101-cybersecurity.vercel.app/auth'; 
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

    /**
     * Send a generic contact email to admin with Reply-To set to the sender.
     * From will remain the configured site address (e.g. Alma101) so mail passes SPF.
     */
    public function sendContactEmail(string $to, string $senderName, string $senderEmail, string $messageHtml) {
        // Prefer HTTP provider if configured (useful when SMTP ports are blocked)
        $sendgridKey = $_ENV['SENDGRID_API_KEY'] ?? null;
        if ($sendgridKey) {
            $subject = 'New Contact Message from ' . $senderName;
            $body = $this->getEmailTemplate('welcome', ['username' => $senderName, 'loginUrl' => '']);
            $body .= $messageHtml;
            try {
                return $this->sendViaSendGrid($to, $subject, $body, ['email' => $senderEmail, 'name' => $senderName]);
            } catch (Exception $e) {
                error_log('SendGrid send failed: ' . $e->getMessage());
                // fall through to SMTP fallback
            }
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = 'New Contact Message from ' . $senderName;
            // set reply-to so admin can reply directly to the sender
            $this->mailer->addReplyTo($senderEmail, $senderName);

            // Use the base template and inject the message into content area
            $body = $this->getEmailTemplate('welcome', ['username' => $senderName, 'loginUrl' => '']);
            $body .= $messageHtml;

            $this->mailer->Body = $body;
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            throw new Exception("Failed to send email: " . $e->getMessage());
        }
    }

    /**
     * Send an email using SendGrid HTTP API v3
     * Returns true on accepted (202) or throws on error.
     */
    private function sendViaSendGrid(string $to, string $subject, string $htmlBody, ?array $replyTo = null) {
        $apiKey = $_ENV['SENDGRID_API_KEY'] ?? null;
        if (!$apiKey) {
            throw new Exception('No SendGrid API key configured');
        }

        try {
            $client = new GuzzleClient([ 'base_uri' => 'https://api.sendgrid.com' ]);

            $payload = [
                'personalizations' => [
                    [ 'to' => [ [ 'email' => $to ] ] ]
                ],
                'from' => [ 'email' => $this->fromEmail, 'name' => $this->fromName ],
                'subject' => $subject,
                'content' => [ [ 'type' => 'text/html', 'value' => $htmlBody ] ]
            ];

            if ($replyTo && isset($replyTo['email'])) {
                $payload['reply_to'] = [ 'email' => $replyTo['email'], 'name' => $replyTo['name'] ?? '' ];
            }

            $resp = $client->post('/v3/mail/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload,
                'http_errors' => false,
                'timeout' => 10
            ]);

            $status = $resp->getStatusCode();
            if ($status === 202) {
                return true;
            }

            $body = (string)$resp->getBody();
            error_log('SendGrid API error: HTTP ' . $status . ' - ' . $body);
            throw new Exception('SendGrid API returned HTTP ' . $status);
        } catch (\Exception $e) {
            error_log('SendGrid exception: ' . $e->getMessage());
            throw $e;
        }
    }
}