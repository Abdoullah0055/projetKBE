-- Migration: create PasswordResets table for password recovery.

SET @table_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'PasswordResets'
);

SET @sql := IF(
  @table_exists = 0,
  'CREATE TABLE PasswordResets (
      ResetId INT NOT NULL AUTO_INCREMENT,
      UserId INT NOT NULL,
      Email VARCHAR(190) NOT NULL,
      TokenHash CHAR(64) NOT NULL,
      ExpiresAt DATETIME NOT NULL,
      UsedAt DATETIME NULL,
      CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (ResetId),
      INDEX IDX_PasswordResets_UserId (UserId),
      INDEX IDX_PasswordResets_TokenHash (TokenHash),
      INDEX IDX_PasswordResets_Email (Email),
      CONSTRAINT FK_PasswordResets_Users FOREIGN KEY (UserId) REFERENCES Users(UserId) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
