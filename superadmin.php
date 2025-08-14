<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=evento','root','',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
]);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $adminId = (int)($_POST['admin_id'] ?? 0);

  // Update details
  if (isset($_POST['update_admin'])) {
    $vals = [trim($_POST['name']), trim($_POST['email']), trim($_POST['phone']), $_POST['role']];
    $q = 'UPDATE admins SET name=?, email=?, phone=?, role=?';
    if (!empty($_POST['password'])) {
      $q .= ', password=?';
      $vals[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    $q .= ' WHERE id=?';
    $vals[] = $adminId;
    $db->prepare($q)->execute($vals);
  }

  // Send Message
  elseif (isset($_POST['send_msg'])) {
    $stmt = $db->prepare("
      INSERT INTO admin_chats (sender_id, receiver_id, message)
      VALUES (:me, :to, :msg)
    ");
    $stmt->execute([
      ':me'=>$_SESSION['user_id'],
      ':to'=>$adminId,
      ':msg'=>trim($_POST['message'])
    ]);
    $_SESSION['last_msg_admin_id'] = $adminId;
  }

  // Lock admin
  elseif (isset($_POST['lock_admin'])) {
    $db->prepare("UPDATE admins SET is_locked=1 WHERE id=?")->execute([$adminId]);
  }

  // Unlock admin
  elseif (isset($_POST['unlock_admin'])) {
    $db->prepare("UPDATE admins SET is_locked=0, failed_attempts=0 WHERE id=?")->execute([$adminId]);
  }

  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Handle search
$where = '';
$params = [];
if (!empty($_GET['search'])) {
  $term = "%".trim($_GET['search'])."%";
  $where = "WHERE email LIKE ? OR phone LIKE ?";
  $params = [$term, $term];
}

// Fetch admins
$stmt = $db->prepare("SELECT * FROM admins $where ORDER BY id");
$stmt->execute($params);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For reopening message modal
$last = $_SESSION['last_msg_admin_id'] ?? null;
unset($_SESSION['last_msg_admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Super Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .img-thumb { width:50px; cursor:pointer; }
    .actions { display:flex; gap:0.5rem; }
    .icon-btn { background:none;border:none;font-size:1.25rem;cursor:pointer; }
    .icon-btn.save { color:#0d6efd; }
    .icon-btn.message { color:#198754; }
    .icon-btn.locked { color:#dc3545; }
    .icon-btn.unlocked { color:#6c757d; }
  </style>
</head>
<body class="bg-light">
    

<div class="container py-5">
  <h2 class="mb-4">Manage Admins</h2>

  <!-- Search form -->
  <form class="mb-4" method="GET">
    <div class="input-group">
      <input type="text" name="search" class="form-control" placeholder="Search by email or phone" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered bg-white align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>New Pwd</th><th>Role</th>
          <th>ID Front</th><th>ID Rear</th><th>Actions</th><th>Lock</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
        <tr>
          <form method="POST">
            <td><?= $a['id'] ?></td>
            <td><input name="name" class="form-control" value="<?= htmlspecialchars($a['name']) ?>"></td>
            <td><input name="email" type="email" class="form-control" value="<?= htmlspecialchars($a['email']) ?>"></td>
            <td><input name="phone" class="form-control" value="<?= htmlspecialchars($a['phone']) ?>"></td>
            <td><input name="password" type="password" class="form-control" placeholder="New pwd"></td>
            <td>
              <select name="role" class="form-select">
                <?php foreach (['pending','admin','super'] as $r): ?>
                  <option value="<?= $r ?>" <?= $a['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><img src="<?= htmlspecialchars($a['front_img']) ?>" class="img-thumb" data-bs-toggle="modal" data-bs-target="#front<?= $a['id'] ?>"></td>
            <td><img src="<?= htmlspecialchars($a['rear_img']) ?>" class="img-thumb" data-bs-toggle="modal" data-bs-target="#rear<?= $a['id'] ?>"></td>
            <td class="actions">
              <input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
              <button type="submit" name="update_admin" class="icon-btn save" title="Save"><i class="bi bi-check2"></i></button>
              <button type="submit" name="send_msg" class="icon-btn message" title="Message"><i class="bi bi-envelope"></i></button>
            </td>
            <td class="text-center">
              <?php if ($a['is_locked']): ?>
                <button type="submit" name="unlock_admin" class="icon-btn unlocked" title="Unlock"><i class="bi bi-unlock-fill"></i></button>
              <?php else: ?>
                <button type="submit" name="lock_admin" class="icon-btn locked" title="Lock"><i class="bi bi-lock-fill"></i></button>
              <?php endif; ?>
            </td>
          </form>

          <!-- Modals follow same as earlier... -->
          <!-- FRONT modal -->
          <div class="modal fade" id="front<?= $a['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content p-3">
                <div class="modal-header"><h5 class="modal-title">Front ID #<?= $a['id'] ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center"><img src="<?= htmlspecialchars($a['front_img']) ?>" class="img-fluid"></div>
            </div></div>
          </div>

          <!-- REAR modal -->
          <div class="modal fade" id="rear<?= $a['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content p-3">
                <div class="modal-header"><h5 class="modal-title">Rear ID #<?= $a['id'] ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center"><img src="<?= htmlspecialchars($a['rear_img']) ?>" class="img-fluid"></div>
            </div></div>
          </div>

          <!-- MESSAGE modal -->
          <div class="modal fade" id="msg<?= $a['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-scrollable"><div class="modal-content p-3">
              <div class="modal-header">
                <h5 class="modal-title">Message Admin #<?= $a['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="POST">
                <div class="modal-body">
                  <input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
                  <textarea name="message" class="form-control mb-3" rows="3" placeholder="Your message…" required></textarea>
                  <button type="submit" name="send_msg" class="btn btn-success">Send</button>
                  <hr><strong>Recent Messages:</strong>
                  <div class="mt-2" style="max-height:200px;overflow:auto">
                    <?php
                    $q = $db->prepare("
                      SELECT * FROM admin_chats
                      WHERE (sender_id=:me AND receiver_id=:them) OR
                            (sender_id=:them AND receiver_id=:me)
                      ORDER BY sent_at DESC LIMIT 5
                    ");
                    $q->execute([':me'=>$_SESSION['user_id'],':them'=>$a['id']]);
                    foreach ($q->fetchAll() as $m):
                    ?>
                      <div class="border rounded p-2 mb-2">
                        <strong><?= $m['sender_id']==$_SESSION['user_id']?'You':'They' ?>:</strong>
                        <?= nl2br(htmlspecialchars($m['message'])) ?><br>
                        <small class="text-muted"><?= $m['sent_at'] ?></small>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </form>
            </div></div>
          </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const last = <?= $last ?? 'null' ?>;
  if (last) {
    setTimeout(() => {
      new bootstrap.Modal(document.getElementById('msg' + last)).show();
    }, 100);
  }
});
</script>


</body>
</html>
