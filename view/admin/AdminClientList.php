<?php
require_once __DIR__ . '/../../resource/AHeader.php';
require_once __DIR__ . '/../../app/config/Connection.php';
?>

<?php
// PHP Logic for client listing and appointment handling

$clients_per_page = 10;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
if ($current_page < 1) {
    $current_page = 1;
}

$offset = ($current_page - 1) * $clients_per_page;

$message = '';
$message_type = '';

// Handle Appointment form submission from modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_appointment'])) {
    $client_id = filter_input(INPUT_POST, 'modal_client_id', FILTER_VALIDATE_INT);
    $pet_id = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT);
    $appointment_datetime = filter_input(INPUT_POST, 'appointment_datetime', FILTER_SANITIZE_STRING);

    if ($client_id && $pet_id && !empty($appointment_datetime)) {
        try {
            $dateTime = new DateTime($appointment_datetime);
            $appointment_date = $dateTime->format('Y-m-d');
            $appointment_time = $dateTime->format('H:i:s');

            $stmt = $pdo->prepare("INSERT INTO appointments (client_id, pet_id, appointment_date, appointment_time, appointment_type, status) VALUES (:client_id, :pet_id, :appointment_date, :appointment_time, 'Check-up', 'pending')");
            $stmt->execute([
                'client_id' => $client_id,
                'pet_id' => $pet_id,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time
            ]);
            $message = "Appointment set successfully!";
            $message_type = 'success';
        } catch (PDOException $e) {
            error_log("Set Appointment Error: " . $e->getMessage());
            $message = "Error setting appointment: " . $e->getMessage();
            $message_type = 'error';
        } catch (Exception $e) {
            $message = "Invalid date/time format provided.";
            $message_type = 'error';
        }
    } else {
        $message = "Please fill all appointment details correctly.";
        $message_type = 'error';
    }
}

// Fetch total number of clients
$total_clients = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'client'");
    $total_clients = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Count Clients Error: " . $e->getMessage());
    $message = "Error fetching client count.";
    $message_type = 'error';
}

$total_pages = ceil($total_clients / $clients_per_page);

// Fetch clients for the current page
$clients = [];
try {
    $stmt = $pdo->prepare("SELECT id, fullname, email, date_created FROM users WHERE role = 'client' ORDER BY fullname ASC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $clients_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch Clients List Error: " . $e->getMessage());
    $message = "Error fetching clients list.";
    $message_type = 'error';
}
?>

<main class="container mx-auto my-12 p-8 bg-white rounded-xl shadow-2xl max-w-4xl border border-gray-200">
    <h2 class="text-4xl font-extrabold text-gray-900 mb-10 text-center leading-tight">Manage Clients & Appointments</h2>

    <?php if ($message): ?>
        <div class="p-4 mb-8 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?> shadow-md text-lg font-medium">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client Name</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date Registered</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="4" class="py-4 px-6 text-center text-gray-500">No clients found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-800 font-medium"><?php echo htmlspecialchars($client['fullname']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($client['email']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo date('M d, Y', strtotime($client['date_created'])); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap space-x-2">
                                <button
                                    class="set-appointment-button bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    data-client-id="<?php echo htmlspecialchars($client['id']); ?>"
                                    data-client-name="<?php echo htmlspecialchars($client['fullname']); ?>"
                                >
                                    Set Appointment
                                </button>
                                <button
                                    class="view-appointments-button bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                                    data-client-id="<?php echo htmlspecialchars($client['id']); ?>"
                                    data-client-name="<?php echo htmlspecialchars($client['fullname']); ?>"
                                >
                                    View List
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex justify-center mt-8 space-x-2">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition duration-300">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded-md <?php echo ($i == $current_page) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-300">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition duration-300">Next</a>
        <?php endif; ?>
    </div>
</main>

<div id="set-appointment-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-md w-11/12 relative">
        <span class="close-button text-gray-500 hover:text-gray-800 text-4xl leading-none absolute top-3 right-5 cursor-pointer">&times;</span>
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Set New Appointment</h2>
        <form action="" method="POST" class="space-y-6">
            <input type="hidden" id="modal_client_id" name="modal_client_id">
            <p class="text-gray-700 text-center mb-4">Client: <strong id="modal_client_name"></strong></p>

            <div>
                <label for="pet_id" class="block text-gray-700 text-base font-semibold mb-2">Select Pet</label>
                <select id="pet_id" name="pet_id" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" required>
                    <option value="">Loading pets...</option>
                </select>
            </div>

            <div>
                <label for="appointment_datetime" class="block text-gray-700 text-base font-semibold mb-2">Appointment Date & Time</label>
                <input type="datetime-local" id="appointment_datetime" name="appointment_datetime" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" required>
            </div>

            <button type="submit" name="set_appointment" class="w-full bg-gradient-to-r from-purple-600 to-indigo-700 hover:from-purple-700 hover:to-indigo-800 text-white font-bold py-3.5 px-6 rounded-lg shadow-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 text-lg">
                <i class="far fa-calendar-alt mr-3"></i> Confirm Appointment
            </button>
        </form>
    </div>
</div>

<div id="appointments-sidebar" class="sidebar p-8">
    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
        <h3 class="text-2xl font-bold text-gray-800">Appointments for <span id="sidebar_client_name"></span></h3>
        <button id="close-sidebar" class="text-gray-500 hover:text-gray-800 text-4xl leading-none">&times;</button>
    </div>

    <div id="appointments-list" class="space-y-6">
        <p class="text-gray-500 text-center">Loading appointments...</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const setAppointmentButtons = document.querySelectorAll('.set-appointment-button');
        const setAppointmentModal = document.getElementById('set-appointment-modal');
        const closeAppointmentModal = setAppointmentModal.querySelector('.close-button');
        const modalClientIdInput = document.getElementById('modal_client_id');
        const modalClientNameDisplay = document.getElementById('modal_client_name');
        const petSelect = document.getElementById('pet_id');

        const viewAppointmentsButtons = document.querySelectorAll('.view-appointments-button');
        const appointmentsSidebar = document.getElementById('appointments-sidebar');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const sidebarClientNameDisplay = document.getElementById('sidebar_client_name');
        const appointmentsListDiv = document.getElementById('appointments-list');

        // Ensure modals/sidebars are hidden on page load
        if (setAppointmentModal) { setAppointmentModal.style.display = 'none'; }
        if (appointmentsSidebar) { appointmentsSidebar.classList.remove('open'); } // Ensure it starts closed

        // Close modal function
        const closeModal = (modalElement) => {
            modalElement.style.display = 'none';
        };

        // Close sidebar function
        const closeSidebar = () => {
            appointmentsSidebar.classList.remove('open');
        };

        // Event listeners for closing modal/sidebar
        if (closeAppointmentModal) { closeAppointmentModal.addEventListener('click', () => closeModal(setAppointmentModal)); }
        if (closeSidebarButton) { closeSidebarButton.addEventListener('click', closeSidebar); }
        window.addEventListener('click', (event) => {
            if (event.target == setAppointmentModal) { closeModal(setAppointmentModal); }
            // For sidebar, clicking outside should close it if it's open, but not if clicking inside it
            if (appointmentsSidebar.classList.contains('open') && !appointmentsSidebar.contains(event.target) && !event.target.closest('.view-appointments-button')) {
                 closeSidebar();
            }
        });

        // Set Appointment Button Logic
        setAppointmentButtons.forEach(button => {
            button.addEventListener('click', () => {
                const clientId = button.dataset.clientId;
                const clientName = button.dataset.clientName;

                modalClientIdInput.value = clientId;
                modalClientNameDisplay.textContent = clientName;

                petSelect.innerHTML = '<option value="">Loading pets...</option>';
                petSelect.disabled = true;

                fetch(`../../app/function/admin/ClientFunction.php?client_id=${clientId}`)
                    .then(response => response.json())
                    .then(data => {
                        petSelect.innerHTML = '';
                        petSelect.disabled = false;
                        if (data.success && data.pets.length > 0) {
                            data.pets.forEach(pet => {
                                const option = document.createElement('option');
                                option.value = pet.id;
                                option.textContent = `${pet.pet_name} (${pet.breed_name})`;
                                petSelect.appendChild(option);
                            });
                        } else {
                            petSelect.innerHTML = '<option value="">No pets found for this client.</option>';
                            petSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching pets:', error);
                        petSelect.innerHTML = '<option value="">Error loading pets.</option>';
                        petSelect.disabled = true;
                    });

                setAppointmentModal.style.display = 'flex';
            });
        });

        // View Appointments Button Logic
        viewAppointmentsButtons.forEach(button => {
            button.addEventListener('click', () => {
                const clientId = button.dataset.clientId;
                const clientName = button.dataset.clientName;

                sidebarClientNameDisplay.textContent = clientName;
                appointmentsListDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Loading appointments...</p>';
                appointmentsSidebar.classList.add('open'); // Open sidebar

                // Fetch appointments for the selected client
                fetch(`../../app/function/admin/AppointmentListFunction.php?client_id=${clientId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.appointments.length > 0) {
                            appointmentsListDiv.innerHTML = ''; // Clear loading message
                            data.appointments.forEach(app => {
                                const appointmentDiv = document.createElement('div');
                                appointmentDiv.className = 'bg-white p-4 rounded-lg shadow-sm border border-gray-200';
                                appointmentDiv.innerHTML = `
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="text-md font-semibold text-gray-800">${app.pet_name} (${app.breed_name})</h4>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${app.status === 'confirmed' ? 'bg-green-100 text-green-800' : (app.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')}">
                                            ${app.status.charAt(0).toUpperCase() + app.status.slice(1)}
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-1">Date: <input type="date" value="${app.appointment_date}" data-id="${app.appointment_id}" data-field="appointment_date" class="appointment-field px-2 py-1 border rounded text-sm w-fit"></p>
                                    <p class="text-gray-600 text-sm mb-1">Time: <input type="time" value="${app.appointment_time.substring(0,5)}" data-id="${app.appointment_id}" data-field="appointment_time" class="appointment-field px-2 py-1 border rounded text-sm w-fit"></p>
                                    <p class="text-gray-600 text-sm mb-1">Type: ${app.appointment_type}</p>
                                    <p class="text-gray-600 text-sm mb-1">Notes: <textarea data-id="${app.appointment_id}" data-field="notes" class="appointment-field px-2 py-1 border rounded text-sm w-full h-16 resize-y">${app.notes || ''}</textarea></p>
                                    <div class="mt-3">
                                        <label for="status-${app.appointment_id}" class="block text-gray-700 text-sm font-medium mb-1">Status:</label>
                                        <select id="status-${app.appointment_id}" data-id="${app.appointment_id}" data-field="status" class="appointment-field px-2 py-1 border rounded text-sm bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="pending" ${app.status === 'pending' ? 'selected' : ''}>Pending</option>
                                            <option value="confirmed" ${app.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                            <option value="completed" ${app.status === 'completed' ? 'selected' : ''}>Completed</option>
                                            <option value="cancelled" ${app.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                        </select>
                                    </div>
                                    <button data-id="${app.appointment_id}" class="save-appointment-button mt-4 bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm transition duration-300">Save Changes</button>
                                `;
                                appointmentsListDiv.appendChild(appointmentDiv);
                            });

                            // Add event listeners to newly created save buttons and editable fields
                            appointmentsListDiv.querySelectorAll('.save-appointment-button').forEach(saveButton => {
                                saveButton.addEventListener('click', (event) => {
                                    const appointmentId = saveButton.dataset.id;
                                    const parentDiv = saveButton.closest('.bg-white');
                                    const fieldsToUpdate = {};

                                    parentDiv.querySelectorAll('.appointment-field').forEach(field => {
                                        fieldsToUpdate[field.dataset.field] = field.value;
                                    });

                                    // Send update via AJAX
                                    fetch('../../app/function/admin/AppointmentListFunction.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                        },
                                        body: new URLSearchParams({
                                            appointment_id: appointmentId,
                                            status: fieldsToUpdate.status,
                                            appointment_date: fieldsToUpdate.appointment_date,
                                            appointment_time: fieldsToUpdate.appointment_time,
                                            notes: fieldsToUpdate.notes,
                                        }),
                                    })
                                    .then(response => response.json())
                                    .then(updateData => {
                                        if (updateData.success) {
                                            // Optional: Update UI or show a toast message
                                            alert('Appointment updated successfully!'); // Replace with Toastr later if preferred
                                            // Re-fetch appointments to refresh the list after save
                                            button.click(); // Simulate click on the 'View List' button to refresh
                                        } else {
                                            alert('Error updating appointment: ' + updateData.message);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error saving appointment:', error);
                                        alert('An error occurred while saving.');
                                    });
                                });
                            });

                        } else {
                            appointmentsListDiv.innerHTML = '<p class="text-gray-500 text-center py-4">No appointments found for this client.</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching appointments:', error);
                        appointmentsListDiv.innerHTML = '<p class="text-red-500 text-center py-4">Error loading appointments.</p>';
                    });
            });
        });

        // Ensure the sidebar closes if clicked outside, but not if interacting with its content
        // This is handled by the generic window.addEventListener('click')
    });
</script>

<?php
require_once __DIR__ . '/../../resource/AFooter.php';
?>