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

// Get search query
$search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

$offset = ($current_page - 1) * $clients_per_page;

$message = '';
$message_type = '';

// Handle Appointment form submission
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

// Build WHERE clause for search
$search_where_clause = '';
$search_params = [];
if (!empty($search_query)) {
    $search_where_clause = " AND (fullname LIKE :search_fullname OR email LIKE :search_email)";
    $search_params[':search_fullname'] = '%' . $search_query . '%';
    $search_params[':search_email'] = '%' . $search_query . '%';
}

// Fetch total number of clients (with search filter)
$total_clients = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM users WHERE role = 'client'" . $search_where_clause);
    $stmt->execute($search_params);
    $total_clients = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Count Clients Error: " . $e->getMessage());
    // $message = "Error fetching client count."; // Avoid overwriting other messages
    // $message_type = 'error';
}

$total_pages = $total_clients > 0 ? ceil($total_clients / $clients_per_page) : 1;


// Fetch clients for the current page (with search filter)
$clients = [];
try {
    $stmt = $pdo->prepare("SELECT id, fullname, email, date_created FROM users WHERE role = 'client'" . $search_where_clause . " ORDER BY fullname ASC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $clients_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    foreach ($search_params as $key => &$val) { 
        $stmt->bindParam($key, $val);
    }
    unset($val); // Unset reference
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch Clients List Error: " . $e->getMessage());
    // $message = "Error fetching clients list."; // Avoid overwriting other messages
    // $message_type = 'error';
}

// Helper to construct pagination links with search query
function getPaginationLink($page, $search_query) {
    $link = "?page=" . $page;
    if (!empty($search_query)) {
        $link .= "&search=" . urlencode($search_query);
    }
    return $link;
}
?>

<main class="container mx-auto my-12 p-8 bg-white rounded-xl shadow-2xl max-w-4xl border border-gray-200">
    <h2 class="text-4xl font-extrabold text-gray-900 mb-10 text-center leading-tight">Manage Clients & Appointments</h2>

    <?php if ($message): ?>
        <div class="p-4 mb-8 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?> shadow-md text-lg font-medium">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="mb-8">
        <form method="GET" action="" class="flex items-center space-x-4">
            <input
                type="text"
                name="search"
                placeholder="Search clients by name or email..."
                value="<?php echo htmlspecialchars($search_query); ?>"
                class="flex-grow px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
            >
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-lg shadow-md transition duration-300">
                <i class="fas fa-search mr-2"></i> Search
            </button>
            <?php if (!empty($search_query)): ?>
                <a href="AdminClientList.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-5 rounded-lg shadow-md transition duration-300">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

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
                            <td class="py-4 px-6 whitespace-nowrap flex space-x-2">
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
            <a href="<?php echo getPaginationLink($current_page - 1, $search_query); ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition duration-300">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo getPaginationLink($i, $search_query); ?>" class="px-4 py-2 rounded-md <?php echo ($i == $current_page) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition duration-300">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo getPaginationLink($current_page + 1, $search_query); ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition duration-300">Next</a>
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

<div id="appointments-side-panel" class="fixed top-0 right-0 h-full bg-white shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out w-96 z-[1000]">
    <div class="p-6 relative h-full flex flex-col">
        <button id="close-side-panel" class="absolute top-4 left-4 text-gray-500 hover:text-gray-800 text-3xl">&times;</button>
        <h3 class="text-3xl font-bold text-gray-900 mb-6 text-center pt-4">Appointments for <span id="panel_client_name"></span></h3>
        <div id="appointments-list-container" class="flex-grow overflow-y-auto space-y-4 pr-2">
            <p class="text-center text-gray-500">Loading appointments...</p>
        </div>
        <div class="mt-6 text-center">
            <button id="refresh-appointments" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition duration-300 text-sm">
                <i class="fas fa-sync-alt mr-2"></i> Refresh
            </button>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const setAppointmentButtons = document.querySelectorAll('.set-appointment-button');
        const setAppointmentModal = document.getElementById('set-appointment-modal');
        const closeAppointmentModalButton = setAppointmentModal ? setAppointmentModal.querySelector('.close-button') : null;
        const modalClientIdInput = document.getElementById('modal_client_id');
        const modalClientNameDisplay = document.getElementById('modal_client_name');
        const petSelect = document.getElementById('pet_id');

        const viewAppointmentsButtons = document.querySelectorAll('.view-appointments-button');
        const appointmentsSidePanel = document.getElementById('appointments-side-panel');
        const closeSidePanelButton = appointmentsSidePanel ? appointmentsSidePanel.querySelector('#close-side-panel') : null;
        const panelClientNameDisplay = document.getElementById('panel_client_name');
        const appointmentsListContainer = document.getElementById('appointments-list-container');
        const refreshAppointmentsButton = document.getElementById('refresh-appointments');

        // Ensure modals/panels are hidden on page load
        if (setAppointmentModal) {
            setAppointmentModal.style.display = 'none';
        }
        if (appointmentsSidePanel) {
            appointmentsSidePanel.style.transform = 'translate-x-full';
        }


        // MODAL FUNCTIONS (Set Appointment)
        const closeModal = () => {
            if (setAppointmentModal) setAppointmentModal.style.display = 'none';
        };

        if (closeAppointmentModalButton) {
            closeAppointmentModalButton.addEventListener('click', closeModal);
        }
        window.addEventListener('click', (event) => {
            if (setAppointmentModal && event.target == setAppointmentModal) {
                closeModal();
            }
        });

        setAppointmentButtons.forEach(button => {
            button.addEventListener('click', () => {
                const clientId = button.dataset.clientId;
                const clientName = button.dataset.clientName;

                if(modalClientIdInput) modalClientIdInput.value = clientId;
                if(modalClientNameDisplay) modalClientNameDisplay.textContent = clientName;

                if(petSelect) {
                    petSelect.innerHTML = '<option value="">Loading pets...</option>';
                    petSelect.disabled = true;
                } else {
                    console.error("Pet select dropdown not found in 'Set Appointment' modal.");
                    return;
                }

                // Fetch pets for the selected client via AJAX
                fetch(`../../app/function/admin/ClientFunction.php?action=get_client_pets&client_id=${clientId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        petSelect.innerHTML = ''; // Clear loading state
                        petSelect.disabled = false;
                        if (data.success && data.pets && data.pets.length > 0) {
                            data.pets.forEach(pet => {
                                const option = document.createElement('option');
                                option.value = pet.id;
                                option.textContent = `${pet.pet_name} (${pet.breed_name})`;
                                petSelect.appendChild(option);
                            });
                        } else if (data.success && data.pets && data.pets.length === 0) {
                            petSelect.innerHTML = '<option value="">No pets found for this client.</option>';
                            petSelect.disabled = true;
                        } else {
                            console.error('Error in fetched pet data:', data.error || 'Unknown error');
                            petSelect.innerHTML = `<option value="">Error: ${data.error || 'Could not load pets.'}</option>`;
                            petSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching pets:', error);
                        petSelect.innerHTML = '<option value="">Network error loading pets.</option>';
                        petSelect.disabled = true;
                    });

                if (setAppointmentModal) setAppointmentModal.style.display = 'flex';
            });
        });


        // SIDE PANEL FUNCTIONS (View Appointments)
        const toggleSidePanel = (clientId = null, clientName = null) => {
            if (!appointmentsSidePanel) return;
            if (appointmentsSidePanel.style.transform === 'translate-x-full' || appointmentsSidePanel.style.transform === '') {
                // Panel is closed, open it
                appointmentsSidePanel.style.transform = 'translate-x-0';
                if (clientId && clientName) {
                    if(panelClientNameDisplay) panelClientNameDisplay.textContent = clientName;
                    if(panelClientNameDisplay) panelClientNameDisplay.dataset.clientId = clientId; 
                    loadClientAppointments(clientId);
                }
            } else {
                // Panel is open, close it
                appointmentsSidePanel.style.transform = 'translate-x-full';
            }
        };

        const loadClientAppointments = (clientId) => {
            if (!appointmentsListContainer) return;
            appointmentsListContainer.innerHTML = '<p class="text-center text-gray-500 mt-4">Loading appointments...</p>'; 

            fetch(`../../app/function/admin/ClientFunction.php?action=get_client_appointments&client_id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    appointmentsListContainer.innerHTML = ''; 
                    if (data.success && data.appointments && data.appointments.length > 0) {
                        data.appointments.forEach(appt => {
                            const apptElement = document.createElement('div');
                            apptElement.classList.add('bg-gray-50', 'p-4', 'rounded-lg', 'shadow-sm', 'border', 'border-gray-200');
                            apptElement.innerHTML = `
                                <h4 class="font-bold text-gray-800 text-lg mb-2">Appointment with ${appt.pet_name} (${appt.breed_name})</h4>
                                <div class="grid grid-cols-2 gap-y-2 text-sm text-gray-700">
                                    <div><strong>Date:</strong></div><div><input type="date" class="form-input w-full p-1 border rounded" value="${appt.appointment_date}" data-appt-field="appointment_date" data-appt-id="${appt.appointment_id}"></div>
                                    <div><strong>Time:</strong></div><div><input type="time" class="form-input w-full p-1 border rounded" value="${appt.appointment_time.substring(0, 5)}" data-appt-field="appointment_time" data-appt-id="${appt.appointment_id}"></div>
                                    <div><strong>Type:</strong></div>
                                    <div>
                                        <select class="form-select w-full p-1 border rounded" data-appt-field="appointment_type" data-appt-id="${appt.appointment_id}">
                                            <option value="Check-up" ${appt.appointment_type === 'Check-up' ? 'selected' : ''}>Check-up</option>
                                            <option value="Vaccination" ${appt.appointment_type === 'Vaccination' ? 'selected' : ''}>Vaccination</option>
                                            <option value="Surgery" ${appt.appointment_type === 'Surgery' ? 'selected' : ''}>Surgery</option>
                                            <option value="Other" ${appt.appointment_type === 'Other' ? 'selected' : ''}>Other</option>
                                        </select>
                                    </div>
                                    <div><strong>Status:</strong></div>
                                    <div>
                                        <select class="form-select w-full p-1 border rounded" data-appt-field="status" data-appt-id="${appt.appointment_id}">
                                            <option value="pending" ${appt.status === 'pending' ? 'selected' : ''}>Pending</option>
                                            <option value="confirmed" ${appt.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                            <option value="completed" ${appt.status === 'completed' ? 'selected' : ''}>Completed</option>
                                            <option value="cancelled" ${appt.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                        </select>
                                    </div>
                                    <div><strong>Notes:</strong></div><div><textarea class="form-textarea w-full p-1 border rounded" rows="2" data-appt-field="notes" data-appt-id="${appt.appointment_id}">${appt.notes || ''}</textarea></div>
                                </div>
                                <button class="save-appointment-button mt-4 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-sm w-full transition duration-300" data-appt-id="${appt.appointment_id}">Save Changes</button>
                            `;
                            appointmentsListContainer.appendChild(apptElement);
                        });
                        
                        appointmentsListContainer.querySelectorAll('.save-appointment-button').forEach(saveButton => {
                            saveButton.addEventListener('click', (e) => {
                                const apptId = e.target.dataset.apptId;
                                saveAppointmentChanges(apptId, clientId); // Pass clientId
                            });
                        });

                    } else if (data.success && data.appointments && data.appointments.length === 0) {
                        appointmentsListContainer.innerHTML = '<p class="text-center text-gray-500">No appointments found for this client.</p>';
                    } else {
                         appointmentsListContainer.innerHTML = `<p class="text-center text-red-500">Error: ${data.error || 'Could not load appointments.'}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching appointments:', error);
                    appointmentsListContainer.innerHTML = '<p class="text-center text-red-500">Failed to load appointments.</p>';
                });
        };

        const saveAppointmentChanges = (appointmentId, clientId) => { // Accept clientId
            const appointmentData = {
                action: 'update_appointment',
                appointment_id: appointmentId,
                client_id: clientId, // Include client_id in the payload
                appointment_date: appointmentsListContainer.querySelector(`[data-appt-id="${appointmentId}"][data-appt-field="appointment_date"]`).value,
                appointment_time: appointmentsListContainer.querySelector(`[data-appt-id="${appointmentId}"][data-appt-field="appointment_time"]`).value,
                appointment_type: appointmentsListContainer.querySelector(`[data-appt-id="${appointmentId}"][data-appt-field="appointment_type"]`).value,
                status: appointmentsListContainer.querySelector(`[data-appt-id="${appointmentId}"][data-appt-field="status"]`).value,
                notes: appointmentsListContainer.querySelector(`[data-appt-id="${appointmentId}"][data-appt-field="notes"]`).value
            };

            fetch('../../app/function/admin/ClientFunction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' 
                },
                body: JSON.stringify(appointmentData) 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment updated successfully!'); 
                    loadClientAppointments(clientId); 
                } else {
                    alert('Error updating appointment: ' + data.error); 
                }
            })
            .catch(error => {
                console.error('Error saving appointment:', error);
                alert('An error occurred while saving.'); 
            });
        };


        viewAppointmentsButtons.forEach(button => {
            button.addEventListener('click', () => {
                const clientId = button.dataset.clientId;
                const clientName = button.dataset.clientName;
                toggleSidePanel(clientId, clientName);
            });
        });

        if (closeSidePanelButton) {
            closeSidePanelButton.addEventListener('click', () => toggleSidePanel());
        }

        if (refreshAppointmentsButton) {
            refreshAppointmentsButton.addEventListener('click', () => {
                const currentClientId = panelClientNameDisplay ? panelClientNameDisplay.dataset.clientId : null; 
                if (currentClientId) {
                    loadClientAppointments(currentClientId);
                }
            });
        }
    });
</script>

<?php
require_once __DIR__ . '/../../resource/AFooter.php';
?>