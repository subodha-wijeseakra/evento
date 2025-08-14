<?php
include 'db.php';

// Get counts
$orgCount = $conn->query("SELECT COUNT(*) AS cnt FROM organizer WHERE status='pending'")->fetch_assoc()['cnt'];
$eventCount = $conn->query("SELECT COUNT(*) AS cnt FROM event_applications WHERE status='pending'")->fetch_assoc()['cnt'];
$userCount = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];
$yesterday = date('Y-m-d', strtotime('-1 day'));
$pasteventCount = $conn->query("SELECT COUNT(*) AS cnt FROM event_applications WHERE date <= '$yesterday'")->fetch_assoc()['cnt'];
?>

<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: admin_auth.php");

$db = new PDO('mysql:host=localhost;dbname=evento', 'root', '', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$me = $_SESSION['user_id'];

// Fetch admin data
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$me]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
  session_destroy();
  header("Location: admin_auth.php");
  exit;
}
$adminName = htmlspecialchars($admin['name']);

// Handle AJAX requests for mark as read and delete
if (isset($_GET['action']) && isset($_GET['msg_id'])) {
  header('Content-Type: application/json');
  $msgId = (int)$_GET['msg_id'];

  if ($_GET['action'] === 'mark_read') {
    $upd = $db->prepare("UPDATE admin_chats SET is_read = 1 WHERE id = ? AND receiver_id = ?");
    $success = $upd->execute([$msgId, $me]);
    echo json_encode(['success' => $success]);
    exit;
  }

  if ($_GET['action'] === 'delete') {
    $del = $db->prepare("DELETE FROM admin_chats WHERE id = ? AND receiver_id = ?");
    $success = $del->execute([$msgId, $me]);
    echo json_encode(['success' => $success]);
    exit;
  }

  echo json_encode(['success' => false, 'error' => 'Invalid action']);
  exit;
}

// Fetch last 5 messages from super admins sent TO this admin
$msgStmt = $db->prepare("
  SELECT m.*, a.name AS sender_name, a.role AS sender_role 
  FROM admin_chats m
  JOIN admins a ON m.sender_id = a.id
  WHERE m.receiver_id = ? AND a.role = 'super'
  ORDER BY m.id DESC
  LIMIT 5
");
$msgStmt->execute([$me]);
$messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread messages (for badge)
$unreadCount = 0;
foreach ($messages as $m) {
  if (empty($m['is_read']) || $m['is_read'] == 0) {
    $unreadCount++;
  }
}

// Handle update account info
$updateFeedback = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
  $newName = trim($_POST['name']);
  $newEmail = trim($_POST['email']);
  $password = $_POST['password_confirm'];

  if (password_verify($password, $admin['password'])) {
    $upd = $db->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
    $upd->execute([$newName, $newEmail, $me]);
    $updateFeedback = "Account info updated successfully.";
    header("Refresh:0");
    exit;
  } else {
    $updateFeedback = "Incorrect password. Please try again.";
  }
}

// Handle password change
$passFeedback = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $oldPass = $_POST['old_password'];
  $newPass = $_POST['new_password'];
  $confPass = $_POST['confirm_password'];

  if (!password_verify($oldPass, $admin['password'])) {
    $passFeedback = "Old password is incorrect.";
  } elseif ($newPass !== $confPass) {
    $passFeedback = "New password and confirm password do not match.";
  } elseif (strlen($newPass) < 6) {
    $passFeedback = "New password must be at least 6 characters.";
  } else {
    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
    $upd = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $upd->execute([$newHash, $me]);
    $passFeedback = "Password changed successfully.";
    header("Refresh:0");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
   <title>Welcome, <?= $adminName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .dashboard-container {
      width: 80%;
      margin: auto;
      padding: 2rem 0;
    }
    .tile {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 10px;
      padding: 2rem;
      text-align: center;
      cursor: pointer;
      transition: 0.3s;
      height: 100%;
    }
    .tile:hover {
      background: #e2e6ea;
    }
    .tile h4 {
      margin-bottom: 1rem;
    }
    .tile .count {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .modal-body {
      max-height: 100vh;
      overflow-y: auto;
    }
    .modal-95 {
  max-width: 98% !important;
  width: 98%;
  height: 100%;
}
    .navbar-nav {
      flex: 1;
      justify-content: center;
    }
    .dropdown-scroll {
      max-height: 300px;
      overflow-y: auto;
      min-width: 400px;
    }
    .msg-action-icons {
      font-size: 1.2rem;
      cursor: pointer;
      margin-left: 10px;
      color: #555;
      user-select: none;
    }
    .msg-action-icons:hover {
      color: #000;
    }
    .msg-actions-container {
      display: flex;
      gap: 12px;
      align-items: center;
      white-space: nowrap;
    }
    
    /* Keep messages normal (no opacity change) */
  </style>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
 
  <script>
    async function markAsRead(msgId, iconElem) {
      try {
        const res = await fetch('?action=mark_read&msg_id=' + msgId);
        const data = await res.json();
        if (data.success) {
          // Remove badge count by 1 if > 0
          let badge = document.querySelector('.nav-link.position-relative > .badge');
          if (badge) {
            let count = parseInt(badge.textContent);
            count = count > 0 ? count - 1 : 0;
            if (count === 0) badge.remove();
            else badge.textContent = count;
          }
          // Disable mark as read icon (remove pointer and color)
          iconElem.style.pointerEvents = 'none';
          iconElem.style.color = '#888';
          iconElem.setAttribute('title', 'Read');
        } else {
          alert('Failed to mark as read.');
        }
      } catch (e) {
        alert('Error: ' + e.message);
      }
    }

    async function deleteMsg(msgId, iconElem) {
      if (!confirm('Delete this message?')) return;
      try {
        const res = await fetch('?action=delete&msg_id=' + msgId);
        const data = await res.json();
        if (data.success) {
          // Remove message from DOM
          const li = iconElem.closest('li');
          li.remove();

          // Update badge count by 1 if unread
          let badge = document.querySelector('.nav-link.position-relative > .badge');
          if (badge) {
            let count = parseInt(badge.textContent);
            // Check if message was unread before deleting to update count
            const wasUnread = li.dataset.isRead === '0' || li.dataset.isRead === undefined;
            if (wasUnread && count > 0) {
              count--;
              if (count <= 0) badge.remove();
              else badge.textContent = count;
            }
          }
        } else {
          alert('Failed to delete message.');
        }
      } catch (e) {
        alert('Error: ' + e.message);
      }
    }
  </script>

</head>
<body class="bg-light">


 <!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
  <a class="navbar-brand" href="#">Evento</a>

  <div class="collapse navbar-collapse">
    <ul class="navbar-nav text-center">
      <li class="nav-item"><a class="nav-link" href="#">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="#">Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="#">Support</a></li>
    </ul>
  </div>

  <ul class="navbar-nav ms-auto align-items-center gap-3">
    <!-- Notification Bell -->
    <li class="nav-item dropdown">
      <a class="nav-link text-light position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Messages">
        <i class="bi bi-bell-fill fs-5"></i>
        <?php if ($unreadCount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $unreadCount ?>
          </span>
        <?php endif; ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end p-2 dropdown-scroll" aria-labelledby="dropdownMenuButton1" style="min-width: 400px;">
        <?php if (count($messages) === 0): ?>
          <li><small class="text-muted ps-2">No new messages</small></li>
        <?php else: ?>
          <?php foreach ($messages as $msg): 
            $isRead = (!isset($msg['is_read']) || $msg['is_read'] == 0) ? false : true;
            $msgId = (int)$msg['id'];
            ?>
            <li class="mb-3" id="msg-<?= $msgId ?>" data-is-read="<?= $isRead ? '1' : '0' ?>">
              <div>
                <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                <small class="text-muted"> (<?= htmlspecialchars($msg['sender_role']) ?>)</small><br>
                <span><?= nl2br(htmlspecialchars($msg['message'])) ?></span><br>
                <?php
                // Display sent time if available
                if (isset($msg['sent_time'])) {
                  echo '<small class="text-muted">' . date('d M Y, H:i', strtotime($msg['sent_time'])) . '</small>';
                }
                ?>
              </div>
              <div class="msg-actions-container mt-1">
                <i 
                  class="bi bi-eye msg-action-icons" 
                  title="<?= $isRead ? 'Already read' : 'Mark as read' ?>" 
                  style="<?= $isRead ? 'pointer-events:none;color:#888;' : '' ?>"
                  onclick="<?= $isRead ? '' : "markAsRead($msgId, this)" ?>"></i>
                <i class="bi bi-trash msg-action-icons text-danger" title="Delete message" onclick="deleteMsg(<?= $msgId ?>, this)"></i>
              </div>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </li>

    <!-- Profile Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Profile">
        <i class="bi bi-person-circle fs-5"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li class="dropdown-item-text">Hey <?= $adminName ?>!</li>
        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateModal">Update Account Info</a></li>
        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="admin_auth.php">Logout</a></li>
      </ul>
    </li>
  </ul>
</nav>

<!-- Main content -->
<div class="container mt-4">
  <h1>Hello, <?= $adminName ?></h1>
  <p>Welcome to your dashboard.</p>
</div>

<!-- Update Account Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content p-3">
      <h5 class="modal-title mb-3">Update Account Info</h5>

      <?php if($updateFeedback): ?>
      <div class="alert alert-info"><?= htmlspecialchars($updateFeedback) ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label for="updateName" class="form-label">Name</label>
        <input id="updateName" type="text" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required />
      </div>
      <div class="mb-3">
        <label for="updateEmail" class="form-label">Email</label>
        <input id="updateEmail" type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required />
      </div>
      <div class="mb-3">
        <label for="passwordConfirm" class="form-label">Enter Password to Confirm</label>
        <input id="passwordConfirm" type="password" name="password_confirm" class="form-control" required />
      </div>
      <div class="modal-footer">
        <button type="submit" name="update_admin" class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content p-3">
      <h5 class="modal-title mb-3">Change Password</h5>

      <?php if($passFeedback): ?>
      <div class="alert alert-info"><?= htmlspecialchars($passFeedback) ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label for="oldPassword" class="form-label">Old Password</label>
        <input id="oldPassword" type="password" name="old_password" class="form-control" required />
      </div>
      <div class="mb-3">
        <label for="newPassword" class="form-label">New Password</label>
        <input id="newPassword" type="password" name="new_password" class="form-control" required minlength="6" />
      </div>
      <div class="mb-3">
        <label for="confirmPassword" class="form-label">Confirm New Password</label>
        <input id="confirmPassword" type="password" name="confirm_password" class="form-control" required minlength="6" />
      </div>
      <div class="modal-footer">
        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>


<div class="dashboard-container">
  <h2 class="text-center mb-4">Admin Dashboard</h2>
  <div class="row g-4">
    <div class="col-md-6 col-lg-3">
      <div class="tile" data-bs-toggle="modal" data-bs-target="#organizerModal">
        <h4>Organizer Requests</h4>
        <div class="count"><?= $orgCount ?></div>
        <p>Pending organizer account approvals</p>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="tile" data-bs-toggle="modal" data-bs-target="#eventsModal">
        <h4>Pending Events</h4>
        <div class="count"><?= $eventCount ?></div>
        <p>Event approvals waiting for review</p>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="tile" data-bs-toggle="modal" data-bs-target="#usersModal">
        <h4>User Management</h4>
       <div class="count"><?= $userCount ?></div>
        <p>System Users</p>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="tile" data-bs-toggle="modal" data-bs-target="#reportsModal">
        <h4>Reports & Analysis</h4>
        <div class="count"><?= $pasteventCount ?></div>
        <p>Track performance and activity</p>
      </div>
    </div>
  </div>
</div>

<!-- Modals -->

<!-- Organizer Modal -->
<div class="modal fade" id="organizerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-95" style="height: 95vh; max-height: 95vh;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Organizer Requests</h5>
        <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe src="approve organizer.php" style="width: 100%; height: 80vh; border: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Events Modal -->
<div class="modal fade" id="eventsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-95">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pending Events</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        <div class="modal-body p-0">
        <iframe src="approve events.php" style="width: 100%; height: 80vh; border: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Users Modal -->
<div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-95">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User Management</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe src="user_management.php" style="width: 100%; height: 80vh; border: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Reports Modal -->
<div class="modal fade" id="reportsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-95">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reports & Analysis</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
         <iframe src="reports.php" style="width: 100%; height: 80vh; border: none;"></iframe>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>


