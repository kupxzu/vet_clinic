<?php

require_once __DIR__ . '/../../resource/UHeader.php';
?>

    <main class="container mx-auto my-8 p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Your Recent Activity</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold text-blue-800 mb-2">Upcoming Appointment</h3>
                <p class="text-gray-700">Date: June 10, 2025</p>
                <p class="text-gray-700">Time: 10:00 AM</p>
                <p class="text-gray-700">Pet: Buddy (Dog)</p>
                <p class="text-gray-700">Service: Annual Check-up</p>
                <button class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Reschedule</button>
            </div>
            <div class="bg-green-50 p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold text-green-800 mb-2">Recent Visits</h3>
                <ul class="list-disc list-inside text-gray-700">
                    <li>May 15, 2025 - Fluffy (Cat) - Vaccination</li>
                    <li>April 20, 2025 - Max (Dog) - Dental Cleaning</li>
                </ul>
                <button class="mt-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">View All Records</button>
            </div>
        </div>
        <div class="mt-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-4">
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-md shadow-md transition duration-300">Book New Appointment</button>
                <button class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-md shadow-md transition duration-300">Add New Pet</button>
            </div>
        </div>
    </main>

<?php
require_once __DIR__ . '/../../resource/UFooter.php';
?>