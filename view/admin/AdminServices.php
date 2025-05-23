<?php
include('../includes/db_connect.php'); // Your DB connection

// Fetch services from DB
$query = "SELECT service_id, service_name, description, price FROM services ORDER BY service_name";
$result = mysqli_query($conn, $query);
?>

<?php include('../includes/header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Select Services</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="mb-4">Choose Veterinary Services</h2>

  <form action="ps.php" method="POST">
    <div class="list-group">
      <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($service = mysqli_fetch_assoc($result)): ?>
          <label class="list-group-item d-flex justify-content-between align-items-start">
            <div class="ms-2 me-auto">
              <div class="fw-bold"><?= htmlspecialchars($service['service_name']) ?></div>
              <small><?= htmlspecialchars($service['description']) ?></small>
            </div>
            <span class="badge bg-primary rounded-pill">₱<?= number_format($service['price'], 2) ?></span>
            <input class="form-check-input ms-3" type="checkbox" name="services[]" value="<?= $service['service_id'] ?>">
          </label>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-muted">No services available at the moment.</p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-success mt-4">Submit Selected Services</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include('../includes/footer.php'); ?>
