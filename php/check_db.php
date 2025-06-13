<?php
// Error reporting for debugging (can be removed in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Attempt to connect to the database and create tables
    require_once 'db_connect.php';
    
    // If we get here, the database setup was successful
    echo json_encode(['success' => true, 'message' => 'Database connection and setup successful']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database setup error: ' . $e->getMessage()]);
}
?>
