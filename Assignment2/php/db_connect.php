<?php
// Error reporting for debugging (can be removed in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "assignment2_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die(json_encode(['success' => false, 'message' => 'Error creating database: ' . $conn->error]));
}

// Select the database
$conn->select_db($dbname);

// Create users table with all necessary fields
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    gender ENUM('male', 'female', 'other'),
    phone_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die(json_encode(['success' => false, 'message' => 'Error creating table: ' . $conn->error]));
}

// Return connection for use in other scripts
return $conn;
?>
