<?php
session_start();
include 'db.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo ''; // no user logged in
    exit;
}

$stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($msg = $result->fetch_assoc()) {
    echo '<div class="notification-item">';
    echo htmlspecialchars($msg['content']);
    echo '</div>';
}

$stmt->close();
$conn->close();
?>
