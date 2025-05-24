<?php
// app/function/admin/ClientFunction.php - Handles AJAX requests for client data and appointments

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Path from app/function/admin/ to app/config/
require_once __DIR__ . '/../../../app/config/Connection.php'; // This should define $pdo globally
require_once __DIR__ . '/../../../app/config/Auth.php';

redirectBasedOnRole('admin'); // Only admins should access this endpoint

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
global $pdo; // Ensure $pdo from Connection.php is in scope

// Quick check if PDO is available
if (!$pdo) {
    error_log("ClientFunction.php: PDO object is not available from Connection.php");
    echo json_encode(['success' => false, 'error' => 'Database connection error.']);
    exit;
}


switch ($action) {
    case 'get_client_pets':
        handleGetClientPets($pdo);
        break;
    case 'get_client_appointments':
        handleGetClientAppointments($pdo);
        break;
    case 'update_appointment':
        handleUpdateAppointment($pdo);
        break;
    case 'update_pet_details': // Make sure this matches the action in AdminPet.php
        handleUpdatePetDetails($pdo);
        break;
    case 'get_visit_details':
    handleGetVisitDetails($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action specified.']);
        exit;
        
}
function handleGetVisitDetails($pdo_param) {
    $visit_id = filter_input(INPUT_GET, 'visit_id', FILTER_VALIDATE_INT);

    if (!$visit_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid visit ID.']);
        exit;
    }

    try {
        // Fetch main visit record
        $stmt_visit = $pdo_param->prepare("
            SELECT 
                vr.*, 
                u.fullname as client_name, 
                p.pet_name, p.pet_sex, p.pet_species, p.pet_age as current_pet_age, /* Current age */
                pb.breed_name,
                s.service_name, 
                s.service_fee as base_service_fee,
                orig_appt.appointment_date as original_appointment_date, /* Date of the appointment this visit is for */
                orig_appt.appointment_time as original_appointment_time
            FROM visit_records vr
            JOIN users u ON vr.client_id = u.id
            JOIN pets p ON vr.pet_id = p.id
            JOIN pet_breeds pb ON p.breed_id = pb.id
            LEFT JOIN services s ON vr.service_id = s.id
            JOIN appointments orig_appt ON vr.appointment_id = orig_appt.id
            WHERE vr.id = :visit_id
        ");
        $stmt_visit->execute(['visit_id' => $visit_id]);
        $visit_details = $stmt_visit->fetch(PDO::FETCH_ASSOC);

        if (!$visit_details) {
            echo json_encode(['success' => false, 'error' => 'Visit record not found.']);
            exit;
        }
        
        // Calculate age at time of visit (approximate)
        // This is a simplification. For exact age, you'd need pet's DOB.
        // Assuming pet_age in pets table is current age in years.
        // And visit_records.date_created is the visit date.
        $pet_current_age_years = $visit_details['current_pet_age'];
        $visit_date_obj = new DateTime($visit_details['date_created']);
        $current_date_obj = new DateTime();
        $interval_since_visit = $current_date_obj->diff($visit_date_obj);
        $years_since_visit = $interval_since_visit->y;
        
        $age_at_visit = $pet_current_age_years - $years_since_visit;
        if ($age_at_visit < 0) $age_at_visit = 0; // Or handle more gracefully
        $visit_details['pet_age_at_visit_approx'] = $age_at_visit;


        // Check if a next appointment was scheduled from THIS visit (this requires more complex linking or convention)
        // For simplicity, this part is illustrative. You might need a more direct link
        // if 'next_appointment_date/time/notes' were stored IN visit_records.
        // Since we create a new appointment, we can try to find a follow-up for this pet scheduled after this visit.
        $stmt_next = $pdo_param->prepare("
            SELECT appointment_date, appointment_time, notes 
            FROM appointments 
            WHERE client_id = :client_id AND pet_id = :pet_id AND appointment_type = 'Follow-up' AND date_created > :visit_creation_date
            ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1
        ");
        $stmt_next->execute([
            'client_id' => $visit_details['client_id'],
            'pet_id' => $visit_details['pet_id'],
            'visit_creation_date' => $visit_details['date_created']
        ]);
        $next_appt_from_visit = $stmt_next->fetch(PDO::FETCH_ASSOC);
        if($next_appt_from_visit){
            $visit_details['next_appointment_from_this_visit_date'] = $next_appt_from_visit['appointment_date'];
            $visit_details['next_appointment_from_this_visit_time'] = $next_appt_from_visit['appointment_time'];
            $visit_details['next_appointment_from_this_visit_notes'] = $next_appt_from_visit['notes'];
        }


        echo json_encode(['success' => true, 'visit' => $visit_details, 'pet_details' => [ /* Pass specific pet details for clarity if needed */
            'pet_sex' => $visit_details['pet_sex'],
            'pet_species' => $visit_details['pet_species'],
            'breed_name' => $visit_details['breed_name'],
            'pet_age_at_visit' => $age_at_visit // Using calculated age
        ]]);

    } catch (PDOException $e) {
        error_log("Get Visit Details Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error fetching visit details.']);
    }
}
function handleGetClientPets($pdo_param) { // Renamed to avoid confusion with global
    $client_id = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);

    if (!$client_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid client ID provided.']);
        exit;
    }

    try {
        $stmt = $pdo_param->prepare("SELECT p.id, p.pet_name, b.breed_name 
                               FROM pets p 
                               JOIN pet_breeds b ON p.breed_id = b.id 
                               WHERE p.client_id = :client_id 
                               ORDER BY p.pet_name ASC");
        $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        $stmt->execute();
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($pets === false) { // Check if fetchAll failed
             error_log("ClientFunction.php (get_client_pets): fetchAll returned false for client_id: " . $client_id);
             echo json_encode(['success' => false, 'error' => 'Failed to fetch pets.']);
        } else {
             echo json_encode(['success' => true, 'pets' => $pets]);
        }

    } catch (PDOException $e) {
        error_log("Get Client Pets Error (client_id: {$client_id}): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error fetching pets. Check server logs.']);
    }
}

function handleUpdatePetDetails($pdo_param) {
    $pet_id = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT);
    $pet_name = filter_input(INPUT_POST, 'pet_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pet_sex = filter_input(INPUT_POST, 'pet_sex', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pet_species = filter_input(INPUT_POST, 'pet_species', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $breed_id = filter_input(INPUT_POST, 'breed_id', FILTER_VALIDATE_INT);
    $pet_age = filter_input(INPUT_POST, 'pet_age', FILTER_VALIDATE_INT);

    $allowed_sex = ['female', 'male'];
    $allowed_species = ['dog', 'cat', 'others'];

    if (!$pet_id || empty($pet_name) || !in_array($pet_sex, $allowed_sex) || !in_array($pet_species, $allowed_species) || !$breed_id || $pet_age === false || $pet_age < 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid pet data provided for update.']);
        exit;
    }

    try {
        $stmt = $pdo_param->prepare("
            UPDATE pets 
            SET pet_name = :pet_name, 
                pet_sex = :pet_sex, 
                pet_species = :pet_species, 
                breed_id = :breed_id, 
                pet_age = :pet_age,
                date_updated = CURRENT_TIMESTAMP
            WHERE id = :pet_id
        ");
        $stmt->execute([
            'pet_name' => $pet_name,
            'pet_sex' => $pet_sex,
            'pet_species' => $pet_species,
            'breed_id' => $breed_id,
            'pet_age' => $pet_age,
            'pet_id' => $pet_id
        ]);
        
        if ($stmt->rowCount()) {
            echo json_encode(['success' => true, 'message' => 'Pet details updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'No changes made or pet not found.']);
        }
    } catch (PDOException $e) {
        error_log("Update Pet Details Error (pet_id: {$pet_id}): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error updating pet details.']);
    }
}


function handleGetClientAppointments($pdo_param) {
    $client_id = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);

    if (!$client_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid client ID.']);
        exit;
    }
    try {
        $stmt = $pdo_param->prepare("
            SELECT 
                a.id AS appointment_id,
                a.appointment_date,
                a.appointment_time,
                a.appointment_type,
                a.status,
                a.notes,
                p.pet_name,
                b.breed_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.id
            JOIN pet_breeds b ON p.breed_id = b.id
            WHERE a.client_id = :client_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute(['client_id' => $client_id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
         if ($appointments === false) {
            error_log("ClientFunction.php (get_client_appointments): fetchAll returned false for client_id: " . $client_id);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch appointments.']);
        } else {
            echo json_encode(['success' => true, 'appointments' => $appointments]);
        }
    } catch (PDOException $e) {
        error_log("Get Client Appointments Error (client_id: {$client_id}): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error fetching appointments.']);
    }
}

function handleUpdateAppointment($pdo_param) {
    // Ensure all necessary POST variables are received and validated
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    // Assuming client_id is also sent with the update request for verification
    $client_id_for_update = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT); 
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    $appointment_type = filter_input(INPUT_POST, 'appointment_type', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    if (!$appointment_id || !$client_id_for_update || empty($appointment_date) || empty($appointment_time) || empty($appointment_type) || empty($status)) {
        echo json_encode(['success' => false, 'error' => 'Missing required appointment data for update.']);
        exit;
    }

    try {
        $stmt = $pdo_param->prepare("
            UPDATE appointments 
            SET 
                appointment_date = :appointment_date, 
                appointment_time = :appointment_time, 
                appointment_type = :appointment_type, 
                status = :status, 
                notes = :notes,
                date_updated = CURRENT_TIMESTAMP
            WHERE id = :appointment_id AND client_id = :client_id_for_update 
        "); // Added client_id check for security
        $result = $stmt->execute([
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'appointment_type' => $appointment_type,
            'status' => $status,
            'notes' => $notes,
            'appointment_id' => $appointment_id,
            'client_id_for_update' => $client_id_for_update
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Appointment updated successfully.']);
        } else {
             echo json_encode(['success' => false, 'error' => 'No changes made or appointment not found/permission denied.']);
        }

    } catch (PDOException $e) {
        error_log("Update Appointment Error (appointment_id: {$appointment_id}): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error updating appointment.']);
    }
    
}


?>