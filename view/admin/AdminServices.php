<?php
require_once __DIR__ . '/../../resource/AHeader.php';
require_once __DIR__ . '/../../app/config/Connection.php';
?>

<?php
$message = '';
$message_type = '';

// Handle Add Service form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $service_name = filter_input(INPUT_POST, 'service_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $service_fee = filter_input(INPUT_POST, 'service_fee', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!empty($service_name) && $service_fee !== false && $service_fee >= 0) {
        try {
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO services (service_name, service_fee, description) VALUES (:service_name, :service_fee, :description)");
            $stmt->execute([
                'service_name' => $service_name,
                'service_fee' => $service_fee,
                'description' => $description
            ]);
            $message = "Service '{$service_name}' added successfully!";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique constraint violation
                $message = "Service '{$service_name}' already exists.";
            } else {
                error_log("Add Service Error: " . $e->getMessage());
                $message = "Error adding service: " . $e->getMessage();
            }
            $message_type = 'error';
        }
    } else {
        $message = "Please fill all required fields correctly (Service Name and Fee).";
        $message_type = 'error';
    }
}

// Fetch existing services
$services = [];
try {
    global $pdo;
    $stmt_services = $pdo->query("SELECT id, service_name, service_fee, description, date_created FROM services ORDER BY service_name ASC");
    $services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch Services Error: " . $e->getMessage());
    $message = "Error fetching services list: " . $e->getMessage();
    $message_type = 'error';
}
?>

<main class="container mx-auto my-12 p-8 bg-white rounded-xl shadow-2xl max-w-4xl border border-gray-200">
    <div class="flex justify-between items-center mb-10">
        <h2 class="text-4xl font-extrabold text-gray-900 leading-tight">Manage Services</h2>
        <button id="trigger-add-service-modal-button" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300">
            <i class="fas fa-plus mr-2"></i> Add New Service
        </button>
    </div>

    <?php if ($message): ?>
        <div class="p-4 mb-8 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?> shadow-md text-lg font-medium">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Existing Services</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Service Name</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fee (PHP)</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date Added</th>
                    </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="4" class="py-4 px-6 text-center text-gray-500">No services found. Add one to get started!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-800 font-medium"><?php echo htmlspecialchars($service['service_name']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo number_format($service['service_fee'], 2); ?></td>
                            <td class="py-4 px-6 text-gray-600 text-sm max-w-xs truncate"><?php echo nl2br(htmlspecialchars($service['description'])); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo date('M d, Y', strtotime($service['date_created'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="add-service-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-lg w-11/12 relative">
        <span class="close-button" id="close-add-service-modal">&times;</span>
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Add New Service</h2>
        <form action="" method="POST" class="space-y-6">
            <div>
                <label for="service_name" class="block text-gray-700 text-base font-semibold mb-2">Service Name</label>
                <input type="text" id="service_name" name="service_name" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="service_fee" class="block text-gray-700 text-base font-semibold mb-2">Service Fee (PHP)</label>
                <input type="number" step="0.01" min="0" id="service_fee" name="service_fee" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="description" class="block text-gray-700 text-base font-semibold mb-2">Description</label>
                <textarea id="description" name="description" rows="4" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <button type="submit" name="add_service" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg">
                <i class="fas fa-plus mr-2"></i> Add Service
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addServiceModal = document.getElementById('add-service-modal');
    const triggerAddServiceModalButton = document.getElementById('trigger-add-service-modal-button');
    const closeAddServiceModalButton = document.getElementById('close-add-service-modal');

    if (triggerAddServiceModalButton && addServiceModal) {
        triggerAddServiceModalButton.addEventListener('click', () => addServiceModal.style.display = 'flex');
    }
    if (closeAddServiceModalButton && addServiceModal) {
        closeAddServiceModalButton.addEventListener('click', () => addServiceModal.style.display = 'none');
    }
    // Global click listener from AHeader.php will handle closing by clicking outside
});
</script>

<?php
require_once __DIR__ . '/../../resource/AFooter.php';
?>