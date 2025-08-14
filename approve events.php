<?php
include 'db.php';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = $_POST['event_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($eventId && $action) {
        $stmt = $conn->prepare("SELECT organizer_email, title FROM event_applications WHERE id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $eventRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($eventRow) {
            $email = $eventRow['organizer_email'];
            $eventTitle = $eventRow['title'];

            $stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $userRow = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            $userId = $userRow['id'] ?? null;

            if (in_array($action, ['approved', 'rejected'])) {
                $upd = $conn->prepare("UPDATE event_applications SET status = ? WHERE id = ?");
                $upd->bind_param("si", $action, $eventId);
                $upd->execute();
                $upd->close();

                if ($userId) {
                    $msg = "Your event \"{$eventTitle}\" has been {$action} by admin.";
                    $ins = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
                    $ins->bind_param("is", $userId, $msg);
                    $ins->execute();
                    $ins->close();
                }
            }

            if ($action === 'delete') {
                $del = $conn->prepare("DELETE FROM event_applications WHERE id = ?");
                $del->bind_param("i", $eventId);
                $del->execute();
                $del->close();

                if ($userId) {
                    $msg = "Your event \"{$eventTitle}\" has been deleted by admin.";
                    $ins = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
                    $ins->bind_param("is", $userId, $msg);
                    $ins->execute();
                    $ins->close();
                }
            }
        }
    }

    // Custom message handler
    if (isset($_POST['send_message'])) {
        $uid = $_POST['send_user_id'] ?? null;
        $text = trim($_POST['message_text'] ?? '');

        if ($uid && $text !== '') {
            $msgInsert = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
            $msgInsert->bind_param("is", $uid,$text);
            $msgInsert->execute();
            $msgInsert->close();
        }
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$email_filter = $_GET['email'] ?? '';

$sql = "SELECT ea.*, u.username AS organizer_name
        FROM event_applications ea
        LEFT JOIN users u ON ea.organizer_email = u.email
        WHERE 1=1";
$params = [];
$types = "";
if ($status_filter !== '') { $sql .= " AND ea.status=?"; $params[] = $status_filter; $types .= "s"; }
if ($email_filter !== '') { $sql .= " AND ea.organizer_email LIKE ?"; $params[] = "%$email_filter%"; $types .= "s"; }

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Event Approvals</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .modal-custom {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.6);
      align-items: center; justify-content: center;
      z-index: 2000;
    }
    .modal-content-custom {
      background: #fff; padding: 1rem; border-radius: 8px;
      max-width: 90vw; max-height: 90vh; overflow: auto;
      position: relative;
    }
    .modal-close {
      position: absolute; top: 1rem; right: 1rem;
      font-size: 2rem; font-weight: bold; color: #333;
      background: transparent; border: none; cursor: pointer;
      z-index: 2100;
    }
    .modal-close:hover { color: red; }
    img.thumbnail {
      max-width: 60px; max-height: 60px;
      cursor: pointer; margin: 2px;
    }
    .send-msg-icon {
      cursor: pointer;
      color: #0d6efd;
      font-size: 1.2rem;
      margin-left: 5px;
    }
    .send-msg-icon:hover {
      color: #0a58ca;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">
  <h2 class="text-center mb-4">Event Requests Approval</h2>
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'rejected'] as $st): ?>
          <option value="<?= $st ?>" <?= $status_filter === $st ? 'selected' : ''; ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <input name="email" class="form-control" placeholder="Organizer email" value="<?= htmlspecialchars($email_filter) ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Filter</button>
    </div>
    <div class="col-md-2">
      <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Email</th>
          <th>Name</th>
          <th>Date</th>
          <th>Status</th>
          <th>Proposal</th>
          <th>Images</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($e = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $e['id'] ?></td>
            <td><?= htmlspecialchars($e['title']) ?></td>
            <td><?= htmlspecialchars($e['organizer_email']) ?></td>
            <td><?= htmlspecialchars($e['organizer_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($e['date'] ?? '-') ?></td>
            <td>
              <?php
                $class = 'secondary';
                if ($e['status'] == 'pending') $class = 'warning text-dark';
                elseif ($e['status'] == 'approved') $class = 'success';
                elseif ($e['status'] == 'rejected') $class = 'danger';
              ?>
              <span class="badge bg-<?= $class ?>"><?= ucfirst($e['status']) ?></span>
            </td>
            <td>
              <?php if ($e['proposal_file']): ?>
                <button class="btn btn-sm btn-outline-primary open-modal" data-type="pdf" data-src="<?= htmlspecialchars($e['proposal_file']) ?>">View PDF</button>
              <?php else: echo '&mdash;'; endif; ?>
            </td>
            <td>
              <?php foreach (['image1','image2','image3'] as $imgField): ?>
                <?php if (!empty($e[$imgField])): ?>
                  <img src="<?= htmlspecialchars($e[$imgField]) ?>" class="thumbnail open-modal" data-type="image" data-src="<?= htmlspecialchars($e[$imgField]) ?>" alt="event image">
                <?php endif; ?>
              <?php endforeach; ?>
            </td>
            <td>
              <?php foreach(['approved'=>'Approve', 'rejected'=>'Reject', 'delete'=>'Delete'] as $act => $label): ?>
                <?php if (!($e['status'] === $act && $act !== 'delete')): ?>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                    <button name="action" value="<?= $act ?>" class="btn btn-<?= $act == 'approved' ? 'success' : ($act == 'rejected' ? 'danger' : 'secondary') ?> btn-sm" title="<?= ucfirst($label) ?>">
                      <i class="bi bi-<?= $act == 'approved' ? 'check-circle-fill' : ($act == 'rejected' ? 'x-circle-fill' : 'trash-fill') ?>"></i>
                    </button>
                  </form>
                <?php endif; ?>
              <?php endforeach; ?>

              <?php
                $stmtU = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmtU->bind_param("s", $e['organizer_email']);
                $stmtU->execute();
                $resU = $stmtU->get_result()->fetch_assoc();
                $stmtU->close();
                $uid = $resU['id'] ?? null;
                if ($uid):
              ?>
                <i class="bi bi-envelope-fill send-msg-icon" data-bs-toggle="modal" data-bs-target="#sendMessageModal" data-user-id="<?= $uid ?>"></i>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Image/PDF Modal -->
<div class="modal-custom" id="modal">
  <div class="modal-content-custom">
    <button class="modal-close" id="modalClose">&times;</button>
    <div id="modalBody"></div>
  </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Message to Organizer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="send_user_id" id="send_user_id">
        <div class="mb-3">
          <label for="message_text" class="form-label">Message</label>
          <textarea name="message_text" id="message_text" rows="4" class="form-control" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button name="send_message" class="btn btn-primary">Send</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.open-modal').forEach(btn => {
  btn.onclick = () => {
    const type = btn.dataset.type, src = btn.dataset.src;
    const body = document.getElementById('modalBody');
    body.innerHTML = '';
    if (type === 'pdf') {
      const iframe = document.createElement('iframe');
      iframe.src = src;
      iframe.style.width = '80vw';
      iframe.style.height = '95vh';
      iframe.style.border = 'none';
      iframe.style.display = 'block';
      iframe.style.margin = '0 auto';
      body.appendChild(iframe);
    } else if (type === 'image') {
      const img = document.createElement('img');
      img.src = src;
      img.style.maxWidth = '90vw';
      img.style.maxHeight = '95vh';
      img.style.display = 'block';
      img.style.margin = '0 auto';
      body.appendChild(img);
    }
    document.getElementById('modal').style.display = 'flex';
  };
});

document.getElementById('modalClose').onclick = () => {
  document.getElementById('modal').style.display = 'none';
};

document.querySelectorAll('.send-msg-icon').forEach(icon => {
  icon.addEventListener('click', () => {
    const userId = icon.getAttribute('data-user-id');
    document.getElementById('send_user_id').value = userId;
    document.getElementById('message_text').value = '';
  });
});

</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
