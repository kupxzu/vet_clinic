<?php

require_once __DIR__ . '/../app/config/Auth.php';
redirectBasedOnRole('client');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            padding-bottom: 80px; /* Adjust this value based on your footer height */
        }
    </style>
</head>
<body class="bg-gray-100">

    <nav class="bg-blue-600 p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="#" class="text-white text-2xl font-bold rounded-md px-3 py-2">My Dashboard</a>
            <div class="flex space-x-4 items-center">
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">My Pets</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">Appointments</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">Profile</a>
                <form action="../../app/config/Auth.php" method="POST">
                    <button type="submit" name="logout" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition duration-300 focus:outline-none">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <header class="bg-gradient-to-r from-blue-500 to-purple-600 text-white py-16 text-center shadow-lg">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4">Welcome Back!</h1>
            <p class="text-lg md:text-xl">Manage your pet's appointments and health records.</p>
        </div>
    </header>