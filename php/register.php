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
error_log("Register data received: " . print_r($data, true));

// Validate required fields
if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Username, email and password are required']);
    exit;
}

// Additional validation
if (strlen($data['username']) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
    exit;
}

if (strlen($data['password']) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

try {
    // Hash the password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param("sss", $data['username'], $data['email'], $hashedPassword);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Get the user ID of the newly registered user
        $userId = $stmt->insert_id;
        
        // Create user object to return (excluding password)
        $user = [
            'id' => $userId,
            'username' => $data['username'],
            'email' => $data['email'],
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode(['success' => true, 'message' => 'Registration successful!', 'user' => $user]);
    } else {
        // Check for duplicate entry error
        if ($conn->errno === 1062) { // Duplicate entry error code
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $stmt->error]);
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
