<?php
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['is_approved'] == 1) {
    header("Location: admin_auth.php");
    exit();
}

$user_id = $_SESSION['admin_id'];
$user_name = $_SESSION['admin_name'];
$user_email = $_SESSION['admin_email'];
$super_admin_id = 1; // Adjust if needed

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Approval Pending</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        #chatBox {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .message {
            margin-bottom: 10px;
        }
        .message.you {
            text-align: right;
            font-weight: bold;
            color: #0d6efd;
        }
        .message.other {
            text-align: left;
            font-style: italic;
            color: #6c757d;
        }
        .timestamp {
            font-size: 0.75rem;
            color: #999;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5 text-center">
    <h2>Your application has been sent to the admin panel</h2>
    <p class="lead">
        Once approved by the super admin, you can start your journey as an admin.
    </p>

    <h4 class="mt-4"><?= htmlspecialchars($user_name) ?></h4>
    <p><?= htmlspecialchars($user_email) ?></p>

    <button class="btn btn-primary mt-4" data-bs-toggle="modal" data-bs-target="#chatModal">
        Chat with Super Admin
    </button>
</div>

<!-- Chat Modal -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="chatModalLabel">Chat with Super Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="chatBox"></div>
      <div class="modal-footer">
        <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." autocomplete="off" />
        <button class="btn btn-success" id="sendBtn">Send</button>
      </div>
    </div>
  </div>
</div>

<script>
const userId = <?= json_encode($user_id) ?>;
const superAdminId = <?= json_encode($super_admin_id) ?>;

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadChat() {
    fetch(`load_messages.php?user1=${userId}&user2=${superAdminId}`)
        .then(res => res.json())
        .then(messages => {
            const chatBox = document.getElementById('chatBox');
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.classList.add('message');
                div.classList.add(msg.sender_id == userId ? 'you' : 'other');
                div.innerHTML = `
                    <div>${escapeHtml(msg.message)}</div>
                    <div class="timestamp">${msg.sent_at}</div>
                `;
                chatBox.appendChild(div);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();
    if (!msg) return;
    fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `message=${encodeURIComponent(msg)}&receiver_id=${superAdminId}`
    }).then(() => {
        input.value = '';
        loadChat();
    });
}

document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('messageInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});

// Poll chat messages only when modal is shown
const chatModal = document.getElementById('chatModal');
let chatInterval = null;

chatModal.addEventListener('shown.bs.modal', () => {
    loadChat();
    chatInterval = setInterval(loadChat, 2000);
});
chatModal.addEventListener('hidden.bs.modal', () => {
    clearInterval(chatInterval);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
