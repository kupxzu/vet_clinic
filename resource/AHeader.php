<?php
require_once __DIR__ . '/../app/config/Auth.php';
redirectBasedOnRole('admin');
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 2rem;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            transform: translateY(-20px);
            animation: fadeInScale 0.3s ease-out forwards;
        }
        .close-button {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
        }
        .close-button:hover,
        .close-button:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        /* Styles for the new side panel */
        .sidebar {
            position: fixed;
            top: 0;
            right: -400px; /* Hidden off-screen by default */
            width: 400px;
            height: 100%;
            background-color: #fefefe;
            box-shadow: -5px 0 15px rgba(0,0,0,0.2);
            z-index: 100;
            transition: right 0.3s ease-in-out;
            overflow-y: auto;
        }
        .sidebar.open {
            right: 0; /* Slide in */
        }
    </style>
</head>
<body class="bg-gray-100 h-full">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="../admin/AdminDashboard.php" class="text-xl font-bold text-blue-600">
                            VetCare Admin
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-4">
                        <!-- Desktop Navigation -->
                        <a href="../admin/AdminDashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'AdminDashboard.php' ? 'border-b-2 border-blue-500' : ''; ?> inline-flex items-center px-1 pt-1 text-gray-700 hover:text-blue-600">
                            <i class="fas fa-home mr-2"></i> Dashboard
                        </a>
                        <a href="../admin/AdminClientList.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'AdminClientList.php' ? 'border-b-2 border-blue-500' : ''; ?> inline-flex items-center px-1 pt-1 text-gray-700 hover:text-blue-600">
                            <i class="fas fa-users mr-2"></i> Clients
                        </a>
                        <a href="../admin/AdminPet.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'AdminPet.php' ? 'border-b-2 border-blue-500' : ''; ?> inline-flex items-center px-1 pt-1 text-gray-700 hover:text-blue-600">
                            <i class="fas fa-paw mr-2"></i> Pets
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <form action="../../app/config/Auth.php" method="POST" class="ml-3">
                        <button type="submit" name="logout" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
                <div class="-mr-2 flex items-center sm:hidden">
                    <!-- Mobile menu button -->
                    <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="sm:hidden hidden" id="mobile-menu">
            <div class="pt-2 pb-3 space-y-1">
                <a href="../admin/AdminDashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'AdminDashboard.php' ? 'bg-blue-50 border-l-4 border-blue-500 text-blue-700' : 'border-l-4 border-transparent'; ?> block pl-3 pr-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="../admin/AdminClientList.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'AdminClientList.php' ? 'bg-blue-50 border-l-4 border-blue-500 text-blue-700' : 'border-l-4 border-transparent'; ?> block pl-3 pr-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300">
                    <i class="fas fa-users mr-2"></i> Clients
                </a>
                <a href="../admin/AdminPet.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'AdminPet.php' ? 'bg-blue-50 border-l-4 border-blue-500 text-blue-700' : 'border-l-4 border-transparent'; ?> block pl-3 pr-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300">
                    <i class="fas fa-paw mr-2"></i> Pets
                </a>
                <form action="../../app/config/Auth.php" method="POST" class="border-l-4 border-transparent">
                    <button type="submit" name="logout" class="block w-full text-left pl-3 pr-4 py-2 text-base font-medium text-red-700 hover:bg-gray-50 hover:border-gray-300">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-16 text-center shadow-lg">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4">Welcome, Admin!</h1>
            <p class="text-lg md:text-xl">Manage your Vet Clinic operations efficiently.</p>
        </div>
    </header>

    <!-- Your existing content for the admin dashboard goes here -->

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html>