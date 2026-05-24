<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

require_once __DIR__ . '/../mailing/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../mailing/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../mailing/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendJobEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = MAIL_HOST;

        $mail->SMTPAuth = true;

        $mail->Username = MAIL_USERNAME;

        $mail->Password = MAIL_PASSWORD;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = MAIL_PORT;

        $mail->setFrom(
            MAIL_FROM_EMAIL,
            MAIL_FROM_NAME
        );

        $mail->addAddress($to);

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = $body;

        $mail->send();

        return true;

    } catch (Exception $e) {

        writeLog(
            ERROR_LOG_FILE,
            "MAIL ERROR: " . $mail->ErrorInfo
        );

        return false;
    }
}

function buildJobsEmailTemplate($jobs)
{
    $html = "";

    $html .= "<h2>Latest Jobs</h2>";

    $html .= "<hr>";

    foreach ($jobs as $job) {

        $html .= "<div style='margin-bottom:20px;'>";

        $html .= "<h3>" . htmlspecialchars($job['title']) . "</h3>";

        $html .= "<p><strong>Company:</strong> " . htmlspecialchars($job['company_name']) . "</p>";

        $html .= "<p><strong>Location:</strong> " . htmlspecialchars($job['location']) . "</p>";

        $html .= "<p>";

        $html .= "<a href='" . $job['apply_url'] . "'>";

        $html .= "Apply Now";

        $html .= "</a>";

        $html .= "</p>";

        $html .= "</div>";
    }

    return $html;
}