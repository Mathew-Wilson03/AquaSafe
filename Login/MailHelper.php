<?php
/**
 * MailHelper.php - Performance-optimized Email Service for AquaSafe
 * Handles SMTP and HTTP-based email delivery with lazy-loading and timeouts.
 */

class MailHelper {
    private static $smtpAvailable = true;
    private static $smtpTested = false;
    private static $failureLogged = false;
    private static $isRailway = null;

    /**
     * Sends an email using the configured driver (SMTP or HTTP)
     * Automatically falls back to HTTP if SMTP is unreachable.
     */
    public static function send($to, $subject, $body, $options = []) {
        if (!defined('ENABLE_EMAIL') || !ENABLE_EMAIL) {
            return true; // Silently skip if disabled
        }

        $driver = defined('MAIL_DRIVER') ? strtolower(MAIL_DRIVER) : 'smtp';

        try {
            if ($driver === 'smtp') {
                // Try SMTP first (includes connectivity check)
                $success = self::sendViaSmtp($to, $subject, $body);
                if ($success) return true;

                // FALLBACK: If SMTP fails/unreachable and we have an API key, try HTTP driver
                $apiKey = defined('MAIL_API_KEY') ? MAIL_API_KEY : '';
                if (!empty($apiKey)) {
                    if (!self::$failureLogged) {
                        error_log("[MailHelper] SMTP restricted or failed. Falling back to HTTP API for $to...");
                        self::$failureLogged = true;
                    }
                    return self::sendViaHttp($to, $subject, $body);
                }
                return false;
            }

            // Explicit HTTP driver
            return self::sendViaHttp($to, $subject, $body);

        } catch (\Throwable $t) {
            if (!self::$failureLogged) {
                error_log("[MailHelper] Fatal Error in send(): " . $t->getMessage());
                self::$failureLogged = true;
            }
            return false;
        }
    }

    /**
     * Quick SMTP Connectivity Check
     * Uses fsockopen to verify host/port before PHPMailer builds the object.
     */
    private static function checkSmtpConnectivity() {
        if (self::$smtpTested) return self::$smtpAvailable;

        // 1. Detect Railway Environment
        if (self::$isRailway === null) {
            $env = function_exists('get_env_var') ? get_env_var('RAILWAY_ENVIRONMENT', '') : (getenv('RAILWAY_ENVIRONMENT') ?: '');
            $pid = function_exists('get_env_var') ? get_env_var('RAILWAY_PROJECT_ID', '') : (getenv('RAILWAY_PROJECT_ID') ?: '');
            self::$isRailway = (!empty($env) || !empty($pid));
        }

        $host = defined('SMTP_HOST') ? SMTP_HOST : '';
        $port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
        $timeout = defined('MAIL_TIMEOUT') ? (float)MAIL_TIMEOUT : 2.0;

        if (empty($host)) {
            self::$smtpAvailable = false;
        } else {
            // Speed optimization: Use a fraction of MAIL_TIMEOUT for the raw socket check
            // A port that isn't blocked usually responds in < 100ms.
            $socketTimeout = min($timeout, 1.5); 
            
            error_log("[MailHelper] Checking SMTP connectivity to $host:$port (Railway: " . (self::$isRailway ? 'Yes' : 'No') . ")");
            
            $connection = @fsockopen($host, $port, $errno, $errstr, $socketTimeout);
            if (is_resource($connection)) {
                fclose($connection);
                self::$smtpAvailable = true;
            } else {
                if (!self::$failureLogged) {
                    $context = self::$isRailway ? "Railway Environment" : "Network";
                    error_log("[MailHelper] SMTP unreachable in $context ($host:$port). Error: $errstr ($errno)");
                    self::$failureLogged = true;
                }
                self::$smtpAvailable = false;
            }
        }

        self::$smtpTested = true;
        return self::$smtpAvailable;
    }

    /**
     * Send email via PHPMailer (SMTP)
     */
    private static function sendViaSmtp($to, $subject, $body) {
        // Fast-fail if socket check fails or previous attempt failed in this request
        if (!self::checkSmtpConnectivity()) return false;

        // Lazy-load Composer autoloader only when SMTP is actually used
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer', false)) {
            $autoloadPath = __DIR__ . '/vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
            } else {
                if (!self::$failureLogged) {
                    error_log("[MailHelper] PHPMailer Autoload missing.");
                    self::$failureLogged = true;
                }
                return false;
            }
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = (SMTP_PORT == 465) ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) SMTP_PORT;
            
            // STRICT TIMEOUTS: Use configured parameters
            $mail->Timeout    = defined('MAIL_TIMEOUT') ? (int)MAIL_TIMEOUT : 5; 
            $mail->SMTPKeepAlive = false;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            return $mail->send();
        } catch (\Exception $e) {
            // Detection: If connect fails, flag as unavailable to avoid retrying in same request
            if (strpos($mail->ErrorInfo, 'connect') !== false || strpos($mail->ErrorInfo, 'Network') !== false) {
                self::$smtpAvailable = false;
            }
            
            if (!self::$failureLogged) {
                error_log("[MailHelper] SMTP Send Failed: " . $mail->ErrorInfo);
                self::$failureLogged = true;
            }
            return false;
        }
    }

    /**
     * Send email via HTTP API (Direct Fallback)
     */
    private static function sendViaHttp($to, $subject, $body) {
        $apiKey = defined('MAIL_API_KEY') ? MAIL_API_KEY : '';
        $apiUrl = defined('MAIL_API_URL') ? MAIL_API_URL : '';

        if (empty($apiKey) || empty($apiUrl)) {
            return false; 
        }

        $payload = [
            'personalizations' => [['to' => [['email' => $to]]]],
            'from' => ['email' => SMTP_FROM_EMAIL, 'name' => SMTP_FROM_NAME],
            'subject' => $subject,
            'content' => [['type' => 'text/html', 'value' => $body]]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "Authorization: Bearer $apiKey"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            if (!self::$failureLogged) {
                error_log("[MailHelper] HTTP Fallback failed ($httpCode)");
                self::$failureLogged = true;
            }
            return false;
        }
    }
}
