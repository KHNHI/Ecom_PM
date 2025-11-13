-- Migration: add payment_method column to orders table
-- Run this on your MySQL development DB (phpMyAdmin or mysql cli)

ALTER TABLE `orders`
  ADD COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod' AFTER `country`;

-- Optional: make sure payment_status column exists (paid/unpaid)
-- ALTER TABLE `orders` ADD COLUMN `payment_status` VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER `order_status`;
