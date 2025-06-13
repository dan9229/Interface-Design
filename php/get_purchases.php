<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Get database connection
$conn = require_once 'db_connect.php';

// Check if user ID is provided
if (!isset($_GET['userId']) || empty($_GET['userId'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = intval($_GET['userId']);

try {
    // Get all purchases for the user
    $purchases = [];
    
    // Query to get all purchases with their items
    $sql = "SELECT p.id, p.order_date, p.total_amount, p.status, p.created_at
            FROM purchases p
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process each purchase
    while ($purchase = $result->fetch_assoc()) {
        // Get items for this purchase
        $itemsSql = "SELECT pi.product_id, pi.product_name, pi.product_price, pi.quantity, pi.subtotal
                     FROM purchase_items pi
                     WHERE pi.purchase_id = ?";
        
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->bind_param("i", $purchase['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        
        // Format the purchase data
        $purchases[] = [
            'id' => $purchase['id'],
            'orderDate' => $purchase['order_date'],
            'totalAmount' => $purchase['total_amount'],
            'status' => $purchase['status'],
            'createdAt' => $purchase['created_at'],
            'items' => $items
        ];
    }
    
    echo json_encode(['success' => true, 'purchases' => $purchases]);
    
} catch (Exception $e) {
    error_log("Error fetching purchases: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
