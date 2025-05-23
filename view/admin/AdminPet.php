<?php
require_once '../../resource/AHeader.php';
require_once '../../app/config/Connection.php'; // Ensure this path is correct for database access
?>

<?php
// PHP logic for handling form submissions

$message = '';
$message_type = '';
$show_breed_modal_on_load = false; // Flag to control modal visibility after submission

// Handle Add Pet Breed form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_breed'])) {
    $new_breed_name = filter_input(INPUT_POST, 'new_breed_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $show_breed_modal_on_load = true; // Always show modal if this form was submitted

    if (!empty($new_breed_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pet_breeds (breed_name) VALUES (:breed_name)");
            $stmt->execute(['breed_name' => $new_breed_name]);
            $message = "New breed '{$new_breed_name}' added successfully!";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation (e.g., unique constraint)
                $message = "Breed '{$new_breed_name}' already exists.";
            } else {
                error_log("Add Breed Error: " . $e->getMessage());
                $message = "Error adding breed: " . $e->getMessage();
            }
            $message_type = 'error';
        }
    } else {
        $message = "Breed name cannot be empty.";
        $message_type = 'error';
    }
}

// Handle Add Client Pet form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pet'])) {
    $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
    $pet_name = filter_input(INPUT_POST, 'pet_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $breed_id = filter_input(INPUT_POST, 'breed_id', FILTER_VALIDATE_INT);
    $pet_age = filter_input(INPUT_POST, 'pet_age', FILTER_VALIDATE_INT);

    if ($client_id && !empty($pet_name) && $breed_id && $pet_age !== false) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pets (client_id, pet_name, breed_id, pet_age) VALUES (:client_id, :pet_name, :breed_id, :pet_age)");
            $stmt->execute([
                'client_id' => $client_id,
                'pet_name' => $pet_name,
                'breed_id' => $breed_id,
                'pet_age' => $pet_age
            ]);
            $message = "Pet '{$pet_name}' added successfully for client.";
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log("Add Pet Error: " . $e->getMessage());
            $message = "Error adding pet: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = "Please fill all pet details correctly.";
        $message_type = 'error';
    }
}

// Fetch clients for the dropdown
$clients = [];
try {
    $stmt = $pdo->query("SELECT id, fullname FROM users WHERE role = 'client' ORDER BY fullname ASC");
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Clients Error: " . $e->getMessage());
    $message = "Error fetching clients.";
    $message_type = 'error';
}

// Fetch pet breeds for the dropdown
$breeds = [];
try {
    $stmt = $pdo->query("SELECT id, breed_name FROM pet_breeds ORDER BY breed_name ASC");
    $breeds = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch Breeds Error: " . $e->getMessage());
    $message = "Error fetching pet breeds.";
    $message_type = 'error';
}
?>

<main class="container mx-auto my-12 p-8 bg-white rounded-xl shadow-2xl max-w-2xl border border-gray-200">
    <h2 class="text-4xl font-extrabold text-gray-900 mb-10 text-center leading-tight">Register a New Pet</h2>

    <?php if ($message && !$show_breed_modal_on_load): ?>
        <div class="p-4 mb-8 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?> shadow-md text-lg font-medium">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-7">
        <div>
            <label for="client_id" class="block text-gray-800 text-lg font-semibold mb-3">Select Client</label>
            <select id="client_id" name="client_id" class="block w-full px-5 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition duration-200 text-gray-700 text-base" required>
                <option value="">-- Select a Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo htmlspecialchars($client['id']); ?>">
                        <?php echo htmlspecialchars($client['fullname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="pet_name" class="block text-gray-800 text-lg font-semibold mb-3">Pet Name</label>
            <input type="text" id="pet_name" name="pet_name" class="block w-full px-5 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition duration-200 text-gray-700 text-base" placeholder="e.g., Buddy, Whiskers" required>
        </div>

        <div>
            <label for="breed_id" class="block text-gray-800 text-lg font-semibold mb-3">Select Pet Breed</label>
            <div class="flex items-center space-x-4">
                <select id="breed_id" name="breed_id" class="block w-full px-5 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition duration-200 text-gray-700 text-base" required>
                    <option value="">-- Select a Breed --</option>
                    <?php foreach ($breeds as $breed): ?>
                        <option value="<?php echo htmlspecialchars($breed['id']); ?>">
                            <?php echo htmlspecialchars($breed['breed_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add-breed-button" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 flex-shrink-0 text-base">
                    <i class="fas fa-plus-circle mr-2"></i> Add New
                </button>
            </div>
        </div>

        <div>
            <label for="pet_age" class="block text-gray-800 text-lg font-semibold mb-3">Pet Age (Years)</label>
            <input type="number" id="pet_age" name="pet_age" min="0" class="block w-full px-5 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition duration-200 text-gray-700 text-base" placeholder="e.g., 3" required>
        </div>

        <button type="submit" name="add_pet" class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3.5 px-8 rounded-lg shadow-xl transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 text-lg">
            <i class="fas fa-paw mr-3"></i> Add Pet to Client
        </button>
    </form>
</main>

<div id="add-breed-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-md w-11/12 relative">
        <span class="close-button text-gray-500 hover:text-gray-800 text-4xl leading-none absolute top-3 right-5 cursor-pointer">&times;</span>
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Add New Pet Breed</h2>
        <?php if ($message && $show_breed_modal_on_load): ?>
            <div class="p-3 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?> shadow-sm text-base font-medium">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-6">
            <div class="mb-4">
                <label for="new_breed_name" class="block text-gray-700 text-base font-semibold mb-2">Breed Name</label>
                <input type="text" id="new_breed_name" name="new_breed_name" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 text-gray-700 text-base" placeholder="e.g., Golden Retriever" required>
            </div>
            <button type="submit" name="add_breed" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3.5 px-6 rounded-lg shadow-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 text-lg">
                <i class="fas fa-plus mr-3"></i> Add Breed
            </button>
        </form>
    </div>
</div>

<script>
    // This script block is specific to AdminPet.php to handle modal visibility after form submission.
    document.addEventListener('DOMContentLoaded', () => {
        const addBreedModal = document.getElementById('add-breed-modal');

        // PHP variable passed to JavaScript to control modal visibility on load
        const showBreedModalOnLoad = <?php echo json_encode($show_breed_modal_on_load); ?>;

        if (showBreedModalOnLoad) {
            addBreedModal.style.display = 'flex';
        }
    });
</script>

<?php
require_once '../../resource/AFooter.php';
?>