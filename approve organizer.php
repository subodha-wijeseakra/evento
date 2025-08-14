<?php
include 'db.php';

$filter_status = $_GET['status'] ?? 'pending';
$search_email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgId = $_POST['org_id'];
    $action = $_POST['action'];

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE organizer SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $action, $orgId);
            $stmt->execute();
            $stmt->close();

            // Get email from organizer
            $emailStmt = $conn->prepare("SELECT email FROM organizer WHERE id = ?");
            $emailStmt->bind_param("i", $orgId);
            $emailStmt->execute();
            $emailResult = $emailStmt->get_result();
            $emailRow = $emailResult->fetch_assoc();
            $emailStmt->close();

            if ($emailRow) {
                $organizerEmail = $emailRow['email'];

                // Find user_id by email
                $userStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $userStmt->bind_param("s", $organizerEmail);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userRow = $userResult->fetch_assoc();
                $userStmt->close();

                if ($userRow) {
                    $userId = $userRow['id'];
                    $msg = "Admin has " . $action . " your organizer registration.";
                    $msgStmt = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
                    if ($msgStmt) {
                        $msgStmt->bind_param("is", $userId, $msg);
                        $msgStmt->execute();
                        $msgStmt->close();
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM organizer WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $orgId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Build query
$query = "SELECT * FROM organizer WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (!empty($search_email)) {
    $query .= " AND email LIKE ?";
    $params[] = "%" . $search_email . "%";
    $types .= "s";
}

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Organizer Requests - Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body class="bg-light">

<div class="container py-4">
  <h2 class="mb-4 text-center">Organizer Registration Requests</h2>

  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
      <select name="status" class="form-select">
        <option value="">-- All Statuses --</option>
        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $filter_status == 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $filter_status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>
    <div class="col-md-5">
      <input type="text" name="email" class="form-control" placeholder="Search by Email" value="<?= htmlspecialchars($search_email) ?>">
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
  </form>

  <?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th>Email</th>
            <th>Degree</th>
            <th>Experience</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['degree_program']) ?></td>
              <td><?= htmlspecialchars($row['experience']) ?></td>
              <td><?= htmlspecialchars($row['description']) ?></td>
              <td>
                <?php
                  $badge = 'secondary';
                  if ($row['status'] == 'pending') $badge = 'warning text-dark';
                  elseif ($row['status'] == 'approved') $badge = 'success';
                  elseif ($row['status'] == 'rejected') $badge = 'danger';
                ?>
                <span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <?php if ($row['status'] !== 'approved'): ?>
                    <form method="POST">
                      <input type="hidden" name="org_id" value="<?= $row['id'] ?>">
                    <button type="submit" name="action" value="approved" class="btn btn-success btn-sm" title="Approve">
          <i class="bi bi-check-circle-fill"></i>
        </button>
                    </form>
                  <?php endif; ?>
                  <?php if ($row['status'] !== 'rejected'): ?>
                    <form method="POST">
                      <input type="hidden" name="org_id" value="<?= $row['id'] ?>">
                           <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm" title="Reject">
          <i class="bi bi-x-circle-fill"></i>
        </button>
                    </form>
                  <?php endif; ?>
                  <?php if ($row['status'] !== 'pending'): ?>
                    <form method="POST">
                      <input type="hidden" name="org_id" value="<?= $row['id'] ?>">
                      <button type="submit" name="action" value="delete" class="btn btn-secondary btn-sm" title="Delete">
          <i class="bi bi-trash-fill"></i>
        </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-warning text-center">No organizer records found.</div>
  <?php endif; ?>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
