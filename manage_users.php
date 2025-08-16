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

// UPDATE USER with Validation
if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $whatsapp = trim($_POST['whatsapp']);
    $password = trim($_POST['password']);

    // Check if critical fields are empty
    if (empty($username) || empty($email) || empty($whatsapp)) {
        $feedback = "Username, email, and WhatsApp cannot be empty.";
        $feedback_type = "danger";
    } else {
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

/* GENERAL */
body {
    font-family: 'Inter', sans-serif;
    background: #f7f7f7;
    margin: 0;
    padding: 2rem;
    color: #111;
}
.container {
    max-width: 1200px;
    margin: auto;
}
h2 {
    text-align: center;
    font-weight: 700;
    margin-bottom: 2rem;
    color: #001633;
}

/* ALERT */
.alert {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    padding: 0.75rem 1rem;
    opacity: 1;
    transition: opacity 0.5s ease-out;
    margin-bottom: 1.5rem;
}
.alert.alert-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}
.alert.alert-danger {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}
.alert.show {
    opacity: 1;
}
.alert.hide {
    opacity: 0;
}

/* FORMS */
.form-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.form-card input,
.form-card select,
.form-card button {
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
    transition: all 0.2s;
}
.form-card input:focus,
.form-card select:focus {
    outline: none;
    border-color: #001633;
}
.form-card button {
    cursor: pointer;
    border: none;
}
.form-card button:hover {
    filter: brightness(0.9);
}

/* CREATE USER FORM */
.create-user-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.create-user-container input {
    flex-grow: 1;
}
.create-user-container button {
    flex-shrink: 0;
}

/* SEARCH BAR */
.search-bar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.search-bar input {
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
}
.search-bar input:focus { border-color: #001633; }
.search-bar button {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    border: none;
    background: #001633;
    color: #fff;
    transition: all 0.2s;
}
.search-bar button:hover { background: #000f26; }

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
thead {
    background: #001633;
    color: #fff;
}
thead th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
}
tbody td {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
tbody tr:hover {
    background: #f1f1f1;
    transition: 0.2s;
}
tbody input {
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 0.35rem 0.5rem;
    font-size: 0.9rem;
}
tbody input:focus { border-color: #001633; }

/* ACTION BUTTONS */
button.action-btn {
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.2rem;
    transition: transform 0.2s;
}
button.action-btn:hover { transform: scale(1.2); }
button.update { color: #001633; }
button.update:hover { color: #000f26; }
button.delete { color: #dc3545; }
button.delete:hover { color: #b02a37; }
button.send { color: #0d6efd; }
button.send:hover { color: #0b5ed7; }

/* Message UI */
.message-cell {
    position: relative;
    text-align: center;
}
.message-cell .message-icon {
    cursor: pointer;
    font-size: 1.5rem;
    color: #0070f3;
    transition: color 0.2s;
}
.message-cell .message-icon:hover {
    color: #005bb5;
}
.message-box {
    position: absolute;
    top: 50%;
    right: 110%;
    transform: translateY(-50%);
    z-index: 10;
    width: 250px;
    padding: 1rem;
    background: #fff;
    border: 1px solid #eaeaea;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: none;
}
.message-box.show {
    display: block;
}
.message-box .message-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.message-box .message-input-group .message-input {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 0.35rem 0.5rem;
}

/* RESPONSIVE */
@media(max-width:768px) {
    .search-bar { flex-direction: column; }
    .search-bar button { width: 100%; }
}
</style>
</head>
<body>
<div class="container">
<h2>User Management</h2>

<?php if (!empty($feedback)): ?>
<div id="alertBox" class="alert alert-<?= $feedback_type ?> show"><?= htmlspecialchars($feedback) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card d-flex gap-2 flex-wrap">
<input type="file" name="csv_file" accept=".csv" required>
<button type="submit" name="import_csv" class="btn btn-success"><i class="bi bi-upload"></i> Upload CSV</button>
<a href="sample_users.csv" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Sample CSV</a>
</form>

<form method="post" class="form-card">
<h5>Create New User</h5>
<div class="create-user-container">
<input type="text" name="new_username" placeholder="Username" required>
<input type="email" name="new_email" placeholder="Email" required>
<input type="text" name="new_whatsapp" placeholder="WhatsApp">
<input type="text" name="new_password" placeholder="Password" required>
<button type="submit" name="create_user" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create</button>
</div>
</form>

<form method="POST" class="search-bar">
<input type="text" name="search" placeholder="Search by email or WhatsApp" value="<?= htmlspecialchars($search) ?>">
<button type="submit"><i class="bi bi-search"></i></button>
</form>

<table>
<thead>
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

$check = $conn->prepare("SELECT status FROM organizer WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->bind_result($org_status);
if ($check->fetch() && $org_status === 'approved') $role .= ", Organizer";
$check->close();
?>
<tr>
<form method="post">
<td><?= $count++ ?></td>
<td><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"></td>
<td><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></td>
<td><input type="text" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp']) ?>"></td>
<td><input type="text" name="password" placeholder="New password"></td>
<td><?= $role ?></td>
<td>
<input type="hidden" name="id" value="<?= $user['id'] ?>">
<button type="submit" name="update_user" class="action-btn update"><i class="bi bi-check-lg"></i></button>
</td>
</form>
<td>
<form method="post">
<input type="hidden" name="id" value="<?= $user['id'] ?>">
<button type="submit" name="delete_user" class="action-btn delete"><i class="bi bi-trash"></i></button>
</form>
</td>
<td class="message-cell">
<i class="bi bi-chat-text message-icon" onclick="toggleMessageBox(this)"></i>
<div class="message-box">
<form method="post">
<div class="message-input-group">
<input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
<input type="text" name="message" class="message-input" placeholder="Type message" required>
<button type="submit" name="send_message" class="action-btn send">
    <i class="bi bi-send fs-5"></i>
</button>
</div>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<script>
function toggleMessageBox(icon) {
    const messageBox = icon.nextElementSibling;
    document.querySelectorAll('.message-box.show').forEach(box => {
        if (box !== messageBox) {
            box.classList.remove('show');
        }
    });
    messageBox.classList.toggle('show');
}

document.addEventListener('click', (event) => {
    if (!event.target.closest('.message-cell')) {
        document.querySelectorAll('.message-box.show').forEach(box => {
            box.classList.remove('show');
        });
    }
});

const alertBox = document.getElementById('alertBox');
if(alertBox){
    setTimeout(() => alertBox.classList.remove('show'), 2000);
    setTimeout(() => alertBox.remove(), 2500);
}
</script>
</body>
</html>