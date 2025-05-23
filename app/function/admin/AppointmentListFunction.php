<?php
// app/function/admin/AppointmentListFunction.php - Endpoint for fetching and managing appointments

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/config/Connection.php'; // Path from app/function/admin/ to app/config/
require_once __DIR__ . '/../../../app/config/Auth.php';       // Path from app/function/admin/ to app/config/

redirectBasedOnRole('admin'); // Only admins should access this endpoint

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Handle fetching appointments for a client
    $client_id = filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT);

    if (!$client_id) {
        $response['message'] = 'Invalid client ID.';
        echo json_encode($response);
        exit;
    }

    try {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT
                a.id AS appointment_id,
                a.appointment_date,
                a.appointment_time,
                a.appointment_type,
                a.status,
                a.notes,
                p.pet_name,
                b.breed_name
            FROM
                appointments a
            JOIN
                pets p ON a.pet_id = p.id
            JOIN
                pet_breeds b ON p.breed_id = b.id
            WHERE
                a.client_id = :client_id
            ORDER BY
                a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute(['client_id' => $client_id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['appointments'] = $appointments;
        echo json_encode($response);

    } catch (PDOException $e) {
        error_log("Fetch Appointments Error: " . $e->getMessage());
        $response['message'] = 'Database error fetching appointments.';
        echo json_encode($response);
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Handle updating an appointment
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$appointment_id) {
        $response['message'] = 'Invalid appointment ID.';
        echo json_encode($response);
        exit;
    }

    try {
        global $pdo;
        $sql = "UPDATE appointments SET ";
        $params = ['appointment_id' => $appointment_id];
        $updates = [];

        if ($status !== null) {
            $updates[] = "status = :status";
            $params['status'] = $status;
        }
        if ($appointment_date !== null) {
            $updates[] = "appointment_date = :appointment_date";
            $params['appointment_date'] = $appointment_date;
        }
        if ($appointment_time !== null) {
            $updates[] = "appointment_time = :appointment_time";
            $params['appointment_time'] = $appointment_time;
        }
        if ($notes !== null) {
            $updates[] = "notes = :notes";
            $params['notes'] = $notes;
        }

        if (empty($updates)) {
            $response['message'] = 'No valid fields provided for update.';
            echo json_encode($response);
            exit;
        }

        $sql .= implode(", ", $updates) . " WHERE id = :appointment_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $response['success'] = true;
        $response['message'] = 'Appointment updated successfully!';
        echo json_encode($response);

    } catch (PDOException $e) {
        error_log("Update Appointment Error: " . $e->getMessage());
        $response['message'] = 'Database error updating appointment.';
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        $response['message'] = 'An unexpected error occurred during update.';
        echo json_encode($response);
    }
} else {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>