<?php
// Error reporting for debugging (can be removed in production)
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
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Make sure we have these columns in our users table
    // Checking if columns exist and create them if they don't
    $checkColumns = [
        'first_name' => 'VARCHAR(50)',
        'last_name' => 'VARCHAR(50)',
        'gender' => "ENUM('male', 'female', 'other')",
        'phone_number' => 'VARCHAR(20)',
        'address' => 'TEXT'
    ];
    
    foreach ($checkColumns as $column => $type) {
        // Check if column exists
        $checkSql = "SHOW COLUMNS FROM `users` LIKE '{$column}'";
        $result = $conn->query($checkSql);
        
        if ($result->num_rows == 0) {
            // Column doesn't exist, create it
            $alterSql = "ALTER TABLE `users` ADD COLUMN `{$column}` {$type}";
            if (!$conn->query($alterSql)) {
                throw new Exception("Failed to create column {$column}: " . $conn->error);
            }
        }
    }
    
    // Prepare SQL statement to update user profile
    $stmt = $conn->prepare("UPDATE users SET 
        first_name = ?, 
        last_name = ?, 
        gender = ?, 
        phone_number = ?, 
        address = ? 
        WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param(
        "sssssi", 
        $data['firstName'],
        $data['lastName'],
        $data['gender'],
        $data['phoneNumber'],
        $data['address'],
        $data['id']
    );
    
    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update profile: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
