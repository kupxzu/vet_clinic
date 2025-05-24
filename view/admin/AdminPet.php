<?php
require_once __DIR__ . '/../../resource/AHeader.php';
require_once __DIR__ . '/../../app/config/Connection.php';
?>

<?php
// PHP logic for handling form submissions for ADDING pets/breeds
$message = ''; // General message for the page
$message_type = '';
$add_pet_modal_message = ''; // Message specifically for the Add Pet Modal
$add_pet_modal_message_type = '';
$add_breed_modal_message = ''; // Message specifically for the Add Breed Modal
$add_breed_modal_message_type = '';

$show_add_pet_modal_on_load = false;
$show_add_breed_modal_on_load = false;

// Handle Add Pet Breed form submission (from within Add Pet Modal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_breed_from_modal'])) {
    $new_breed_name = filter_input(INPUT_POST, 'new_breed_name_modal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $show_add_pet_modal_on_load = true; // Re-open the main Add Pet modal
    $show_add_breed_modal_on_load = true; // Also, re-open the breed sub-modal to show the message

    if (!empty($new_breed_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pet_breeds (breed_name) VALUES (:breed_name)");
            $stmt->execute(['breed_name' => $new_breed_name]);
            $add_breed_modal_message = "New breed '{$new_breed_name}' added successfully!";
            $add_breed_modal_message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $add_breed_modal_message = "Breed '{$new_breed_name}' already exists.";
            } else {
                $add_breed_modal_message = "Error adding breed: " . $e->getMessage();
            }
            $add_breed_modal_message_type = 'error';
        }
    } else {
        $add_breed_modal_message = "Breed name cannot be empty.";
        $add_breed_modal_message_type = 'error';
    }
}


// Handle Add Client Pet form submission (from Add Pet Modal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pet_from_modal'])) {
    $client_id = filter_input(INPUT_POST, 'client_id_modal', FILTER_VALIDATE_INT);
    $pet_name = filter_input(INPUT_POST, 'pet_name_modal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pet_sex = filter_input(INPUT_POST, 'pet_sex_modal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pet_species = filter_input(INPUT_POST, 'pet_species_modal', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $breed_id = filter_input(INPUT_POST, 'breed_id_modal', FILTER_VALIDATE_INT);
    $pet_age = filter_input(INPUT_POST, 'pet_age_modal', FILTER_VALIDATE_INT);
    $show_add_pet_modal_on_load = true; // Re-open the modal to show success/error

    $allowed_sex = ['female', 'male'];
    $allowed_species = ['dog', 'cat', 'others'];

    if (!in_array($pet_sex, $allowed_sex)) {
        $add_pet_modal_message = "Invalid value for pet sex.";
        $add_pet_modal_message_type = 'error';
    } elseif (!in_array($pet_species, $allowed_species)) {
        $add_pet_modal_message = "Invalid value for pet species.";
        $add_pet_modal_message_type = 'error';
    } elseif ($client_id && !empty($pet_name) && !empty($pet_sex) && !empty($pet_species) && $breed_id && $pet_age !== false && $pet_age >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO pets (client_id, pet_name, pet_sex, pet_species, breed_id, pet_age) 
                VALUES (:client_id, :pet_name, :pet_sex, :pet_species, :breed_id, :pet_age)
            ");
            $stmt->execute([
                'client_id' => $client_id,
                'pet_name' => $pet_name,
                'pet_sex' => $pet_sex,
                'pet_species' => $pet_species,
                'breed_id' => $breed_id,
                'pet_age' => $pet_age
            ]);
            $add_pet_modal_message = "Pet '{$pet_name}' added successfully!";
            $add_pet_modal_message_type = 'success';
        } catch (PDOException $e) {
            $add_pet_modal_message = "Error adding pet: " . $e->getMessage();
            $add_pet_modal_message_type = 'error';
        }
    } else {
        $add_pet_modal_message = "Please fill all pet details correctly.";
        $add_pet_modal_message_type = 'error';
    }
}

// Fetch all clients for the main list and for the "Add Pet" modal dropdown
$all_clients_list = [];
try {
    $stmt_clients = $pdo->query("SELECT id, fullname, email FROM users WHERE role = 'client' ORDER BY fullname ASC");
    $all_clients_list = $stmt_clients->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching clients list: " . $e->getMessage();
    $message_type = 'error';
}

// Fetch all pet breeds for the "Add Pet" modal and "Edit Pet" modal dropdowns
$all_pet_breeds_list = [];
try {
    $stmt_breeds = $pdo->query("SELECT id, breed_name FROM pet_breeds ORDER BY breed_name ASC");
    $all_pet_breeds_list = $stmt_breeds->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching pet breeds: " . $e->getMessage();
    $message_type = 'error';
}

?>

<main class="container mx-auto my-12 p-8 bg-white rounded-xl shadow-2xl max-w-4xl border border-gray-200">
    <div class="flex justify-between items-center mb-10">
        <h2 class="text-4xl font-extrabold text-gray-900 leading-tight">Client Pet Management</h2>
        <button id="trigger-add-pet-modal-button" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300">
            <i class="fas fa-plus mr-2"></i> Add New Pet
        </button>
    </div>

    <?php if ($message && empty($add_pet_modal_message) && empty($add_breed_modal_message)): // Display general page messages if no modal messages ?>
        <div class="p-4 mb-8 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> shadow-md text-lg font-medium">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <h3 class="text-2xl font-semibold text-gray-800 mb-6">Client List</h3>
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client Name</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($all_clients_list)): ?>
                    <tr>
                        <td colspan="3" class="py-4 px-6 text-center text-gray-500">No clients found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_clients_list as $client_item): ?>
                        <tr>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-800 font-medium"><?php echo htmlspecialchars($client_item['fullname']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($client_item['email']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <button
                                    class="view-client-pets-button bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300"
                                    data-client-id="<?php echo htmlspecialchars($client_item['id']); ?>"
                                    data-client-name="<?php echo htmlspecialchars($client_item['fullname']); ?>"
                                >
                                    View/Edit Pets
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="add-pet-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-2xl w-11/12 relative">
        <span class="close-button" id="close-add-pet-modal">&times;</span>
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Add New Pet for Client</h2>

        <?php if ($add_pet_modal_message): ?>
            <div class="p-3 mb-6 rounded-lg <?php echo $add_pet_modal_message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> shadow-sm text-base font-medium">
                <?php echo htmlspecialchars($add_pet_modal_message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label for="client_id_modal" class="block text-gray-700 text-base font-semibold mb-2">Select Client</label>
                <select id="client_id_modal" name="client_id_modal" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Select a Client --</option>
                    <?php foreach ($all_clients_list as $client_item_modal): ?>
                        <option value="<?php echo htmlspecialchars($client_item_modal['id']); ?>">
                            <?php echo htmlspecialchars($client_item_modal['fullname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="pet_name_modal" class="block text-gray-700 text-base font-semibold mb-2">Pet Name</label>
                <input type="text" id="pet_name_modal" name="pet_name_modal" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Buddy" required>
            </div>

            <div>
                <label for="pet_sex_modal" class="block text-gray-700 text-base font-semibold mb-2">Pet Sex</label>
                <select id="pet_sex_modal" name="pet_sex_modal" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Select Sex --</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>

            <div>
                <label for="pet_species_modal" class="block text-gray-700 text-base font-semibold mb-2">Pet Species</label>
                <select id="pet_species_modal" name="pet_species_modal" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Select Species --</option>
                    <option value="dog">Dog</option>
                    <option value="cat">Cat</option>
                    <option value="others">Others</option>
                </select>
            </div>

            <div>
                <label for="breed_id_modal" class="block text-gray-700 text-base font-semibold mb-2">Select Pet Breed</label>
                <div class="flex items-center space-x-3">
                    <select id="breed_id_modal" name="breed_id_modal" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Select a Breed --</option>
                        <?php foreach ($all_pet_breeds_list as $breed_item_modal): ?>
                            <option value="<?php echo htmlspecialchars($breed_item_modal['id']); ?>">
                                <?php echo htmlspecialchars($breed_item_modal['breed_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="trigger-add-breed-submodal-button" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md">
                        Add Breed
                    </button>
                </div>
            </div>

            <div>
                <label for="pet_age_modal" class="block text-gray-700 text-base font-semibold mb-2">Pet Age (Years)</label>
                <input type="number" id="pet_age_modal" name="pet_age_modal" min="0" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., 3" required>
            </div>

            <button type="submit" name="add_pet_from_modal" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg">
                <i class="fas fa-paw mr-2"></i> Add This Pet
            </button>
        </form>
    </div>
</div>

<div id="add-breed-submodal" class="modal"> <div class="modal-content bg-gray-50 p-6 rounded-lg shadow-xl max-w-sm w-full relative">
        <span class="close-button" id="close-add-breed-submodal">&times;</span>
        <h3 class="text-2xl font-bold mb-6 text-gray-800 text-center">Add New Breed</h3>
        <?php if ($add_breed_modal_message): ?>
            <div class="p-2 mb-4 rounded-md <?php echo $add_breed_modal_message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> text-sm">
                <?php echo htmlspecialchars($add_breed_modal_message); ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" class="space-y-4">
            <div>
                <label for="new_breed_name_modal" class="block text-gray-700 text-sm font-semibold mb-1">Breed Name</label>
                <input type="text" id="new_breed_name_modal" name="new_breed_name_modal" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500" required>
            </div>
            <button type="submit" name="add_breed_from_modal" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md shadow-md">
                Save Breed
            </button>
        </form>
    </div>
</div>


<div id="view-client-pets-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-3xl w-11/12 relative overflow-y-auto" style="max-height: 90vh;">
        <span class="close-button" id="close-view-pets-modal">&times;</span>
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Pets of <span id="view_pets_client_name_display"></span></h2>
        <div id="client-pets-list-container" class="space-y-6">
            <p class="text-center text-gray-500">Loading pets...</p>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- MODAL 1: Add New Pet ---
    const addPetModal = document.getElementById('add-pet-modal');
    const triggerAddPetModalButton = document.getElementById('trigger-add-pet-modal-button');
    const closeAddPetModalButton = document.getElementById('close-add-pet-modal');

    if (triggerAddPetModalButton) {
        triggerAddPetModalButton.addEventListener('click', () => addPetModal.style.display = 'flex');
    }
    if (closeAddPetModalButton) {
        closeAddPetModalButton.addEventListener('click', () => addPetModal.style.display = 'none');
    }

    // --- SUB-MODAL: Add New Breed (within Add Pet Modal) ---
    const addBreedSubModal = document.getElementById('add-breed-submodal');
    const triggerAddBreedSubModalButton = document.getElementById('trigger-add-breed-submodal-button');
    const closeAddBreedSubModalButton = document.getElementById('close-add-breed-submodal');

    if (triggerAddBreedSubModalButton) {
        triggerAddBreedSubModalButton.addEventListener('click', () => addBreedSubModal.style.display = 'flex');
    }
    if (closeAddBreedSubModalButton) {
        closeAddBreedSubModalButton.addEventListener('click', () => addBreedSubModal.style.display = 'none');
    }

    // If PHP indicates modals should be open on load (due to form submission with message)
    const showAddPetModalOnLoad = <?php echo json_encode($show_add_pet_modal_on_load); ?>;
    const showAddBreedModalOnLoad = <?php echo json_encode($show_add_breed_modal_on_load); ?>;

    if (showAddPetModalOnLoad && addPetModal) {
        addPetModal.style.display = 'flex';
    }
    if (showAddBreedModalOnLoad && addBreedSubModal) { // This ensures the sub-modal also reopens if it had a message
        addBreedSubModal.style.display = 'flex';
    }


    // --- MODAL 2: View/Edit Client's Pets ---
    const viewClientPetsModal = document.getElementById('view-client-pets-modal');
    const viewClientPetsButtons = document.querySelectorAll('.view-client-pets-button');
    const closeViewPetsModalButton = document.getElementById('close-view-pets-modal');
    const clientPetsListContainer = document.getElementById('client-pets-list-container');
    const viewPetsClientNameDisplay = document.getElementById('view_pets_client_name_display');
    const allPetBreeds = <?php echo json_encode($all_pet_breeds_list); ?>;


    const renderPetEditForm = (pet) => {
        let breedOptions = allPetBreeds.map(breed => 
            `<option value="${breed.id}" ${pet.breed_id == breed.id ? 'selected' : ''}>${breed.breed_name}</option>`
        ).join('');

        return `
            <form class="pet-edit-form bg-gray-50 p-4 rounded-lg shadow border" data-pet-id="${pet.id}">
                <h4 class="text-xl font-semibold text-blue-700 mb-3">${pet.pet_name} (ID: ${pet.id})</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="pet_name" value="${pet.pet_name}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sex</label>
                        <select name="pet_sex" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                            <option value="male" ${pet.pet_sex === 'male' ? 'selected' : ''}>Male</option>
                            <option value="female" ${pet.pet_sex === 'female' ? 'selected' : ''}>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Species</label>
                        <select name="pet_species" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                            <option value="dog" ${pet.pet_species === 'dog' ? 'selected' : ''}>Dog</option>
                            <option value="cat" ${pet.pet_species === 'cat' ? 'selected' : ''}>Cat</option>
                            <option value="others" ${pet.pet_species === 'others' ? 'selected' : ''}>Others</option>
                        </select>
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700">Breed</label>
                        <select name="breed_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                            ${breedOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Age (Years)</label>
                        <input type="number" name="pet_age" value="${pet.pet_age}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" min="0">
                    </div>
                </div>
                <button type="submit" class="mt-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">Save Changes</button>
                <span class="text-sm ml-2 edit-pet-message-${pet.id}"></span>
            </form>
        `;
    };

    if (viewClientPetsButtons) {
        viewClientPetsButtons.forEach(button => {
            button.addEventListener('click', () => {
                const clientId = button.dataset.clientId;
                const clientName = button.dataset.clientName;
                viewPetsClientNameDisplay.textContent = clientName;
                clientPetsListContainer.innerHTML = '<p class="text-center text-gray-500">Loading pets...</p>';
                viewClientPetsModal.style.display = 'flex';

                fetch(`../../app/function/admin/ClientFunction.php?action=get_client_pets&client_id=${clientId}`)
                    .then(response => response.json())
                    .then(data => {
                        clientPetsListContainer.innerHTML = ''; // Clear loading
                        if (data.success && data.pets.length > 0) {
                            data.pets.forEach(pet => {
                                clientPetsListContainer.innerHTML += renderPetEditForm(pet);
                            });
                            // Attach event listeners to newly created save buttons
                            clientPetsListContainer.querySelectorAll('.pet-edit-form').forEach(form => {
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    const petId = this.dataset.petId;
                                    const formData = new FormData(this);
                                    formData.append('action', 'update_pet_details');
                                    formData.append('pet_id', petId);
                                    
                                    const messageSpan = this.querySelector(`.edit-pet-message-${petId}`);
                                    messageSpan.textContent = 'Saving...';
                                    messageSpan.className = `text-sm ml-2 edit-pet-message-${petId} text-blue-600`;


                                    fetch('../../app/function/admin/ClientFunction.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(updateData => {
                                        if (updateData.success) {
                                            messageSpan.textContent = 'Saved!';
                                            messageSpan.className = `text-sm ml-2 edit-pet-message-${petId} text-green-600`;
                                        } else {
                                            messageSpan.textContent = 'Error: ' + updateData.error;
                                            messageSpan.className = `text-sm ml-2 edit-pet-message-${petId} text-red-600`;
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Error updating pet:', err);
                                        messageSpan.textContent = 'Network error.';
                                        messageSpan.className = `text-sm ml-2 edit-pet-message-${petId} text-red-600`;
                                    });
                                });
                            });
                        } else if (data.success) {
                            clientPetsListContainer.innerHTML = '<p class="text-center text-gray-500">No pets found for this client.</p>';
                        } else {
                            clientPetsListContainer.innerHTML = `<p class="text-center text-red-500">Error: ${data.error || 'Could not load pets.'}</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching client pets:', error);
                        clientPetsListContainer.innerHTML = '<p class="text-center text-red-500">Failed to load pets due to a network error.</p>';
                    });
            });
        });
    }
    if (closeViewPetsModalButton) {
        closeViewPetsModalButton.addEventListener('click', () => viewClientPetsModal.style.display = 'none');
    }


    // Global click listener to close modals
    window.addEventListener('click', (event) => {
        if (event.target == addPetModal) {
            addPetModal.style.display = 'none';
        }
        if (event.target == addBreedSubModal) {
            addBreedSubModal.style.display = 'none';
        }
        if (event.target == viewClientPetsModal) {
            viewClientPetsModal.style.display = 'none';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../../resource/AFooter.php';
?>