-- Migration: add RiddleType to Riddles.

SET @riddles_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
);

SET @type_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND COLUMN_NAME = 'RiddleType'
);

SET @sql := IF(
  @riddles_exists = 1 AND @type_exists = 0,
  'ALTER TABLE Riddles ADD COLUMN RiddleType VARCHAR(20) NOT NULL DEFAULT ''qcm'' AFTER WrongAnswer3',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  @riddles_exists = 1,
  'UPDATE Riddles SET RiddleType = ''qcm'' WHERE RiddleType IS NULL OR RiddleType = ''''',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @check_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Riddles'
    AND CONSTRAINT_NAME = 'CHK_Riddles_RiddleType'
    AND CONSTRAINT_TYPE = 'CHECK'
);

SET @sql := IF(
  @riddles_exists = 1 AND @check_exists = 0,
  'ALTER TABLE Riddles ADD CONSTRAINT CHK_Riddles_RiddleType CHECK (RiddleType IN (''qcm'', ''vrai_faux'', ''phrase_courte''))',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
