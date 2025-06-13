<?php
// Error reporting for debugging (can be removed in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Get database connection
$conn = require_once 'db_connect.php';

// Get and sanitize POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if data is received
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Log received data for debugging
error_log("Login attempt for user: " . ($data['username'] ?? 'unknown'));

// Validate required fields
if (empty($data['username']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

try {
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, email, password, created_at FROM users WHERE username = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $data['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($data['password'], $user['password'])) {
            // Remove password from user data before returning
            unset($user['password']);
            
            // Format user data with camelCase keys for JavaScript
            $userData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'createdAt' => $user['created_at']
            ];
            
            echo json_encode(['success' => true, 'message' => 'Login successful!', 'user' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
