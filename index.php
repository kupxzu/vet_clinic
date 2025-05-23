<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vet Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        /* Custom styles for the login dropdown and register modal */
        body {
            font-family: "Inter", sans-serif;
        }
        .dropdown-menu {
            display: none;
        }
        .dropdown-menu.active {
            display: block;
        }
        .modal {
            display: none; /* Hidden by default - ensures it's not visible on page load */
            position: fixed; /* Stay in place */
            z-index: 100; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }
        /* Override the default display:flex from .modal when it's not active */
        .modal:not(.active) {
            display: none;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%; /* Responsive width */
            max-width: 500px; /* Max width for larger screens */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-100">

    <nav class="bg-blue-600 p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="#" class="text-white text-2xl font-bold rounded-md px-3 py-2 hover:bg-blue-700 transition duration-300">Vet Clinic</a>

            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>

            <div class="hidden md:flex space-x-4 items-center">
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">Home</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">Services</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">About Us</a>
                <a href="#" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md transition duration-300">Contact</a>

                <div class="relative">
                    <button id="login-dropdown-button" class="bg-blue-700 text-white px-4 py-2 rounded-md hover:bg-blue-800 transition duration-300 focus:outline-none">
                        Login <i class="fas fa-caret-down ml-2"></i>
                    </button>
                    <div id="login-dropdown-menu" class="dropdown-menu absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg py-2 z-20">
                        <form action="app/config/Auth.php" method="POST" class="px-4 py-2">
                            <div class="mb-4">
                                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                                <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 rounded-md" placeholder="Enter your username">
                            </div>
                            <div class="mb-6">
                                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 rounded-md" placeholder="Enter your password">
                            </div>
                            <button type="submit" name="login" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md w-full transition duration-300">Sign In</button>
                        </form>
                        <hr class="my-2">
                        <p class="text-center text-sm text-gray-600">Don't have an account?</p>
                        <button id="register-button-from-login" class="text-blue-500 hover:underline block w-full text-center py-2">Register here</button>
                    </div>
                </div>

                <button id="register-button-navbar" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-300 focus:outline-none">
                    Register
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden bg-blue-700 mt-2 rounded-md shadow-md">
            <a href="#" class="block text-white hover:bg-blue-800 px-4 py-2 rounded-md transition duration-300">Home</a>
            <a href="#" class="block text-white hover:bg-blue-800 px-4 py-2 rounded-md transition duration-300">Services</a>
            <a href="#" class="block text-white hover:bg-blue-800 px-4 py-2 rounded-md transition duration-300">About Us</a>
            <a href="#" class="block text-white hover:bg-blue-800 px-4 py-2 rounded-md transition duration-300">Contact</a>
            <button id="mobile-login-button" class="block bg-blue-800 text-white px-4 py-2 rounded-md w-full text-left hover:bg-blue-900 transition duration-300 mt-2">Login</button>
            <button id="mobile-register-button" class="block bg-green-600 text-white px-4 py-2 rounded-md w-full text-left hover:bg-green-700 transition duration-300 mt-2">Register</button>
        </div>
    </nav>

    <header class="bg-gradient-to-r from-blue-500 to-purple-600 text-white py-20 text-center shadow-lg">
        <div class="container mx-auto px-4">
            <h1 class="text-5xl md:text-6xl font-extrabold mb-4 leading-tight">Your Pet's Health, Our Priority</h1>
            <p class="text-xl md:text-2xl mb-8">Compassionate and expert veterinary care for your beloved animals.</p>
            <a href="#" class="bg-white text-blue-600 hover:bg-gray-200 px-8 py-4 rounded-full text-lg font-semibold shadow-lg transition duration-300 transform hover:scale-105 inline-block">
                Book an Appointment
            </a>
        </div>
    </header>

    <main class="container mx-auto my-8 p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Welcome to Vet Clinic!</h2>
        <p class="text-gray-600 leading-relaxed">
            We are dedicated to providing the highest quality veterinary care for your pets. Our team of experienced
            veterinarians and staff are passionate about animal health and well-being. From routine check-ups to
            advanced surgical procedures, we offer a comprehensive range of services to keep your furry, feathered,
            or scaled friends healthy and happy.
        </p>
        <p class="text-gray-600 leading-relaxed mt-4">
            Explore our services, learn more about our team, or easily book an appointment online. We look forward
            to welcoming you and your pets!
        </p>
    </main>

    <footer class="bg-gray-800 text-white py-6 text-center mt-8">
        <div class="container mx-auto">
            <p>&copy; 2025 Vet Clinic. All rights reserved.</p>
            <div class="flex justify-center space-x-4 mt-2">
                <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="close-register-modal">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-gray-800 text-center">Register for an Account</h2>
            <form action="app/function/RegisterFunction.php" method="POST">
                <div class="mb-4">
                    <label for="reg-fullname" class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
                    <input type="text" id="reg-fullname" name="fullname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 rounded-md" placeholder="Your Full Name">
                </div>
                <div class="mb-4">
                    <label for="reg-email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" id="reg-email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 rounded-md" placeholder="your@example.com">
                </div>
                <div class="mb-4">
                    <label for="reg-username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" id="reg-username" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 rounded-md" placeholder="Choose a username">
                </div>
                <div class="mb-6">
                    <label for="reg-password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="reg-password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500 rounded-md" placeholder="Create a password">
                </div>
                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md w-full transition duration-300">Register</button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // JavaScript for Navbar, Login Dropdown, Register Modal, and Toastr Notifications
        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const loginDropdownButton = document.getElementById('login-dropdown-button');
            const loginDropdownMenu = document.getElementById('login-dropdown-menu');
            const registerButtonNavbar = document.getElementById('register-button-navbar');
            const registerButtonFromLogin = document.getElementById('register-button-from-login');
            const mobileLoginButton = document.getElementById('mobile-login-button');
            const mobileRegisterButton = document.getElementById('mobile-register-button');
            const registerModal = document.getElementById('register-modal');
            const closeRegisterModal = document.getElementById('close-register-modal');

            // Configure Toastr options
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Check for URL parameters for notifications
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');

            if (status && message) {
                if (status === 'success') {
                    toastr.success(message);
                } else if (status === 'error') {
                    toastr.error(message);
                }
                // Clear the URL parameters to prevent re-showing on refresh
                history.replaceState({}, document.title, window.location.pathname);
            }

            // Toggle mobile menu
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });

            // Toggle login dropdown
            loginDropdownButton.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from immediately closing dropdown
                loginDropdownMenu.classList.toggle('active');
            });

            // Close dropdown if clicked outside
            document.addEventListener('click', (event) => {
                if (!loginDropdownButton.contains(event.target) && !loginDropdownMenu.contains(event.target)) {
                    loginDropdownMenu.classList.remove('active');
                }
            });

            // Open register modal from navbar button
            registerButtonNavbar.addEventListener('click', () => {
                registerModal.style.display = 'flex'; // Show modal
                loginDropdownMenu.classList.remove('active'); // Close login dropdown if open
                mobileMenu.classList.add('hidden'); // Hide mobile menu if open
            });

            // Open register modal from login dropdown link
            registerButtonFromLogin.addEventListener('click', (event) => {
                event.preventDefault(); // Prevent default link behavior
                registerModal.style.display = 'flex'; // Show modal
                loginDropdownMenu.classList.remove('active'); // Close login dropdown
            });

            // Open register modal from mobile menu button
            mobileRegisterButton.addEventListener('click', () => {
                registerModal.style.display = 'flex'; // Show modal
                mobileMenu.classList.add('hidden'); // Hide mobile menu
            });

            // Handle mobile login button (can be extended to show login form or navigate)
            mobileLoginButton.addEventListener('click', () => {
                loginDropdownMenu.classList.toggle('active');
                mobileMenu.classList.add('hidden'); // Hide mobile menu
            });

            // Close register modal
            closeRegisterModal.addEventListener('click', () => {
                registerModal.style.display = 'none'; // Hide modal
            });

            // Close modal if clicked outside of content
            window.addEventListener('click', (event) => {
                if (event.target == registerModal) {
                    registerModal.style.display = 'none'; // Hide modal
                }
            });
        });
    </script>
</body>
</html>
