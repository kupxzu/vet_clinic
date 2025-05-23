<?php
define('DB_HOST', 'localhost'); 
define('DB_NAME', 'vet_db');   
define('DB_USER', 'root');     
define('DB_PASS', '');         

try {
    // Create a new PDO instance
    // DSN (Data Source Name) string for MySQL
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // PDO options for error handling and fetching modes
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security and performance
    ];

    // Create the PDO connection object
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Optional: Echo a success message for debugging, remove in production
    // echo "Database connection successful!";

} catch (PDOException $e) {
    // Catch any PDO exceptions (e.g., connection errors)
    // Log the error message and terminate the script
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later. (Error Code: " . $e->getCode() . ")");
}
?>