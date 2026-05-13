CREATE TABLE IF NOT EXISTS EmailVerifications (
    Email VARCHAR(190) NOT NULL,
    Token VARCHAR(128) NOT NULL,
    ExpiresAt DATETIME NOT NULL,
    VerifiedAt DATETIME NULL,
    PRIMARY KEY (Email),
    UNIQUE KEY uq_email_verification_token (Token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
