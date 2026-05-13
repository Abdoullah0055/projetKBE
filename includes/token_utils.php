<?php
function generate_reset_token() {
    return bin2hex(random_bytes(32));
}

function save_reset_token($pdo, $email, $token) {
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
    $stmt = $pdo->prepare("REPLACE INTO PasswordResets (Email, Token, ExpiresAt) VALUES (?, ?, ?)");
    return $stmt->execute([$email, $token, $expires]);
}