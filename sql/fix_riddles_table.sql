-- Migration: fix Riddles table - add primary key, auto_increment, missing columns
-- Execute sur la base existante (idempotent)

-- 1. Add PRIMARY KEY on RiddleId if missing
SET @pk_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(
  @pk_exists = 0,
  'ALTER TABLE Riddles ADD PRIMARY KEY (RiddleId)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add AUTO_INCREMENT on RiddleId if missing
SET @autoinc_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND COLUMN_NAME = 'RiddleId'
    AND EXTRA LIKE '%auto_increment%'
);
SET @pk_actually_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(
  @autoinc_exists = 0 AND @pk_actually_exists = 1,
  'ALTER TABLE Riddles MODIFY RiddleId INT NOT NULL AUTO_INCREMENT',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add WrongAnswer1 column if missing
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND COLUMN_NAME = 'WrongAnswer1'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Riddles ADD COLUMN WrongAnswer1 VARCHAR(255) NOT NULL DEFAULT \'\' AFTER AnswerText',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Add WrongAnswer2 column if missing
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND COLUMN_NAME = 'WrongAnswer2'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Riddles ADD COLUMN WrongAnswer2 VARCHAR(255) NOT NULL DEFAULT \'\' AFTER WrongAnswer1',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Add WrongAnswer3 column if missing
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND COLUMN_NAME = 'WrongAnswer3'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Riddles ADD COLUMN WrongAnswer3 VARCHAR(255) NOT NULL DEFAULT \'\' AFTER WrongAnswer2',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Add RiddleType column if missing
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND COLUMN_NAME = 'RiddleType'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE Riddles ADD COLUMN RiddleType VARCHAR(50) DEFAULT \'MultipleChoice\'',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Migrate old TEXT/ShortAnswer riddles to MultipleChoice if RiddleType is NULL
UPDATE Riddles
SET RiddleType = 'MultipleChoice'
WHERE RiddleType IS NULL OR RiddleType = 'Text';
