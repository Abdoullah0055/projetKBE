-- Migration: support half-star ratings in Reviews.

SET @reviews_table_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Reviews'
);

SET @sql := IF(
  @reviews_table_exists = 1,
  'ALTER TABLE Reviews MODIFY COLUMN Rating DECIMAL(2,1) NOT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rating_check_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Reviews'
    AND CONSTRAINT_NAME = 'CHK_Reviews_Rating'
    AND CONSTRAINT_TYPE = 'CHECK'
);

SET @sql := IF(
  @rating_check_exists = 1,
  'ALTER TABLE Reviews DROP CHECK CHK_Reviews_Rating',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rating_check_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Reviews'
    AND CONSTRAINT_NAME = 'CHK_Reviews_Rating'
    AND CONSTRAINT_TYPE = 'CHECK'
);

SET @sql := IF(
  @reviews_table_exists = 1 AND @rating_check_exists = 0,
  'ALTER TABLE Reviews ADD CONSTRAINT CHK_Reviews_Rating CHECK (Rating BETWEEN 1 AND 5 AND ROUND(Rating * 2) = Rating * 2)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
