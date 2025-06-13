<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Log the raw input for debugging
$raw_input = file_get_contents('php://input');
error_log("Raw input: " . $raw_input);

// Get database connection
$conn = require_once 'db_connect.php';

// Get and sanitize POST data
$data = json_decode($raw_input, true);

// Check if data is received
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received or invalid JSON']);
    exit;
}

// Validate required fields
if (empty($data['userId']) || empty($data['items']) || empty($data['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check if purchases table exists
    $tableCheckSql = "SHOW TABLES LIKE 'purchases'";
    $result = $conn->query($tableCheckSql);
    
    if ($result->num_rows == 0) {
        // Table doesn't exist, create it with default status 'ongoing'
        $conn->query("CREATE TABLE IF NOT EXISTS purchases (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            order_date DATETIME NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) DEFAULT 'ongoing',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $conn->query("CREATE TABLE IF NOT EXISTS purchase_items (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            purchase_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            quantity INT(11) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL
        )");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Calculate total amount
    $totalAmount = 0;
    foreach ($data['items'] as $item) {
        $totalAmount += $item['price'] * $item['quantity'];
    }
    
    // Format date for MySQL
    $orderDate = date('Y-m-d H:i:s', strtotime($data['date']));
    
    // Insert into purchases table - explicitly set status to 'ongoing'
    $stmt = $conn->prepare("INSERT INTO purchases (user_id, order_date, total_amount, status) VALUES (?, ?, ?, 'ongoing')");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("isd", $data['userId'], $orderDate, $totalAmount);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save purchase: " . $stmt->error);
    }
    
    // Get the purchase ID
    $purchaseId = $conn->insert_id;
    
    // Insert purchase items
    $itemStmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, product_name, product_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$itemStmt) {
        throw new Exception("Prepare items statement failed: " . $conn->error);
    }
    
    foreach ($data['items'] as $item) {
        $productId = $item['id'];
        $productName = $item['name'];
        $productPrice = $item['price'];
        $quantity = $item['quantity'];
        $subtotal = $productPrice * $quantity;
        
        $itemStmt->bind_param("iisdid", $purchaseId, $productId, $productName, $productPrice, $quantity, $subtotal);
        
        if (!$itemStmt->execute()) {
            throw new Exception("Failed to save purchase item: " . $itemStmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response with purchase ID
    echo json_encode([
        'success' => true, 
        'message' => 'Purchase saved successfully',
        'purchaseId' => $purchaseId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Purchase error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error processing purchase: ' . $e->getMessage(),
        'details' => error_get_last()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
