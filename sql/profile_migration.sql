-- Migration profil utilisateur (a executer sur une base existante)
-- Version compatible large (sans "ADD COLUMN IF NOT EXISTS")

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND COLUMN_NAME = 'FullName'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Users ADD COLUMN FullName VARCHAR(80) NULL AFTER Alias',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND COLUMN_NAME = 'Email'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Users ADD COLUMN Email VARCHAR(190) NULL AFTER FullName',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND COLUMN_NAME = 'AvatarUrl'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Users ADD COLUMN AvatarUrl VARCHAR(255) NULL AFTER Email',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND COLUMN_NAME = 'ProfileIsDeleted'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Users ADD COLUMN ProfileIsDeleted TINYINT(1) NOT NULL DEFAULT 0 AFTER Bronze',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND COLUMN_NAME = 'ProfileDeletedAt'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Users ADD COLUMN ProfileDeletedAt DATETIME NULL AFTER ProfileIsDeleted',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Users'
    AND INDEX_NAME = 'UQ_Users_Email'
);

SET @idx_sql := IF(
  @idx_exists = 0,
  'ALTER TABLE Users ADD CONSTRAINT UQ_Users_Email UNIQUE (Email)',
  'SELECT 1'
);

PREPARE stmt_idx FROM @idx_sql;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;
