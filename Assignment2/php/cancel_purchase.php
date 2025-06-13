<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Get database connection
$conn = require_once 'db_connect.php';

// Get and sanitize POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if data is received
if (!$data || !isset($data['purchaseId']) || !isset($data['userId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$purchaseId = intval($data['purchaseId']);
$userId = intval($data['userId']);

try {
    // Check if the purchase belongs to the user
    $stmt = $conn->prepare("SELECT id FROM purchases WHERE id = ? AND user_id = ? AND status = 'ongoing'");
    $stmt->bind_param("ii", $purchaseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Purchase not found or cannot be cancelled']);
        exit;
    }
    
    // Update the purchase status to 'cancelled'
    $updateStmt = $conn->prepare("UPDATE purchases SET status = 'cancelled' WHERE id = ?");
    $updateStmt->bind_param("i", $purchaseId);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to cancel purchase: " . $updateStmt->error);
    }
    
    echo json_encode(['success' => true, 'message' => 'Purchase cancelled successfully']);
    
} catch (Exception $e) {
    error_log("Cancel purchase error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
