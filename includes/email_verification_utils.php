<?php

function generate_email_verification_token(): string
{
    return bin2hex(random_bytes(32));
}

function upsert_email_verification(PDO $pdo, string $email, string $token): void
{
    $expiresAt = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        "INSERT INTO EmailVerifications (Email, Token, ExpiresAt, VerifiedAt)
         VALUES (?, ?, ?, NULL)
         ON DUPLICATE KEY UPDATE
            Token = VALUES(Token),
            ExpiresAt = VALUES(ExpiresAt),
            VerifiedAt = NULL"
    );
    $stmt->execute([$email, $token, $expiresAt]);
}

function invalidate_email_verification(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare("DELETE FROM EmailVerifications WHERE Email = ?");
    $stmt->execute([$email]);
}

function is_email_verified(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare("SELECT VerifiedAt FROM EmailVerifications WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return false;
    }

    return !empty($row['VerifiedAt'] ?? $row['verifiedat'] ?? null);
}
