<?php
// Error reporting for debugging (can be removed in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Get database connection
$conn = require_once 'db_connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = intval($_GET['id']);

try {
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, gender, phone_number, address, created_at FROM users WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Format user data with camelCase keys for JavaScript
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'gender' => $user['gender'],
            'phoneNumber' => $user['phone_number'],
            'address' => $user['address'],
            'createdAt' => $user['created_at']
        ];
        
        echo json_encode(['success' => true, 'user' => $userData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
