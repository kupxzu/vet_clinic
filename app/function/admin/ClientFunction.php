<?php
// app/function/admin/ClientList.php - Endpoint to fetch pets for a specific client
// This file assumes it's being called by AJAX from a client-side script.

// Start the session if it hasn't been started already (Auth.php typically does this, but good practice for endpoints)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Adjust path: from app/function/admin/ to app/config/
require_once __DIR__ . '/../../../app/config/Connection.php';
require_once __DIR__ . '/../../../app/config/Auth.php';

// Ensure only authenticated admins can access this endpoint
redirectBasedOnRole('admin');

header('Content-Type: application/json');

$client_id = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid client ID.']);
    exit;
}

try {
    global $pdo; // Access the global PDO object from Connection.php
    $stmt = $pdo->prepare("SELECT p.id, p.pet_name, b.breed_name FROM pets p JOIN pet_breeds b ON p.breed_id = b.id WHERE p.client_id = :client_id ORDER BY p.pet_name ASC");
    $stmt->execute(['client_id' => $client_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'pets' => $pets]);

} catch (PDOException $e) {
    error_log("Get Client Pets Error: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again later.']);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage()); // Catch any other unexpected errors
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred.']);
}
?>