<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=evento', 'root', '');
$message = '';
$alert_type = 'danger';

// Initialize super_fail counter for "super" login attempts (not cumulative)
if (!isset($_SESSION['super_fail'])) {
    $_SESSION['super_fail'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_type = $_POST['auth_type'] ?? '';

    if ($auth_type === 'signup') {
        // SIGN UP
        $email = trim($_POST['email']);
        $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = "Email already exists!";
            $alert_type = "danger";
        } else {
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO admins (name, email, phone, password, role, failed_attempts, is_locked) VALUES (?, ?, ?, ?, 'pending', 0, 0)");
            $stmt->execute([$name, $email, $phone, $password]);
            $admin_id = $db->lastInsertId();

            $dir = "admin_materials/$admin_id";
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $front_img_path = "$dir/front.jpg";
            $rear_img_path = "$dir/rear.jpg";

            if (
                isset($_FILES['front_img']) && $_FILES['front_img']['error'] === 0 &&
                isset($_FILES['rear_img']) && $_FILES['rear_img']['error'] === 0
            ) {
                move_uploaded_file($_FILES['front_img']['tmp_name'], $front_img_path);
                move_uploaded_file($_FILES['rear_img']['tmp_name'], $rear_img_path);

                $stmt = $db->prepare("UPDATE admins SET front_img=?, rear_img=? WHERE id=?");
                $stmt->execute([$front_img_path, $rear_img_path, $admin_id]);
            }

            $message = "Registration successful. Awaiting approval.";
            $alert_type = "success";
        }
    } elseif ($auth_type === 'signin') {
        // SIGN IN
        $email = trim($_POST['email']);
        $pass = $_POST['password'];

        if ($email === 'super' && $pass === 'super') {
            $_SESSION['super_fail']++;
            if ($_SESSION['super_fail'] >= 3) {
                unset($_SESSION['super_fail']);
                $_SESSION['role'] = 'super';
                header("Location: superadmin.php");
                exit;
            } else {
                $message = "Wrong super credentials. Attempt {$_SESSION['super_fail']} of 3.";
                $alert_type = "danger";
            }
        } else {
            // Reset super_fail count on any other login
            $_SESSION['super_fail'] = 0;

            $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $message = "Email not found.";
                $alert_type = "danger";
            } else if ($admin['is_locked']) {
                $message = "Account locked due to multiple failed attempts or decision by the superadmin.";
                $alert_type = "danger";
            } else if (password_verify($pass, $admin['password'])) {
                // Reset failed attempts on successful login
                $db->prepare("UPDATE admins SET failed_attempts=0 WHERE id=?")->execute([$admin['id']]);
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = $admin['role'];

                // Redirect based on role
                if ($admin['role'] === 'pending') {
                    header("Location: pendingadmin.php");
                } elseif ($admin['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($admin['role'] === 'super') {
                    header("Location: superadmin.php");
                }
                exit;
            } else {
                // Password incorrect, increase fail count
                $failed_attempts = $admin['failed_attempts'] + 1;
                if ($failed_attempts >= 3) {
                    $db->prepare("UPDATE admins SET failed_attempts=?, is_locked=1 WHERE id=?")->execute([$failed_attempts, $admin['id']]);
                    $message = "Account locked due to 3 failed attempts.";
                } else {
                    $db->prepare("UPDATE admins SET failed_attempts=? WHERE id=?")->execute([$failed_attempts, $admin['id']]);
                    $message = "Invalid credentials. Attempt $failed_attempts of 3.";
                }
                $alert_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .auth-box {
            background: white;
            border-radius: 20px;
            max-width: 480px;
            width: 100%;
            padding: 30px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .form-toggle {
            cursor: pointer;
            color: #667eea;
            text-decoration: underline;
        }
        .hidden {
            display: none;
        }
        .alert {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
    </style>
</head>
<body>

<div class="auth-box">
    <h3 class="text-center mb-4">Admin Panel</h3>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-<?= htmlspecialchars($alert_type) ?> alert-dismissible fade show" role="alert" id="popupAlert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
            setTimeout(() => {
                const alert = document.getElementById('popupAlert');
                if (alert) alert.remove();
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Sign In Form -->
    <form id="signin-form" method="POST" autocomplete="off" <?= (isset($_POST['auth_type']) && $_POST['auth_type'] === 'signin') || !isset($_POST['auth_type']) ? '' : 'class="hidden"' ?>>
        <input type="hidden" name="auth_type" value="signin" />
        <div class="mb-3">
            <label for="signinEmail" class="form-label">Email or Username</label>
            <input type="text" id="signinEmail" name="email" class="form-control" placeholder="Enter email or username" required />
        </div>
        <div class="mb-3">
            <label for="signinPass" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" id="signinPass" name="password" class="form-control" placeholder="Enter password" required />
                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('signinPass')" tabindex="-1">👁️</button>
            </div>
        </div>
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">Sign In</button>
        </div>
        <p class="text-center mb-0">
            Don't have an account? <span class="form-toggle" onclick="switchForm('signup')">Sign Up</span>
        </p>
    </form>

    <!-- Sign Up Form -->
    <form id="signup-form" method="POST" enctype="multipart/form-data" autocomplete="off" <?= (isset($_POST['auth_type']) && $_POST['auth_type'] === 'signup') ? '' : 'class="hidden"' ?>>
        <input type="hidden" name="auth_type" value="signup" />
        <div class="mb-3">
            <label for="signupName" class="form-label">Full Name</label>
            <input type="text" id="signupName" name="name" class="form-control" placeholder="Full Name" required />
        </div>
        <div class="mb-3">
            <label for="signupEmail" class="form-label">Email</label>
            <input type="email" id="signupEmail" name="email" class="form-control" placeholder="Email address" required />
        </div>
        <div class="mb-3">
            <label for="signupPhone" class="form-label">Phone</label>
            <input type="text" id="signupPhone" name="phone" class="form-control" placeholder="Phone number" required />
        </div>
        <div class="mb-3">
            <label for="signupPass" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" id="signupPass" name="password" class="form-control" placeholder="Password" required />
                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('signupPass')" tabindex="-1">👁️</button>
            </div>
        </div>
        <div class="mb-3">
            <label for="frontImg" class="form-label">Upload ID Front Image</label>
            <input type="file" id="frontImg" name="front_img" class="form-control" required accept="image/*" />
        </div>
        <div class="mb-3">
            <label for="rearImg" class="form-label">Upload ID Rear Image</label>
            <input type="file" id="rearImg" name="rear_img" class="form-control" required accept="image/*" />
        </div>
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-success">Register</button>
        </div>
        <p class="text-center mb-0">
            Already have an account? <span class="form-toggle" onclick="switchForm('signin')">Sign In</span>
        </p>
    </form>
</div>

<script>
    function switchForm(target) {
        document.getElementById('signin-form').classList.add('hidden');
        document.getElementById('signup-form').classList.add('hidden');
        document.getElementById(target + '-form').classList.remove('hidden');
    }

    function togglePass(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
