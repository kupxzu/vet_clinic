<?php
require_once __DIR__ . '/../../resource/AHeader.php';
require_once __DIR__ . '/../../app/config/Connection.php';
?>

<?php
// PHP logic for fetching data and handling form submission
$message = ''; 
$message_type = '';
$today_date = date('Y-m-d');
global $pdo; 

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['record_visit_submit_button']) || isset($_POST['record_follow_up_submit_button']))) {
    
    $is_follow_up_submission = isset($_POST['record_follow_up_submit_button']);
    
    // Determine the correct appointment_id based on submission type
    if ($is_follow_up_submission) {
        $appointment_id = filter_input(INPUT_POST, 'submitted_appointment_id_for_followup', FILTER_VALIDATE_INT);
    } else { // It's a new visit submission
        $appointment_id = filter_input(INPUT_POST, 'appointment_id_new_visit', FILTER_VALIDATE_INT);
    }

    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $visit_notes = filter_input(INPUT_POST, 'visit_notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $additional_fees = filter_input(INPUT_POST, 'additional_fees', FILTER_VALIDATE_FLOAT);
    $discharged = filter_input(INPUT_POST, 'discharged', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $next_appointment_date_input = filter_input(INPUT_POST, 'next_appointment_date', FILTER_SANITIZE_STRING);
    $next_appointment_time_input = filter_input(INPUT_POST, 'next_appointment_time', FILTER_SANITIZE_STRING);
    $next_appointment_notes_input = filter_input(INPUT_POST, 'next_appointment_notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($additional_fees === false || $additional_fees < 0) $additional_fees = 0.00;
    $discharged_status = ($discharged === 'yes' || $discharged === 'no') ? $discharged : 'yes';

    if ($appointment_id && $service_id) { // This condition is key
        try {
            $pdo->beginTransaction();

            $stmt_appt = $pdo->prepare("SELECT client_id, pet_id FROM appointments WHERE id = :appointment_id");
            $stmt_appt->execute(['appointment_id' => $appointment_id]);
            $appointment_details = $stmt_appt->fetch(PDO::FETCH_ASSOC);

            if ($appointment_details) {
                $client_id = $appointment_details['client_id'];
                $pet_id = $appointment_details['pet_id'];

                $stmt_service_fee = $pdo->prepare("SELECT service_fee FROM services WHERE id = :service_id");
                $stmt_service_fee->execute(['service_id' => $service_id]);
                $service = $stmt_service_fee->fetch(PDO::FETCH_ASSOC);
                $service_fee = $service ? $service['service_fee'] : 0.00;
                
                $total_amount = $service_fee + $additional_fees;

                $stmt_insert_visit = $pdo->prepare("
                    INSERT INTO visit_records (appointment_id, client_id, pet_id, service_id, visit_notes, additional_fees, total_amount, discharged)
                    VALUES (:appointment_id, :client_id, :pet_id, :service_id, :visit_notes, :additional_fees, :total_amount, :discharged)
                ");
                $stmt_insert_visit->execute([
                    'appointment_id' => $appointment_id,
                    'client_id' => $client_id,
                    'pet_id' => $pet_id,
                    'service_id' => $service_id,
                    'visit_notes' => $visit_notes,
                    'additional_fees' => $additional_fees,
                    'total_amount' => $total_amount,
                    'discharged' => $discharged_status
                ]);
                
                $stmt_update_appt = $pdo->prepare("UPDATE appointments SET status = 'completed', notes = :visit_notes WHERE id = :appointment_id");
                $stmt_update_appt->execute([
                    'visit_notes' => "[Visit Recorded] " . $visit_notes,
                    'appointment_id' => $appointment_id
                ]);

                $message = "Visit record created successfully!";
                $message_type = 'success';

                if (!empty($next_appointment_date_input) && !empty($next_appointment_time_input)) {
                    // ... (Next appointment creation logic remains the same) ...
                    try {
                        $next_appt_datetime = new DateTime($next_appointment_date_input . ' ' . $next_appointment_time_input);
                        $next_appointment_date_val = $next_appt_datetime->format('Y-m-d');
                        $next_appointment_time_val = $next_appt_datetime->format('H:i:s');
                        $current_datetime_obj = new DateTime();

                        if ($next_appt_datetime >= $current_datetime_obj) {
                            $stmt_next_appt = $pdo->prepare("
                                INSERT INTO appointments (client_id, pet_id, appointment_date, appointment_time, appointment_type, status, notes)
                                VALUES (:client_id, :pet_id, :appointment_date, :appointment_time, :appointment_type, :status, :notes)
                            ");
                            $stmt_next_appt->execute([
                                'client_id' => $client_id,
                                'pet_id' => $pet_id,
                                'appointment_date' => $next_appointment_date_val,
                                'appointment_time' => $next_appointment_time_val,
                                'appointment_type' => 'Follow-up',
                                'status' => 'pending',
                                'notes' => $next_appointment_notes_input ?: 'Scheduled follow-up'
                            ]);
                            $message .= " Next follow-up appointment scheduled for " . $next_appointment_date_val . " at " . date('h:i A', strtotime($next_appointment_time_val)) . ".";
                        } else {
                             $message .= " Note: Next appointment date/time chosen is in the past and was not scheduled.";
                        }
                    } catch (Exception $e) {
                        $message .= " Could not schedule next appointment due to invalid date/time format.";
                        error_log("Next Appointment DateTime Error: " . $e->getMessage());
                    }
                }
                $pdo->commit();
            } else {
                $pdo->rollBack();
                $message = "Invalid appointment selected for the visit.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $message = "A visit record for this appointment already exists.";
            } else {
                error_log("Record Visit/Follow-up Error: " . $e->getMessage());
                $message = "Error recording visit: " . $e->getMessage();
            }
            $message_type = 'error';
        }
    } else if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['record_visit_submit_button']) || isset($_POST['record_follow_up_submit_button']))) {
        // This condition hits if $appointment_id or $service_id is empty/invalid
        $message = "Please select an appointment and a service for the visit record.";
        $message_type = 'error';
    }
}

// --- DATA FETCHING LOGIC (remains the same) ---
// Fetch today's appointments for "Record New Visit" Modal
$todays_appointments_for_new_visit = [];
try {
    $stmt_today_appts = $pdo->prepare("
        SELECT a.id as appointment_id, a.appointment_time, u.fullname as client_name, p.pet_name
        FROM appointments a
        JOIN users u ON a.client_id = u.id JOIN pets p ON a.pet_id = p.id
        LEFT JOIN visit_records vr ON a.id = vr.appointment_id
        WHERE a.appointment_date = :today_date AND (a.status = 'pending' OR a.status = 'confirmed') AND vr.id IS NULL
        ORDER BY a.appointment_time ASC
    ");
    $stmt_today_appts->execute(['today_date' => $today_date]);
    $todays_appointments_for_new_visit = $stmt_today_appts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log("Fetch Today's Appts Error: " . $e->getMessage()); }

// Fetch all pending/confirmed future appointments (including today) for "Process Follow-up" Modal
$scheduled_follow_ups = [];
try {
    $stmt_follow_ups = $pdo->prepare("
        SELECT a.id as appointment_id, a.appointment_date, a.appointment_time, u.fullname as client_name, p.pet_name, a.appointment_type
        FROM appointments a
        JOIN users u ON a.client_id = u.id JOIN pets p ON a.pet_id = p.id
        LEFT JOIN visit_records vr ON a.id = vr.appointment_id
        WHERE a.appointment_date >= :today_date AND (a.status = 'pending' OR a.status = 'confirmed') AND vr.id IS NULL
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $stmt_follow_ups->execute(['today_date' => $today_date]);
    $scheduled_follow_ups = $stmt_follow_ups->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log("Fetch Scheduled Follow-ups Error: " . $e->getMessage()); }


// Fetch all services for dropdowns
$all_services = [];
try {
    $stmt_all_services = $pdo->query("SELECT id, service_name, service_fee FROM services ORDER BY service_name ASC");
    $all_services = $stmt_all_services->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log("Fetch All Services Error: " . $e->getMessage()); }

// Fetch recent visit records to display
$recent_visits = [];
try {
    $stmt_visits = $pdo->prepare("
        SELECT vr.id as visit_id, vr.date_created as visit_date, u.fullname as client_name, p.pet_name, s.service_name, vr.total_amount, vr.discharged, vr.appointment_id
        FROM visit_records vr
        JOIN users u ON vr.client_id = u.id JOIN pets p ON vr.pet_id = p.id
        LEFT JOIN services s ON vr.service_id = s.id
        ORDER BY vr.date_created DESC LIMIT 10 
    ");
    $stmt_visits->execute();
    $recent_visits = $stmt_visits->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log("Fetch Recent Visits Error: " . $e->getMessage()); }

?>

<main class="container mx-auto my-12 p-8 bg-white rounded-xl shadow-2xl max-w-5xl border border-gray-200">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-4">
        <h2 class="text-4xl font-extrabold text-gray-900 leading-tight text-center sm:text-left">Pet Care & Visit Records</h2>
        <div class="flex flex-col sm:flex-row gap-4">
            <button id="trigger-record-visit-modal-button" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300 whitespace-nowrap">
                <i class="fas fa-notes-medical mr-2"></i> Record New Visit
            </button>
            <button id="trigger-process-followup-modal-button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300 whitespace-nowrap">
                <i class="fas fa-calendar-check mr-2"></i> Process Follow-up
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="p-4 mb-8 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?> shadow-md text-lg font-medium">
            <?php echo nl2br(htmlspecialchars($message)); ?>
        </div>
    <?php endif; ?>

    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Recent Visit Records</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Visit Date</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pet</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Service</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total (PHP)</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Discharged</th>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($recent_visits)): ?>
                    <tr>
                        <td colspan="7" class="py-4 px-6 text-center text-gray-500">No recent visit records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_visits as $visit): ?>
                        <tr>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo date('M d, Y H:i', strtotime($visit['visit_date'])); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-800 font-medium"><?php echo htmlspecialchars($visit['client_name']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-800"><?php echo htmlspecialchars($visit['pet_name']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($visit['service_name'] ?: 'N/A'); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600"><?php echo number_format($visit['total_amount'], 2); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-gray-600">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $visit['discharged'] === 'yes' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'; ?>">
                                    <?php echo ucfirst($visit['discharged']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <button class="view-visit-details-button bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm" data-visit-id="<?php echo $visit['visit_id']; ?>">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="select-followup-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-lg w-11/12 relative">
        <span class="close-button" id="close-select-followup-modal">&times;</span>
        <h2 class="text-3xl font-bold mb-8 text-gray-800 text-center">Select Scheduled Follow-up</h2>
        <div class="max-h-96 overflow-y-auto">
            <?php if (empty($scheduled_follow_ups)): ?>
                <p class="text-center text-gray-500 py-4">No pending or confirmed follow-up appointments found.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($scheduled_follow_ups as $appt): ?>
                        <li class="p-3 border rounded-md hover:bg-gray-50">
                            <button class="process-this-followup-button w-full text-left" 
                                    data-appointment-id="<?php echo htmlspecialchars($appt['appointment_id']); ?>"
                                    data-client-name="<?php echo htmlspecialchars($appt['client_name']); ?>"
                                    data-pet-name="<?php echo htmlspecialchars($appt['pet_name']); ?>">
                                <span class="font-semibold"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']))); ?></span><br>
                                Client: <?php echo htmlspecialchars($appt['client_name']); ?><br>
                                Pet: <?php echo htmlspecialchars($appt['pet_name']); ?> (Type: <?php echo htmlspecialchars($appt['appointment_type']);?>)
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="record-visit-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-xl w-11/12 relative overflow-y-auto" style="max-height: 90vh;">
        <span class="close-button" id="close-record-visit-modal">&times;</span>
        <h2 id="record-visit-modal-title" class="text-3xl font-bold mb-8 text-gray-800 text-center">Record Pet Visit</h2>
        
        <form action="" method="POST" class="space-y-6" id="record-visit-form">
            <input type="hidden" name="submitted_appointment_id_for_followup" id="submitted_appointment_id_for_followup_hidden_input">
            
            <div>
                <label for="appointment_id_new_visit" class="block text-gray-700 text-base font-semibold mb-2">Selected Appointment <span class="text-red-500">*</span></label>
                <select id="appointment_id_new_visit" name="appointment_id_new_visit" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Select an Appointment --</option>
                    </select>
                <div id="selected-appointment-details" class="mt-3 text-sm text-gray-600 bg-gray-50 p-2 rounded-md border">Select an appointment from above.</div>
            </div>

            <div>
                <label for="service_id" class="block text-gray-700 text-base font-semibold mb-2">Service Provided <span class="text-red-500">*</span></label>
                <select id="service_id" name="service_id" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Select a Service --</option>
                    <?php foreach ($all_services as $service): ?>
                        <option value="<?php echo htmlspecialchars($service['id']); ?>" data-fee="<?php echo htmlspecialchars($service['service_fee']); ?>">
                            <?php echo htmlspecialchars($service['service_name']) . " (PHP " . number_format($service['service_fee'], 2) . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="visit_notes" class="block text-gray-700 text-base font-semibold mb-2">Visit Notes / Diagnosis</label>
                <textarea id="visit_notes" name="visit_notes" rows="3" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div>
                <label for="additional_fees" class="block text-gray-700 text-base font-semibold mb-2">Additional Fees (PHP)</label>
                <input type="number" step="0.01" min="0" id="additional_fees" name="additional_fees" value="0.00" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="text-xl font-bold text-gray-800">
                Total Amount: PHP <span id="total_amount_display">0.00</span>
            </div>

            <hr class="my-4">
            <h4 class="text-lg font-semibold text-gray-700 mb-2">Post-Visit Information</h4>

            <div>
                <label class="block text-gray-700 text-base font-semibold mb-2">Discharged? <span class="text-red-500">*</span></label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="discharged" value="yes" class="form-radio h-5 w-5 text-blue-600 discharged-radio" checked>
                        <span class="ml-2 text-gray-700">Yes</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="discharged" value="no" class="form-radio h-5 w-5 text-blue-600 discharged-radio">
                        <span class="ml-2 text-gray-700">No</span>
                    </label>
                </div>
            </div>
            
            <div id="next-appointment-section" class="space-y-4 mt-4 border-t pt-4 border-gray-200" style="display: none;"> <p class="text-md font-semibold text-gray-700">Schedule Next Appointment <span id="next-appointment-optional-text" class="text-gray-500">(Optional)</span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="next_appointment_date" class="block text-gray-700 text-sm font-medium mb-1">Next Appointment Date</label>
                        <input type="date" id="next_appointment_date" name="next_appointment_date" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label for="next_appointment_time" class="block text-gray-700 text-sm font-medium mb-1">Next Appointment Time</label>
                        <input type="time" id="next_appointment_time" name="next_appointment_time" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                 <div>
                    <label for="next_appointment_notes" class="block text-gray-700 text-sm font-medium mb-1">Next Appointment Notes</label>
                    <textarea id="next_appointment_notes" name="next_appointment_notes" rows="2" class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <button type="submit" name="record_visit_submit_button" id="save-visit-button" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg">
                <i class="fas fa-save mr-2"></i> Save Visit Record
            </button>
        </form>
    </div>
</div>


<div id="view-visit-details-modal" class="modal">
    <div class="modal-content bg-white p-8 rounded-xl shadow-2xl max-w-2xl w-11/12 relative overflow-y-auto" style="max-height: 90vh;">
        <span class="close-button" id="close-view-visit-details-modal">&times;</span>
        <h2 class="text-3xl font-bold mb-6 text-gray-800 text-center">Visit Details</h2>
        <div id="visit-details-content" class="text-gray-700 space-y-3">
            <p class="text-center">Loading details...</p>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Common Modal Elements & Functions ---
    const recordVisitModal = document.getElementById('record-visit-modal');
    const closeRecordVisitModalButton = document.getElementById('close-record-visit-modal');
    // Use a more specific ID for the 'appointment_id' select in the "Record New Visit" flow
    const appointmentSelectForNewVisit = document.getElementById('appointment_id_new_visit'); 
    const submittedAppointmentIdHiddenInput = document.getElementById('submitted_appointment_id_for_followup_hidden_input');

    const serviceSelectInModal = document.getElementById('service_id');
    const additionalFeesInputInModal = document.getElementById('additional_fees');
    const totalAmountDisplayInModal = document.getElementById('total_amount_display');
    const selectedApptDetailsDivInModal = document.getElementById('selected-appointment-details');
    const dischargedRadiosInModal = recordVisitModal.querySelectorAll('.discharged-radio');
    const nextApptDateInputInModal = document.getElementById('next_appointment_date');
    const nextApptTimeInputInModal = document.getElementById('next_appointment_time');
    const nextApptNotesInputInModal = document.getElementById('next_appointment_notes');
    const nextApptOptionalTextInModal = document.getElementById('next-appointment-optional-text');
    const nextAppointmentSectionInModal = document.getElementById('next-appointment-section');
    const recordVisitModalTitle = document.getElementById('record-visit-modal-title');
    const recordVisitForm = document.getElementById('record-visit-form');
    const saveVisitButton = document.getElementById('save-visit-button');
    // const recordVisitTypeInput = document.getElementById('record_visit_type_input'); // Not strictly needed if using button names

    const selectFollowupModal = document.getElementById('select-followup-modal');
    const triggerProcessFollowupButton = document.getElementById('trigger-process-followup-modal-button');
    const closeSelectFollowupModalButton = document.getElementById('close-select-followup-modal');

    const viewVisitDetailsModal = document.getElementById('view-visit-details-modal');
    const closeViewVisitDetailsModalButton = document.getElementById('close-view-visit-details-modal');
    const visitDetailsContent = document.getElementById('visit-details-content');

    const allServicesData = <?php echo json_encode($all_services); ?>;

    function updateTotalAmount() {
        if (!serviceSelectInModal || !additionalFeesInputInModal || !totalAmountDisplayInModal) return;
        const selectedServiceOption = serviceSelectInModal.options[serviceSelectInModal.selectedIndex];
        const serviceFee = selectedServiceOption && selectedServiceOption.value !== "" ? parseFloat(selectedServiceOption.dataset.fee) : 0;
        const additionalFees = parseFloat(additionalFeesInputInModal.value) || 0;
        const total = serviceFee + additionalFees;
        totalAmountDisplayInModal.textContent = total.toFixed(2);
    }

    function toggleNextAppointmentSection(show) {
        if (!nextAppointmentSectionInModal || !nextApptDateInputInModal || !nextApptTimeInputInModal || !nextApptNotesInputInModal || !nextApptOptionalTextInModal) return;
        
        if (show) {
            nextAppointmentSectionInModal.style.display = 'block';
            nextApptDateInputInModal.required = true;
            nextApptTimeInputInModal.required = true;
            nextApptOptionalTextInModal.textContent = "(Required for Follow-up)";
            nextApptOptionalTextInModal.classList.add('text-red-500');
            nextApptOptionalTextInModal.classList.remove('text-gray-500');
        } else {
            nextAppointmentSectionInModal.style.display = 'none';
            nextApptDateInputInModal.required = false;
            nextApptTimeInputInModal.required = false;
            nextApptDateInputInModal.value = '';
            nextApptTimeInputInModal.value = '';
            nextApptNotesInputInModal.value = '';
            nextApptOptionalTextInModal.textContent = "(Optional)";
            nextApptOptionalTextInModal.classList.remove('text-red-500');
            nextApptOptionalTextInModal.classList.add('text-gray-500');
        }
    }

    // --- MODAL A: Select Follow-up Appointment ---
    if (triggerProcessFollowupButton && selectFollowupModal) {
        triggerProcessFollowupButton.addEventListener('click', () => selectFollowupModal.style.display = 'flex');
    }
    if (closeSelectFollowupModalButton && selectFollowupModal) {
        closeSelectFollowupModalButton.addEventListener('click', () => selectFollowupModal.style.display = 'none');
    }
    document.querySelectorAll('.process-this-followup-button').forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.dataset.appointmentId;
            const clientName = this.dataset.clientName;
            const petName = this.dataset.petName;

            recordVisitModalTitle.textContent = 'Record Follow-up Visit';
            saveVisitButton.name = 'record_follow_up_submit_button'; // Distinguish submission
            
            // Set the hidden input for appointment_id for follow-ups
            if(submittedAppointmentIdHiddenInput) submittedAppointmentIdHiddenInput.value = appointmentId;
            
            // For display, you can update the select or the details div
            if(appointmentSelectForNewVisit) {
                appointmentSelectForNewVisit.innerHTML = `<option value="${appointmentId}" selected>Selected: ${clientName} - ${petName}</option>`;
                appointmentSelectForNewVisit.disabled = true; // Disable as it's pre-selected
            }
            if(selectedApptDetailsDivInModal) selectedApptDetailsDivInModal.innerHTML = `Processing Follow-up for: <strong>${clientName}</strong> with <strong>${petName}</strong> (Appointment ID: ${appointmentId})`;
            
            if(recordVisitForm) recordVisitForm.reset(); 
            // After reset, re-ensure the hidden ID is set if the form elements get cleared
            if(submittedAppointmentIdHiddenInput) submittedAppointmentIdHiddenInput.value = appointmentId;
            // And re-ensure the select displays the correct item if it was part of the reset
             if(appointmentSelectForNewVisit) {
                appointmentSelectForNewVisit.innerHTML = `<option value="${appointmentId}" selected>Selected: ${clientName} - ${petName}</option>`;
             }


            if(totalAmountDisplayInModal) totalAmountDisplayInModal.textContent = '0.00';
            
            const dischargedNoRadio = recordVisitModal.querySelector('.discharged-radio[value="no"]');
            if (dischargedNoRadio) dischargedNoRadio.checked = true; // Default to "No" for follow-ups
            toggleNextAppointmentSection(true); // Show and require next appt for follow-up

            if(selectFollowupModal) selectFollowupModal.style.display = 'none';
            if(recordVisitModal) recordVisitModal.style.display = 'flex';
        });
    });

    // --- MODAL B: Record New Visit ---
    const triggerRecordVisitModalButton = document.getElementById('trigger-record-visit-modal-button');
    if (triggerRecordVisitModalButton && recordVisitModal) {
        triggerRecordVisitModalButton.addEventListener('click', () => {
            recordVisitModalTitle.textContent = 'Record New Visit';
            saveVisitButton.name = 'record_visit_submit_button'; // Distinguish submission
            if(submittedAppointmentIdHiddenInput) submittedAppointmentIdHiddenInput.value = ''; // Clear hidden ID for new visits

            if(appointmentSelectForNewVisit) {
                appointmentSelectForNewVisit.disabled = false;
                appointmentSelectForNewVisit.innerHTML = '<option value="">-- Select an Appointment --</option>';
                <?php if (empty($todays_appointments_for_new_visit)): ?>
                    appointmentSelectForNewVisit.innerHTML += '<option value="" disabled>No eligible appointments for today.</option>';
                <?php else: ?>
                    <?php foreach ($todays_appointments_for_new_visit as $appt): ?>
                        appointmentSelectForNewVisit.innerHTML += `<option value="<?php echo htmlspecialchars($appt['appointment_id']); ?>" data-client-name="<?php echo htmlspecialchars($appt['client_name']); ?>" data-pet-name="<?php echo htmlspecialchars($appt['pet_name']); ?>"><?php echo htmlspecialchars(date('h:i A', strtotime($appt['appointment_time']))) . " - " . htmlspecialchars($appt['client_name']) . " - " . htmlspecialchars($appt['pet_name']); ?></option>`;
                    <?php endforeach; ?>
                <?php endif; ?>
            }
            
            if(recordVisitForm) recordVisitForm.reset();
            if(totalAmountDisplayInModal) totalAmountDisplayInModal.textContent = '0.00';
            if(selectedApptDetailsDivInModal) selectedApptDetailsDivInModal.innerHTML = 'Select an appointment from above.';
            
            const dischargedYesRadio = recordVisitModal.querySelector('.discharged-radio[value="yes"]');
            if (dischargedYesRadio) dischargedYesRadio.checked = true; // Default to Yes for new visits
            toggleNextAppointmentSection(false); 

            recordVisitModal.style.display = 'flex';
        });
    }
    if (closeRecordVisitModalButton && recordVisitModal) {
        closeRecordVisitModalButton.addEventListener('click', () => {
            recordVisitModal.style.display = 'none';
        });
    }

    if (appointmentSelectForNewVisit) { // Changed to appointmentSelectForNewVisit
        appointmentSelectForNewVisit.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value !== "") {
                selectedApptDetailsDivInModal.innerHTML = `Selected Visit: <strong>${selectedOption.dataset.clientName}</strong> with <strong>${selectedOption.dataset.petName}</strong>`;
            } else {
                selectedApptDetailsDivInModal.innerHTML = 'Select an appointment from above.';
            }
        });
    }
    if (serviceSelectInModal) serviceSelectInModal.addEventListener('change', updateTotalAmount);
    if (additionalFeesInputInModal) additionalFeesInputInModal.addEventListener('input', updateTotalAmount);
    
    if (dischargedRadiosInModal) {
        dischargedRadiosInModal.forEach(radio => {
            radio.addEventListener('change', function() {
                toggleNextAppointmentSection(this.value === 'no');
            });
        });
        const initiallyCheckedDischarged = recordVisitModal.querySelector('.discharged-radio:checked');
        if(initiallyCheckedDischarged) {
            toggleNextAppointmentSection(initiallyCheckedDischarged.value === 'no');
        } else {
            toggleNextAppointmentSection(false); // Default if nothing is checked (e.g. after reset)
        }
    }
    
    // Logic for date/time dependency if next appointment section is visible
    if (nextApptDateInputInModal && nextApptTimeInputInModal && nextAppointmentSectionInModal) {
        const updateDateTimeRequiredStatus = () => {
            if (nextAppointmentSectionInModal.style.display === 'block') { // Only apply if section is visible
                if (nextApptTimeInputInModal.value !== "") {
                    nextApptDateInputInModal.required = true;
                } else if (nextApptDateInputInModal.value === "" && document.querySelector('.discharged-radio[value="yes"]:checked')) {
                    // If discharged=yes and time is empty, date is not strictly required unless time is re-added
                    nextApptDateInputInModal.required = false;
                }
                // If discharged=no, both are already set to required by toggleNextAppointmentSection
            }
        };
        nextApptDateInputInModal.addEventListener('change', updateDateTimeRequiredStatus);
        nextApptTimeInputInModal.addEventListener('change', updateDateTimeRequiredStatus);
    }
    updateTotalAmount(); // Initial call

    // --- MODAL C: View Visit Details (JS remains the same as previous correct version) ---
    document.querySelectorAll('.view-visit-details-button').forEach(button => { /* ... */ });
    if (closeViewVisitDetailsModalButton) { /* ... */ }
    // htmlspecialchars, nl2br, ucfirst JS helper functions should be here or in AFooter.php

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    function nl2br(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/(?:\r\n|\r|\n)/g, '<br>');
    }
    function ucfirst(str) {
        if (typeof str !== 'string' || str.length === 0) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Global click listener for closing modals
    window.addEventListener('click', (event) => {
        if (recordVisitModal && event.target == recordVisitModal) recordVisitModal.style.display = 'none';
        if (selectFollowupModal && event.target == selectFollowupModal) selectFollowupModal.style.display = 'none';
        if (viewVisitDetailsModal && event.target == viewVisitDetailsModal) viewVisitDetailsModal.style.display = 'none';
    });
});
</script>

<?php
require_once __DIR__ . '/../../resource/AFooter.php';
?>