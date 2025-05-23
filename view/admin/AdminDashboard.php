<?php
require_once __DIR__ . '/../../resource/AHeader.php';
require_once __DIR__ . '/../../app/config/Connection.php';
require_once __DIR__ . '/../../app/function/admin/StatisticsFunction.php';

$statistics = getClientStatistics();
?>

<main class="container mx-auto my-8 p-6">
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Today's Clients</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo $statistics['stats']['today']; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">This Week</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $statistics['stats']['week']; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">This Month</h3>
            <p class="text-3xl font-bold text-purple-600"><?php echo $statistics['stats']['month']; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Quick Actions</h3>
            <div class="flex gap-2">
                <a href="AdminClientList.php" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">View Clients</a>
                <a href="AdminPet.php" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">Add Pet</a>
            </div>
        </div>
    </div>

    <!-- Graphs Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Trend -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Monthly Registration Trend</h3>
            <canvas id="yearlyChart" width="400" height="200"></canvas>
        </div>
        <!-- Weekly Comparison -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Registration Statistics</h3>
            <canvas id="comparisonChart" width="400" height="200"></canvas>
        </div>
    </div>
</main>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    const yearlyData = <?php echo json_encode($statistics['stats']['yearly']); ?>;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const counts = Array(12).fill(0);
    
    yearlyData.forEach(data => {
        counts[data.month - 1] = parseInt(data.count);
    });

    new Chart(document.getElementById('yearlyChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Client Registrations',
                data: counts,
                borderColor: 'rgb(59, 130, 246)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Comparison Chart
    new Chart(document.getElementById('comparisonChart'), {
        type: 'bar',
        data: {
            labels: ['Today', 'This Week', 'This Month'],
            datasets: [{
                label: 'Number of Registrations',
                data: [
                    <?php echo $statistics['stats']['today']; ?>,
                    <?php echo $statistics['stats']['week']; ?>,
                    <?php echo $statistics['stats']['month']; ?>
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(139, 92, 246, 0.5)'
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(139, 92, 246)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../../resource/AFooter.php';
?>