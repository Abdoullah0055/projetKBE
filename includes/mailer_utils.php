<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function darquest_mailer_bootstrap(): bool
{
    if (class_exists(PHPMailer::class)) {
        return true;
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    return class_exists(PHPMailer::class);
}

function send_darquest_mail($to, $subject, $body) {
    if (!darquest_mailer_bootstrap()) {
        error_log("send_darquest_mail: PHPMailer is missing. Run composer install.");
        return false;
    }

    $config = require __DIR__ . '/../config/smtp_config.php';
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = $config['auth'];
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['secure'];
        $mail->Port       = $config['port'];

        $fromEmail = !empty($config['from_email']) ? $config['from_email'] : $config['username'];
        if (strcasecmp($fromEmail, (string)$config['username']) !== 0) {
            $fromEmail = $config['username'];
        }
        $mail->setFrom($fromEmail, $config['from_name']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
