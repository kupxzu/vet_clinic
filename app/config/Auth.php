<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Path to Connection.php, assuming Auth.php is in app/config/ and Connection.php is also in app/config/
require_once __DIR__ . '/Connection.php';

if (isset($_POST['logout'])) {
    logout();
}

if (isset($_POST['login'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        // Redirect to index.php from app/config/
        header('Location: ../../index.php?status=error&message=Username and password are required.');
        exit();
    }

    try {
        global $pdo;

        $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                // Redirect to view/admin/AdminDashboard.php from app/config/
                header('Location: ../../view/admin/AdminDashboard.php');
                exit();
            } else {
                // Redirect to view/user/UserDashboard.php from app/config/
                header('Location: ../../view/user/UserDashboard.php');
                exit();
            }
        } else {
            // Redirect to index.php from app/config/
            header('Location: ../../index.php?status=error&message=Invalid username or password.');
            exit();
        }

    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        // Redirect to index.php from app/config/
        header('Location: ../../index.php?status=error&message=An error occurred during login. Please try again later.');
        exit();
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function redirectBasedOnRole(string $expectedRole) {
    // This function is called from AHeader.php or UHeader.php,
    // which are then included in AdminDashboard.php or UserDashboard.php.
    // So, the current script context for relative paths is view/admin/ or view/user/.

    if (!isLoggedIn()) {
        // Redirect to index.php from view/admin/ or view/user/
        header('Location: ../../index.php?status=error&message=Please log in to access this page.');
        exit();
    }

    $userRole = $_SESSION['user_role'] ?? 'client';

    if ($expectedRole === 'admin' && $userRole !== 'admin') {
        // Redirect to view/user/UserDashboard.php from view/admin/
        header('Location: ../user/UserDashboard.php?status=error&message=You do not have permission to access the admin dashboard.');
        exit();
    } elseif ($expectedRole === 'client' && $userRole !== 'client' && $userRole !== 'admin') {
        // Redirect to index.php from view/user/
        header('Location: ../../index.php?status=error&message=You do not have permission to access this page.');
        exit();
    }
}

function logout() {
    $_SESSION = array();
    session_destroy();

    // Redirect to index.php from app/config/
    header('Location: ../../index.php?status=success&message=You have been logged out.');
    exit();
}