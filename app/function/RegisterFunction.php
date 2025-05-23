<?php

require_once '../config/Connection.php'; // Updated path based on your structure

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        header('Location: index.php?status=error&message=All fields are required.');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.php?status=error&message=Invalid email format.');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmtCheck->execute(['email' => $email, 'username' => $username]);

        if ($stmtCheck->fetch()) {
            header('Location: index.php?status=error&message=Email or username already exists.');
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (:fullname, :email, :username, :password, :role)");

        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindValue(':role', 'client');

        $stmt->execute();

        // Redirect with success message for Toastr
        header('Location: ../../index.php?status=success&message=Registration successful! You can now log in.');
        exit();

    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        header('Location: ./../index.php?status=error&message=Something went wrong with the registration. Please try again later.');
        exit();
    }
} else {
    header('Location: ./../index.php?status=error&message=Access Denied: This script should only be accessed via form submission.');
    exit();
}