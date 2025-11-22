-- Migration: Add latitude and longitude columns to existing travels table
-- Run this SQL script if you have an existing database

ALTER TABLE `travels` 
ADD COLUMN `latitude` DECIMAL(10,8) DEFAULT NULL AFTER `description`,
ADD COLUMN `longitude` DECIMAL(11,8) DEFAULT NULL AFTER `latitude`;

ALTER TABLE `travels`
ADD INDEX `idx_coordinates` (`latitude`, `longitude`);

