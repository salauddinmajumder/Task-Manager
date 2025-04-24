<?php
// Database Configuration
define('DB_HOST', 'localhost'); // Or your database host
define('DB_USERNAME', 'root');    // Your database username
define('DB_PASSWORD', '');        // Your database password
define('DB_NAME', 'taskmanager_db'); // The database name you created

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Ensure UTF8 encoding for proper character handling
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch(PDOException $e) {
    // Important: Don't echo detailed errors in production
    error_log("Database Connection Error: " . $e->getMessage());
    // Return a generic error response
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check server logs.']);
    exit(); // Stop script execution
}
?>