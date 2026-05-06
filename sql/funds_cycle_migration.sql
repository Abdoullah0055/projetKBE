-- Migration: add FundsGivenCount cycle counter to Users table.

SET @users_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
);

SET @counter_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND COLUMN_NAME = 'FundsGivenCount'
);

SET @sql := IF(
  @users_exists = 1 AND @counter_exists = 0,
  'ALTER TABLE Users ADD COLUMN FundsGivenCount INT NOT NULL DEFAULT 0 AFTER Bronze',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
