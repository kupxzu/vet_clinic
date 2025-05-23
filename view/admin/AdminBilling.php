<?php
include('../includes/header.php');
include('db_connect.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $service_id = $_POST['service_id'];
    $amount = $_POST['amount'];
    $billing_date = $_POST['billing_date'];
    $paid_status = $_POST['paid_status'];

    // Simple validation could be added here

    $stmt = $conn->prepare("INSERT INTO billing (client_id, service_id, amount, billing_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidss", $client_id, $service_id, $amount, $billing_date, $paid_status);

    if ($stmt->execute()) {
        $message = "Billing record added successfully!";
    } else {
        $error = "Error adding billing record: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Billing Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Billing Form</h4>
      <a href="receipts.php" class="btn btn-light btn-sm">View Receipts</a>
    </div>
    <div class="card-body">

      <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="billing.php" method="POST">
        <div class="mb-3">
          <label for="client_id" class="form-label">Client ID</label>
          <input type="number" class="form-control" id="client_id" name="client_id" required>
        </div>
        <div class="mb-3">
          <label for="service_id" class="form-label">Service ID</label>
          <input type="number" class="form-control" id="service_id" name="service_id" required>
        </div>
        <div class="mb-3">
          <label for="amount" class="form-label">Amount</label>
          <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
        </div>
        <div class="mb-3">
          <label for="billing_date" class="form-label">Billing Date</label>
          <input type="date" class="form-control" id="billing_date" name="billing_date" required>
        </div>
        <div class="mb-3">
          <label for="paid_status" class="form-label">Paid Status</label>
          <select class="form-select" id="paid_status" name="paid_status" required>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">View</button>
      </form>

    </div>
  </div>
</div>

</body>
</html>

<?php include('footer.php'); ?>
