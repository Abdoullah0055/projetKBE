<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../includes/token_utils.php';

function get_smtp_config(): array
{
    $path = __DIR__ . '/../config/smtp_config.php';

    if (!is_file($path)) {
        return [];
    }

    $config = require $path;
    return is_array($config) ? $config : [];
}

function send_password_reset_email(string $toEmail, string $toAlias, string $resetUrl): array
{
    $smtp = get_smtp_config();
    $isEnabled = (bool) ($smtp['enabled'] ?? false);

    if (!$isEnabled) {
        return [
            'success' => true,
            'message' => 'SMTP desactive en mode developpement.',
            'dev_mode' => true,
        ];
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return [
            'success' => false,
            'message' => 'Dependances Composer introuvables. Installez PHPMailer.',
            'dev_mode' => false,
        ];
    }

    require_once $autoload;

    try {
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host = (string) ($smtp['host'] ?? 'smtp.gmail.com');
        $mailer->Port = (int) ($smtp['port'] ?? 587);
        $mailer->SMTPAuth = true;
        $mailer->Username = (string) ($smtp['username'] ?? '');
        $mailer->Password = (string) ($smtp['password'] ?? '');
        $mailer->SMTPSecure = (string) ($smtp['encryption'] ?? 'tls');

        $fromEmail = (string) ($smtp['from_email'] ?? $mailer->Username);
        $fromName = (string) ($smtp['from_name'] ?? 'L\'Arsenal');

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($toEmail, $toAlias);
        $mailer->CharSet = 'UTF-8';
        $mailer->isHTML(true);
        $mailer->Subject = 'Reinitialisation de votre mot de passe';

        $safeAlias = htmlspecialchars($toAlias !== '' ? $toAlias : 'aventurier', ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $mailer->Body = '<p>Bonjour ' . $safeAlias . ',</p>'
            . '<p>Vous avez demande la reinitialisation de votre mot de passe.</p>'
            . '<p><a href="' . $safeUrl . '">Cliquez ici pour definir un nouveau mot de passe</a></p>'
            . '<p>Ce lien expire dans 60 minutes.</p>';

        $mailer->AltBody = "Bonjour {$toAlias},\n\n"
            . "Utilisez ce lien pour reinitialiser votre mot de passe:\n{$resetUrl}\n\n"
            . "Ce lien expire dans 60 minutes.";

        $mailer->send();

        return [
            'success' => true,
            'message' => 'Email envoye.',
            'dev_mode' => false,
        ];
    } catch (PHPMailerException $e) {
        return [
            'success' => false,
            'message' => 'Envoi email echoue: ' . $e->getMessage(),
            'dev_mode' => false,
        ];
    }
}
