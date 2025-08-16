<?php
include 'db.php';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = $_POST['event_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($eventId && $action) {
        $stmt = $conn->prepare("SELECT organizer_email, title FROM event_applications WHERE id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $eventRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($eventRow) {
            $email = $eventRow['organizer_email'];
            $eventTitle = $eventRow['title'];

            $stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $userRow = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            $userId = $userRow['id'] ?? null;

            if (in_array($action, ['approved', 'rejected'])) {
                $upd = $conn->prepare("UPDATE event_applications SET status = ? WHERE id = ?");
                $upd->bind_param("si", $action, $eventId);
                $upd->execute();
                $upd->close();

                if ($userId) {
                    $msg = "Your event \"{$eventTitle}\" has been {$action} by admin.";
                    $ins = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
                    $ins->bind_param("is", $userId, $msg);
                    $ins->execute();
                    $ins->close();
                }
            }

            if ($action === 'delete') {
                $del = $conn->prepare("DELETE FROM event_applications WHERE id = ?");
                $del->bind_param("i", $eventId);
                $del->execute();
                $del->close();

                if ($userId) {
                    $msg = "Your event \"{$eventTitle}\" has been deleted by admin.";
                    $ins = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
                    $ins->bind_param("is", $userId, $msg);
                    $ins->execute();
                    $ins->close();
                }
            }
        }
    }

    if (isset($_POST['send_message'])) {
        $uid = $_POST['send_user_id'] ?? null;
        $text = trim($_POST['message_text'] ?? '');

        if ($uid && $text !== '') {
            $msgInsert = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
            $msgInsert->bind_param("is", $uid, $text);
            $msgInsert->execute();
            $msgInsert->close();
        }
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$email_filter = $_GET['email'] ?? '';

$sql = "SELECT ea.*, u.username AS organizer_name
        FROM event_applications ea
        LEFT JOIN users u ON ea.organizer_email = u.email
        WHERE 1=1";
$params = [];
$types = "";
if ($status_filter !== '') { $sql .= " AND ea.status=?"; $params[] = $status_filter; $types .= "s"; }
if ($email_filter !== '') { $sql .= " AND ea.organizer_email LIKE ?"; $params[] = "%$email_filter%"; $types .= "s"; }

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approve Events</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { margin:0; font-family:'Inter',sans-serif; background:#f7f7f7; color:#111; }
.container { max-width:1200px; margin:2rem auto; padding:0 1rem; }
h2 { text-align:center; margin-bottom:2rem; font-weight:700; color:#001633; }

/* Filters */
.filters { display:flex; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; justify-content:center; }
.filters select, .filters input { padding:0.5rem 1rem; border-radius:8px; border:1px solid #ccc; transition:all 0.2s; }
.filters select:focus, .filters input:focus { outline:none; border-color:#001633; }
.filters button { background:#001633; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; transition:all 0.2s; }
.filters button:hover { background:#000f26; }
.filters a { text-decoration:none; color:#001633; padding:0.5rem 1rem; border-radius:8px; border:1px solid #ccc; background:#e6e6e6; transition:all 0.2s; }
.filters a:hover { background:#d4d4d4; }

/* Table */
table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
thead { background:#001633; color:#fff; }
thead th { padding:0.75rem; text-align:left; font-weight:600; }
tbody td { padding:0.75rem; border-bottom:1px solid #eee; vertical-align:middle; }
tbody tr:hover { background:#f1f1f1; transition:0.2s; }

/* Status badges */
.badge { padding:0.35rem 0.6rem; border-radius:6px; font-size:0.85rem; font-weight:600; display:inline-block; }
.badge-pending { background:#ffc107; color:#1a1a1a; }
.badge-approved { background:#001633; color:#fff; }
.badge-rejected { background:#dc3545; color:#fff; }

/* Thumbnails */
.thumbnails { display:flex; gap:0.25rem; flex-wrap:wrap; }
.thumbnail { width:50px; height:50px; object-fit:cover; border-radius:6px; cursor:pointer; }

/* Action buttons container */
.actions-container { display:flex; align-items:center; gap:0.5rem; flex-wrap:nowrap; }

/* Action buttons */
button.action-btn { border:none; background:none; padding:0.25rem; cursor:pointer; font-size:1.2rem; display:inline-flex; align-items:center; justify-content:center; transition:transform 0.2s; }
button.action-btn:hover { transform:scale(1.2); }
button.approve { color:#001633; }
button.approve:hover { color:#000f26; }
button.reject { color:#dc3545; }
button.reject:hover { color:#b02a37; }
button.delete { color:#6c757d; }
button.delete:hover { color:#495057; }
.send-msg-icon { cursor:pointer; font-size:1.2rem; color:#001633; }
.send-msg-icon:hover { color:#000f26; }

/* Modal */
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:#fff; border-radius:12px; max-width:90vw; max-height:90vh; overflow:auto; padding:1rem; position:relative; }
.modal-close { position:absolute; top:0.5rem; right:0.5rem; font-size:1.8rem; border:none; background:none; cursor:pointer; color:#001633; }
.modal-close:hover { color:#dc3545; }

@media(max-width:768px){ .filters { flex-direction:column; align-items:center; } }
</style>
</head>
<body>

<div class="container">
<h2>Approve Events</h2>

<form method="get" class="filters">
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach(['pending','approved','rejected'] as $st): ?>
        <option value="<?= $st ?>" <?= $status_filter === $st?'selected':'' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="email" placeholder="Organizer email" value="<?= htmlspecialchars($email_filter) ?>">
    <button type="submit">Filter</button>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Reset</a>
</form>

<table>
<thead>
<tr>
<th>ID</th>
<th>Title</th>
<th>Email</th>
<th>Name</th>
<th>Date</th>
<th>Status</th>
<th>Proposal</th>
<th>Images</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($e=$result->fetch_assoc()): ?>
<tr>
<td><?= $e['id'] ?></td>
<td><?= htmlspecialchars($e['title']) ?></td>
<td><?= htmlspecialchars($e['organizer_email']) ?></td>
<td><?= htmlspecialchars($e['organizer_name']??'-') ?></td>
<td><?= htmlspecialchars($e['date']??'-') ?></td>
<td><span class="badge badge-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span></td>
<td>
<?php if($e['proposal_file']): ?>
<button class="approve action-btn open-modal" data-type="pdf" data-src="<?= htmlspecialchars($e['proposal_file']) ?>" title="View PDF">
<i class="bi bi-file-earmark-pdf-fill"></i>
</button>
<?php else: ?> &mdash; <?php endif; ?>
</td>
<td>
<div class="thumbnails">
<?php foreach(['image1','image2','image3'] as $imgF):
if(!empty($e[$imgF])): ?>
<img src="<?= htmlspecialchars($e[$imgF]) ?>" class="thumbnail open-modal" data-type="image" data-src="<?= htmlspecialchars($e[$imgF]) ?>">
<?php endif; endforeach; ?>
</div>
</td>
<td>
<div class="actions-container">
<?php foreach(['approved'=>'approve','rejected'=>'reject','delete'=>'delete'] as $act=>$cls):
if(!($e['status']==$act && $act!=='delete')): ?>
<form method="POST">
<input type="hidden" name="event_id" value="<?= $e['id'] ?>">
<button name="action" value="<?= $act ?>" class="action-btn <?= $cls ?>" title="<?= ucfirst($act) ?>">
<i class="bi <?= $act=='approved'?'bi-check-circle-fill':($act=='rejected'?'bi-x-circle-fill':'bi-trash-fill') ?>"></i>
</button>
</form>
<?php endif; endforeach; ?>

<?php
$stmtU = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmtU->bind_param("s",$e['organizer_email']);
$stmtU->execute();
$resU = $stmtU->get_result()->fetch_assoc();
$stmtU->close();
$uid = $resU['id']??null;
if($uid): ?>
<i class="bi bi-envelope-fill send-msg-icon" data-user-id="<?= $uid ?>" title="Send message"></i>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- Modals -->
<div class="modal" id="modal">
<div class="modal-content">
<button class="modal-close" id="modalClose">&times;</button>
<div id="modalBody"></div>
</div>
</div>

<div class="modal" id="sendMessageModal">
<form method="POST" class="modal-content">
<button type="button" class="modal-close" id="msgClose">&times;</button>
<h5>Send Message</h5>
<input type="hidden" name="send_user_id" id="send_user_id">
<textarea name="message_text" id="message_text" rows="4" style="width:100%; margin-top:0.5rem; padding:0.5rem;" required></textarea>
<button type="submit" name="send_message" style="margin-top:0.5rem; padding:0.5rem 1rem; background:#001633;color:white;border:none;border-radius:6px;">Send</button>
</form>
</div>

<script>
document.querySelectorAll('.open-modal').forEach(btn=>{
btn.onclick=()=>{
const type=btn.dataset.type, src=btn.dataset.src;
const body=document.getElementById('modalBody');
body.innerHTML='';
if(type==='pdf'){
const iframe=document.createElement('iframe');
iframe.src=src;
iframe.style.width='80vw';
iframe.style.height='90vh';
iframe.style.border='none';
body.appendChild(iframe);
}else if(type==='image'){
const img=document.createElement('img');
img.src=src;
img.style.maxWidth='90vw';
img.style.maxHeight='90vh';
img.style.display='block';
img.style.margin='0 auto';
body.appendChild(img);
}
document.getElementById('modal').style.display='flex';
};
});
document.getElementById('modalClose').onclick=()=>{document.getElementById('modal').style.display='none';};

document.querySelectorAll('.send-msg-icon').forEach(icon=>{
icon.onclick=()=>{
const uid=icon.getAttribute('data-user-id');
document.getElementById('send_user_id').value=uid;
document.getElementById('message_text').value='';
document.getElementById('sendMessageModal').style.display='flex';
};
});
document.getElementById('msgClose').onclick=()=>{document.getElementById('sendMessageModal').style.display='none';};
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
