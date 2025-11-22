-- Travel App Database Schema
-- MariaDB/MySQL compatible

CREATE TABLE IF NOT EXISTS `travels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `city` VARCHAR(255) NOT NULL,
  `country` VARCHAR(255) NOT NULL,
  `year` INT(4) NOT NULL,
  `description` TEXT,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_year` (`year`),
  INDEX `idx_country` (`country`),
  INDEX `idx_coordinates` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

