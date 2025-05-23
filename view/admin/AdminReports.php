
<?php include('../includes/header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generate Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white">
        <h4>Submit a Report</h4>
      </div>
      <div class="card-body">
       

        <form action="reports.php" method="POST">
          <div class="mb-3">
            <label for="report_type" class="form-label">Report Type</label>
            <select class="form-select" id="report_type" name="report_type" required>
              <option value="">-- Select Report Type --</option>
              <option value="billing">Billing Report</option>
              <option value="checkups">Checkup Report</option>
              <option value="services">Services Report</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" class="form-control" id="from_date" name="from_date">
          </div>

          <div class="mb-3">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" class="form-control" id="to_date" name="to_date">
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Report Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Write your observations or concerns here..." required></textarea>
          </div>

          <button type="submit" class="btn btn-dark">Submit Report</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
<?php include('db_connect.php'); ?>
<?php
$sql = "SELECT * FROM reports";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h3 class="mb-4">Report Records</h3>
    
    <?php if ($result && $result->num_rows > 0): ?>
      <table class="table table-bordered table-hover">
        <thead class="table-dark">
          <tr>
            <th>Report ID</th>
            <th>Client/Pet ID</th>
            <th>Description</th>
            <th>Report Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['report_id'] ?></td>
            <td><?= $row['client_id'] ?? $row['pet_id'] ?></td>
            <td><?= $row['description'] ?></td>
            <td><?= $row['report_date'] ?></td>
            <td><?= $row['status'] ?? 'Pending' ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-warning">No reports found.</div>
    <?php endif; ?>
  </div>
</body>
</html>

<?php include('footer.php'); ?>