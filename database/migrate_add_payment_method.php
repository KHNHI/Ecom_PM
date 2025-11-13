<?php
/**
 * Migration: add `payment_method` column to `orders` table if missing
 * Run from CLI or browser (developer environment). Safe / idempotent check included.
 */
try {
    require_once __DIR__ . '/../configs/config.php';
    require_once __DIR__ . '/../configs/database.php';

    // Ensure $_SERVER index exists in CLI
    if (!isset($_SERVER['HTTP_HOST'])) {
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    $db = Database::getInstance();

    // Check if column already exists
    $db->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method'");
    $db->bind(':schema', _DB);
    $row = $db->single();

    if ($row && $row->cnt > 0) {
        echo "Column `payment_method` already exists in `orders`. Nothing to do.";
        exit;
    }

    // Add column
    $sql = "ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod' AFTER `country`";
    $db->query($sql);
    $db->execute();

    echo "Migration completed: added `payment_method` column to `orders`.";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage();
    exit(1);
}

