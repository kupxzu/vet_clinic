<?php include("db_connect.php"); ?>
<?php include('../includes/header.php'); ?>

<?php

$sql = "SELECT * FROM checkups";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Checkups</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h3 class="mb-4">Pet Checkup Records</h3>
    <?php if ($result && $result->num_rows > 0): ?>
      <table class="table table-bordered table-striped">
        <thead class="table-success">
          <tr>
            <th>Checkup ID</th>
            <th>Pet ID</th>
            <th>Checkup Date</th>
            <th>Symptoms</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Next Visit</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['checkup_id'] ?></td>
            <td><?= $row['pet_id'] ?></td>
            <td><?= $row['checkup_date'] ?></td>
            <td><?= $row['symptoms'] ?></td>
            <td><?= $row['diagnos'] ?></td>
            <td><?= $row['traetment'] ?></td>
            <td><?= $row['next_visit'] ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-warning">No checkup data found.</div>
    <?php endif; ?>
  </div>
</body>
</html>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkup Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="card shadow-sm">
      <div class="card-header bg-success text-white">
        <h4>Checkup Now!</h4>
      </div>
      <div class="card-body">
        <form action="checkups.php" method="POST">
          <div class="mb-3">
            <label for="pet_id" class="form-label">Pet ID</label>
            <input type="number" class="form-control" id="pet_id" name="pet_id" required>
          </div>
          <div class="mb-3">
            <label for="checkup_date" class="form-label">Checkup Date</label>
            <input type="date" class="form-control" id="checkup_date" name="checkup_date" required>
          </div>
          <div class="mb-3">
            <label for="symptoms" class="form-label">Symptoms</label>
            <textarea class="form-control" id="symptoms" name="symptoms" rows="2" required></textarea>
          </div>
          <div class="mb-3">
            <label for="diagnosis" class="form-label">Diagnosis</label>
            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="2" required></textarea>
          </div>
          <div class="mb-3">
            <label for="treatment" class="form-label">Treatment</label>
            <textarea class="form-control" id="treatment" name="treatment" rows="2" required></textarea>
          </div>
          <div class="mb-3">
            <label for="next_visit" class="form-label">Next Visit Date</label>
            <input type="date" class="form-control" id="next_visit" name="next_visit" required>
          </div>
          <button type="submit" class="btn btn-primary">Submit Checkup</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
<?php include('footer.php'); ?>
