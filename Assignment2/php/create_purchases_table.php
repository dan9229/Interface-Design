<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

// Get database connection
$conn = require_once 'db_connect.php';

try {
    // Create purchases table with default status as 'ongoing'
    $sql = "CREATE TABLE IF NOT EXISTS purchases (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        order_date DATETIME NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) DEFAULT 'ongoing',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) !== TRUE) {
        throw new Exception("Error creating purchases table: " . $conn->error);
    }

    // Create purchase_items table for the details of each purchase
    $sql = "CREATE TABLE IF NOT EXISTS purchase_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        quantity INT(11) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
    )";

    if ($conn->query($sql) !== TRUE) {
        throw new Exception("Error creating purchase_items table: " . $conn->error);
    }

    echo json_encode(['success' => true, 'message' => 'Purchase tables created successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
