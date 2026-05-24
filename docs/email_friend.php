<?php

declare(strict_types=1);

// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// JSON RESPONSE
// ============================================================

header('Content-Type: application/json; charset=UTF-8');

// ============================================================
// CONFIG
// ============================================================

$configPath = __DIR__ . '/core/config.php';

if (!file_exists($configPath)) {

    if (!is_dir(__DIR__ . '/core')) {
        mkdir(__DIR__ . '/core', 0755, true);
    }

    $defaultConfig = <<<'PHP'
        <?php

        define('SMTP_HOST', 'smtp.gmail.com');
        define('SMTP_PORT', 587);
        define('SMTP_SECURE', 'tls');
        define('SMTP_AUTH', true);

        // YOUR REAL GMAIL
        define('SMTP_USERNAME', 'yourgmail@gmail.com');

        // YOUR GOOGLE APP PASSWORD
        define('SMTP_PASSWORD', 'your_app_password');

        // MUST BE SAME AS SMTP USERNAME FOR GMAIL
        define('FROM_EMAIL', 'yourgmail@gmail.com');

        define('FROM_NAME', 'Bisure Jobs');
        PHP;

            file_put_contents($configPath, $defaultConfig);
        }

        require_once $configPath;

        // ============================================================
        // RATE LIMIT
        // ============================================================

        define('MAX_EMAILS_PER_HOUR', 100);

        // ============================================================
        // PHPMailer
        // ============================================================

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;

        $phpmailerDir = __DIR__ . '/mailing/PHPMailer/src/';

        $phpmailerExists =
            file_exists($phpmailerDir . 'PHPMailer.php') &&
            file_exists($phpmailerDir . 'SMTP.php') &&
            file_exists($phpmailerDir . 'Exception.php');

        if ($phpmailerExists) {

            require_once $phpmailerDir . 'PHPMailer.php';
            require_once $phpmailerDir . 'SMTP.php';
            require_once $phpmailerDir . 'Exception.php';

            define('USE_PHPMAILER', true);

        } else {

            define('USE_PHPMAILER', false);
        }

        // ============================================================
        // HELPERS
        // ============================================================

        function jsonResponse(
            bool $success,
            string $message,
            array $extra = []
        ): void {

            echo json_encode(array_merge([
                'success' => $success,
                'message' => $message
            ], $extra));

            exit;
        }

        function sanitize(string $value): string
        {
            return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        function validEmail(string $email): bool
        {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }

        function ensureDirectory(string $path): void
        {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        function logEmail(array $data): void
        {
            $dir = __DIR__ . '/logs';

            ensureDirectory($dir);

            $file = $dir . '/email_log.txt';

            $entry = [
                'time' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'data' => $data
            ];

            file_put_contents(
                $file,
                json_encode($entry) . PHP_EOL,
                FILE_APPEND
            );
        }

        function checkRateLimit(): bool
        {
            $dir = __DIR__ . '/logs';

            ensureDirectory($dir);

            $file = $dir . '/rate_limit.txt';

            if (!file_exists($file)) {
                return true;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $hour = date('Y-m-d-H');

            $count = 0;

            $lines = file(
                $file,
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            );

            foreach ($lines as $line) {

                $data = json_decode($line, true);

                if (
                    isset($data['ip'], $data['hour']) &&
                    $data['ip'] === $ip &&
                    $data['hour'] === $hour
                ) {
                    $count++;
                }
            }

            return $count < MAX_EMAILS_PER_HOUR;
        }

        function updateRateLimit(): void
        {
            $dir = __DIR__ . '/logs';

            ensureDirectory($dir);

            $file = $dir . '/rate_limit.txt';

            $entry = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'hour' => date('Y-m-d-H'),
                'time' => time()
            ];

            file_put_contents(
                $file,
                json_encode($entry) . PHP_EOL,
                FILE_APPEND
            );
        }

        // ============================================================
        // EMAIL TEMPLATE
        // ============================================================

        function buildEmailTemplate(
            string $friendName,
            string $senderName,
            string $jobLink,
            string $message
        ): string {

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Job Recommendation</title>
        </head>

        <body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>

            <div style='max-width:600px;margin:30px auto;background:#ffffff;border-radius:10px;overflow:hidden;'>

                <div style='background:#6C5CE7;padding:25px;text-align:center;color:#ffffff;'>
                    <h2 style='margin:0;'>
                        Job Opportunity Shared With You
                    </h2>
                </div>

                <div style='padding:30px;'>

                    <p>
                        Hello <strong>{$friendName}</strong>,
                    </p>

                    <p>
                        <strong>{$senderName}</strong>
                        shared a job opportunity with you.
                    </p>

                    <div style='background:#f1f1f1;padding:15px;border-radius:8px;margin:20px 0;text-align:center;'>
                        <a href='{$jobLink}' 
                        style='display:inline-block;
                                background:#6C5CE7;
                                color:#ffffff;
                                text-decoration:none;
                                padding:12px 24px;
                                border-radius:6px;
                                font-weight:bold;'>
                            🔍 Click Here to View Job
                        </a>
                        <p style='margin-top:15px;font-size:12px;color:#666;word-break:break-all;'>
                            Or copy this link: <a href='{$jobLink}' style='color:#6C5CE7;'>{$jobLink}</a>
                        </p>
                    </div>

                    <div style='background:#fafafa;border-left:4px solid #6C5CE7;padding:15px;margin-bottom:20px;'>

                        <strong>Message:</strong>
                        <br><br>

                        " . nl2br($message) . "

                    </div>

                    <div style='text-align:center;margin-top:30px;'>

                        <a href='https://www.bisurejobs.22web.org'
                        style='display:inline-block;
                                background:#6C5CE7;
                                color:#ffffff;
                                text-decoration:none;
                                padding:12px 24px;
                                border-radius:6px;'>

                            View Jobs

                        </a>

                    </div>

                </div>

                <div style='padding:15px;text-align:center;font-size:12px;color:#777;background:#fafafa;'>

                    © " . date('Y') . " Bisure Jobs

                </div>

            </div>

        </body>
        </html>
        ";
    }

// ============================================================
// SEND EMAIL
// ============================================================

function sendEmail(
    string $friendEmail,
    string $friendName,
    string $senderName,
    string $senderEmail,
    string $jobLink,
    string $message
): array {

    $subject = "Job Opportunity Shared By {$senderName}";

    $htmlBody = buildEmailTemplate(
        $friendName,
        $senderName,
        $jobLink,
        $message
    );

    $plainBody = "
        Hello {$friendName},

        {$senderName} shared a job opportunity with you.

        JOB LINK:
        {$jobLink}

        Message:
        {$message}

        Copy and paste the link above into your browser to view the job.
        ";

    // ========================================================
    // PHPMailer
    // ========================================================

    if (USE_PHPMAILER) {

        try {

            $mail = new PHPMailer(true);

            // =====================================================
            // SMTP
            // =====================================================

            $mail->isSMTP();

            // IMPORTANT:

            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;

            // TLS
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port       = SMTP_PORT;

            // =====================================================
            // SSL OPTIONS
            // =====================================================

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            // =====================================================
            // TIMEOUT
            // =====================================================

            $mail->Timeout = 60;

            // =====================================================
            // FROM
            // =====================================================

            $mail->setFrom(FROM_EMAIL, FROM_NAME);

            // =====================================================
            // TO
            // =====================================================

            $mail->addAddress($friendEmail, $friendName);

            // =====================================================
            // REPLY TO
            // =====================================================

            $mail->addReplyTo($senderEmail, $senderName);

            // =====================================================
            // EMAIL CONTENT
            // =====================================================

            $mail->isHTML(true);

            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            // =====================================================
            // SEND EMAIL
            // =====================================================

            $mail->send();

            return [
                'success' => true,
                'error'   => null
            ];

        } catch (Exception $e) {

            error_log('PHPMailer Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    // ========================================================
    // FALLBACK MAIL()
    // ========================================================

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: {$senderEmail}\r\n";

    $sent = mail(
        $friendEmail,
        $subject,
        $htmlBody,
        $headers
    );

    return [
        'success' => $sent,
        'error' => $sent ? null : 'PHP mail() failed'
    ];
}

// ============================================================
// REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonResponse(false, 'Invalid request method');
}

// ============================================================
// INPUT
// ============================================================

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!$input) {
    $input = $_POST;
}

// ============================================================
// REQUIRED FIELDS
// ============================================================

$required = [
    'sendername',
    'senderemail',
    'email1',
    'message',
    'jobid'
];

foreach ($required as $field) {

    if (
        !isset($input[$field]) ||
        trim($input[$field]) === ''
    ) {

        jsonResponse(
            false,
            "Missing field: {$field}"
        );
    }
}

// ============================================================
// SANITIZE
// ============================================================

$senderName  = sanitize($input['sendername']);
$senderEmail = sanitize($input['senderemail']);
$friendEmail = sanitize($input['email1']);
$message     = sanitize($input['message']);
$jobId       = sanitize($input['jobid']);

$jobLink = isset($input['joblink'])
    ? sanitize($input['joblink'])
    : 'https://www.bisurejobs.22web.org';

// ============================================================
// VALIDATION
// ============================================================

if (!validEmail($senderEmail)) {

    jsonResponse(
        false,
        'Invalid sender email'
    );
}

if (!validEmail($friendEmail)) {

    jsonResponse(
        false,
        'Invalid friend email'
    );
}

if (strlen($message) > 2000) {

    jsonResponse(
        false,
        'Message is too long'
    );
}

if (!checkRateLimit()) {

    jsonResponse(
        false,
        'Too many emails sent. Try again later.'
    );
}

// ============================================================
// FRIEND NAME
// ============================================================

$friendName = ucfirst(
    explode('@', $friendEmail)[0]
);

// ============================================================
// SEND EMAIL
// ============================================================

$result = sendEmail(
    $friendEmail,
    $friendName,
    $senderName,
    $senderEmail,
    $jobLink,
    $message
);

// ============================================================
// RESPONSE
// ============================================================

if ($result['success']) {

    updateRateLimit();

    logEmail([
        'status' => 'SUCCESS',
        'sender' => $senderEmail,
        'friend' => $friendEmail,
        'job_id' => $jobId
    ]);

    jsonResponse(
        true,
        'Job recommendation sent successfully!'
    );

} else {

    logEmail([
        'status' => 'FAILED',
        'sender' => $senderEmail,
        'friend' => $friendEmail,
        'job_id' => $jobId,
        'error' => $result['error']
    ]);

    jsonResponse(
        false,
        'Failed to send email',
        [
            'debug_error' => $result['error']
        ]
    );
}
?>