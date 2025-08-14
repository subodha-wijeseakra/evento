<?php
session_start();
include 'db.php'; // Your DB connection

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? null;
$id = intval($_POST['id'] ?? 0);

if ($action === 'read' && $id > 0) {
    // Mark message as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'read']);
    } else {
        echo json_encode(['error' => 'Query error (read)']);
    }
    exit;
}

if ($action === 'delete' && $id > 0) {
    // Delete message
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'deleted']);
    } else {
        echo json_encode(['error' => 'Query error (delete)']);
    }
    exit;
}

// Optionally: Future enhancement to return messages list
if ($action === 'fetch') {
    $stmt = $conn->prepare("SELECT id, content, is_read, created_at FROM messages WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['messages' => $messages]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
exit;
