<?php
require_once 'config/db.php';

try {
    // Add currency to users
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'USD' AFTER password");

    // Add currency to transactions
    $pdo->exec("ALTER TABLE transactions ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'USD' AFTER transaction_date");

    echo "Database updated successfully!";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>