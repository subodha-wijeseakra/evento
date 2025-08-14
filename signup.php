<?php
$db = new PDO('mysql:host=localhost;dbname=evento', 'root', '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        exit("Email already exists!");
    }

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert first to get ID
    $stmt = $db->prepare("INSERT INTO admins (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $password]);
    $admin_id = $db->lastInsertId();

    // Upload images
    $dir = "admin_materials/$admin_id";
    mkdir($dir, 0777, true);
    move_uploaded_file($_FILES['front_img']['tmp_name'], "$dir/front.jpg");
    move_uploaded_file($_FILES['rear_img']['tmp_name'], "$dir/rear.jpg");

    // Update paths in DB
    $stmt = $db->prepare("UPDATE admins SET front_img=?, rear_img=? WHERE id=?");
    $stmt->execute(["$dir/front.jpg", "$dir/rear.jpg", $admin_id]);

    echo "Registered successfully. Awaiting approval.";
}
?>
