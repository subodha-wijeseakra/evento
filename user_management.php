<?php  
include 'db.php';

$search = "";
$feedback = "";
$feedback_type = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['search'])) {
    $search = trim($_POST['search']);
}

// CREATE USER
if (isset($_POST['create_user'])) {
    $username = trim($_POST['new_username']);
    $email = trim($_POST['new_email']);
    $whatsapp = trim($_POST['new_whatsapp']);
    $password = trim($_POST['new_password']);

    if (!empty($email) && !empty($password)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            $hashed_password = md5($password);
            $insert = $conn->prepare("INSERT INTO users (username, email, whatsapp, password) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $username, $email, $whatsapp, $hashed_password);
            $insert->execute();
            $insert->close();
            $feedback = "User created successfully.";
        } else {
            $feedback = "Email already exists!";
            $feedback_type = "danger";
        }
        $check->close();
    }
}

// UPDATE USER
if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $password = $_POST['password'];

    $sql = "UPDATE users SET username=?, email=?, whatsapp=?";
    $params = [$username, $email, $whatsapp];
    $types = "sss";

    if (!empty($password)) {
        $sql .= ", password=?";
        $params[] = md5($password);
        $types .= "s";
    }

    $sql .= " WHERE id=?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
    $feedback = "User updated successfully.";
}

// DELETE USER
if (isset($_POST['delete_user'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $feedback = "User deleted successfully.";
}

// SEND MESSAGE
if (isset($_POST['send_message'])) {
    $email = $_POST['email'];
    $message = "Admin sent you: " . $_POST['message'];

    $getUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $getUser->bind_param("s", $email);
    $getUser->execute();
    $getUser->bind_result($userId);
    $getUser->fetch();
    $getUser->close();

    if (!empty($userId) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $userId, $message);
        $stmt->execute();
        $stmt->close();
        $feedback = "Message sent successfully.";
    } else {
        $feedback = "Message failed to send.";
        $feedback_type = "danger";
    }
}

// IMPORT CSV
if (isset($_POST['import_csv'])) {
    if ($_FILES['csv_file']['error'] === 0 && pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION) === 'csv') {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($file); // skip header

        while (($data = fgetcsv($file)) !== FALSE) {
            $username = $data[0] ?? '';
            $email = $data[1] ?? '';
            $whatsapp = $data[2] ?? '';
            $password = md5("user_123");

            if (!empty($email)) {
                $check = $conn->prepare("SELECT id FROM users WHERE email=?");
                $check->bind_param("s", $email);
                $check->execute();
                $check->store_result();

                if ($check->num_rows === 0) {
                    $insert = $conn->prepare("INSERT INTO users (username, email, whatsapp, password) VALUES (?, ?, ?, ?)");
                    $insert->bind_param("ssss", $username, $email, $whatsapp, $password);
                    $insert->execute();
                    $insert->close();
                }
                $check->close();
            }
        }
        fclose($file);
        $feedback = "Users imported successfully.";
    } else {
        $feedback = "Invalid CSV file.";
        $feedback_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .form-control-sm { font-size: 0.85rem; }
    .table td, .table th { vertical-align: middle; }
    .icon-btn { border: none; background: none; padding: 0; }
    .message-input { max-width: 280px; }
  </style>
</head>
<body class="bg-light p-4">
<div class="container">
  <h2 class="mb-4">User Management</h2>

  <!-- Feedback Alert -->
  <?php if (!empty($feedback)): ?>
    <div id="alertBox" class="alert alert-<?= $feedback_type ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($feedback) ?>
    </div>
  <?php endif; ?>

  <!-- CSV Upload -->
  <form method="post" enctype="multipart/form-data" class="mb-3 d-flex gap-2 flex-wrap">
    <input type="file" name="csv_file" accept=".csv" class="form-control" required>
    <button type="submit" name="import_csv" class="btn btn-success"><i class="bi bi-upload"></i> Upload CSV</button>
    <a href="sample_users.csv" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Sample CSV</a>
  </form>

  <!-- Create User -->
  <form method="post" class="mb-3 border rounded p-3 bg-white shadow-sm">
    <h5>Create New User</h5>
    <div class="row g-2">
      <div class="col-md"><input type="text" name="new_username" class="form-control" placeholder="Username" required></div>
      <div class="col-md"><input type="email" name="new_email" class="form-control" placeholder="Email" required></div>
      <div class="col-md"><input type="text" name="new_whatsapp" class="form-control" placeholder="WhatsApp"></div>
      <div class="col-md"><input type="text" name="new_password" class="form-control" placeholder="Password" required></div>
      <div class="col-auto"><button type="submit" name="create_user" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create</button></div>
    </div>
  </form>

  <!-- Search -->
  <form method="POST" class="mb-3 d-flex gap-2">
    <input type="text" name="search" class="form-control" placeholder="Search by email or WhatsApp" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
  </form>

  <!-- User Table -->
  <table class="table table-bordered table-sm bg-white align-middle">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Username</th>
        <th>Email</th>
        <th>WhatsApp</th>
        <th>Password</th>
        <th>Role</th>
        <th>Update</th>
        <th>Delete</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $sql = "SELECT * FROM users";
    if (!empty($search)) {
        $sql .= " WHERE email LIKE ? OR whatsapp LIKE ?";
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $count = 1;
    while ($user = $result->fetch_assoc()):
        $email = $user['email'];
        $role = "Student";

        // Check if the user is an approved organizer
        $check = $conn->prepare("SELECT status FROM organizer WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->bind_result($org_status);
        if ($check->fetch() && $org_status === 'approved') {
            $role .= ", Organizer";
        }
        $check->close();
    ?>
      <tr>
        <form method="post">
          <td><?= $count++ ?></td>
          <td><input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($user['username']) ?>"></td>
          <td><input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($user['email']) ?>"></td>
          <td><input type="text" name="whatsapp" class="form-control form-control-sm" value="<?= htmlspecialchars($user['whatsapp']) ?>"></td>
          <td><input type="text" name="password" class="form-control form-control-sm" placeholder="New password"></td>
          <td><?= $role ?></td>
          <td>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <button type="submit" name="update_user" class="btn btn-outline-success btn-sm"><i class="bi bi-check-lg"></i></button>
          </td>
        </form>
        <td>
          <form method="post">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
          </form>
        </td>
        <td>
          <form method="post" class="d-flex align-items-center gap-2">
            <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
            <input type="text" name="message" class="form-control form-control-sm message-input" placeholder="Type message" required>
            <button type="submit" name="send_message" class="icon-btn text-success"><i class="bi bi-send-fill fs-5"></i></button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script>
  // Auto-hide alerts in 3 seconds
  const alertBox = document.getElementById('alertBox');
  if (alertBox) {
    setTimeout(() => alertBox.classList.remove('show'), 2000);
    setTimeout(() => alertBox.remove(), 2500);
  }
</script>
</body>
</html>
