<?php
require_once 'config.php';
if(DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
// Database connection details
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

try {
    // Create a new PDO instance and establish a connection
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Return a JSON error response if the connection fails
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}
