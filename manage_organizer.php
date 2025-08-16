<?php
include 'db.php';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgId = $_POST['org_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($orgId && $action) {
        if (in_array($action, ['approved', 'rejected'])) {
            $upd = $conn->prepare("UPDATE organizer SET status = ? WHERE id = ?");
            $upd->bind_param("si", $action, $orgId);
            $upd->execute();
            $upd->close();

            // Notify user
            $stmtEmail = $conn->prepare("SELECT email FROM organizer WHERE id=?");
            $stmtEmail->bind_param("i", $orgId);
            $stmtEmail->execute();
            $resEmail = $stmtEmail->get_result()->fetch_assoc();
            $stmtEmail->close();

            if ($resEmail) {
                $userEmail = $resEmail['email'];
                $stmtUser = $conn->prepare("SELECT id FROM users WHERE email=?");
                $stmtUser->bind_param("s", $userEmail);
                $stmtUser->execute();
                $resUser = $stmtUser->get_result()->fetch_assoc();
                $stmtUser->close();

                if ($resUser) {
                    $msg = "Admin has {$action} your organizer registration.";
                    $stmtMsg = $conn->prepare("INSERT INTO messages (user_id, content) VALUES (?, ?)");
                    $stmtMsg->bind_param("is", $resUser['id'], $msg);
                    $stmtMsg->execute();
                    $stmtMsg->close();
                }
            }
        } elseif ($action === 'delete') {
            $del = $conn->prepare("DELETE FROM organizer WHERE id=?");
            $del->bind_param("i", $orgId);
            $del->execute();
            $del->close();
        }
    }
}

// Filters
$filter_status = $_GET['status'] ?? '';
$search_email = $_GET['email'] ?? '';

$sql = "SELECT * FROM organizer WHERE 1=1";
$params = [];
$types = "";

if ($filter_status) { $sql .= " AND status=?"; $params[] = $filter_status; $types .= "s"; }
if ($search_email) { $sql .= " AND email LIKE ?"; $params[] = "%$search_email%"; $types .= "s"; }

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
<title>Organizer Requests</title>
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

/* Action buttons container */
.actions { display:flex; gap:0.5rem; flex-wrap:nowrap; }

/* Action buttons */
button.action-btn { border:none; background:none; padding:0.25rem; cursor:pointer; font-size:1.2rem; display:inline-flex; align-items:center; justify-content:center; }
button.approve { color:#001633; }
button.approve:hover { color:#000f26; }
button.reject { color:#dc3545; }
button.reject:hover { color:#b02a37; }
button.delete { color:#6c757d; }
button.delete:hover { color:#495057; }

/* Responsive */
@media(max-width:768px){ .filters { flex-direction:column; align-items:center; } }
</style>
</head>
<body>

<div class="container">
<h2>Organizer Registration Requests</h2>

<form method="GET" class="filters">
<select name="status">
<option value="">All statuses</option>
<option value="pending" <?= $filter_status=='pending'?'selected':'' ?>>Pending</option>
<option value="approved" <?= $filter_status=='approved'?'selected':'' ?>>Approved</option>
<option value="rejected" <?= $filter_status=='rejected'?'selected':'' ?>>Rejected</option>
</select>
<input type="text" name="email" placeholder="Search by Email" value="<?= htmlspecialchars($search_email) ?>">
<button type="submit">Filter</button>
<a href="<?= $_SERVER['PHP_SELF'] ?>">Reset</a>
</form>

<?php if($result->num_rows>0): ?>
<table>
<thead>
<tr>
<th>Email</th>
<th>Degree</th>
<th>Experience</th>
<th>Description</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['degree_program']) ?></td>
<td><?= htmlspecialchars($row['experience']) ?></td>
<td><?= htmlspecialchars($row['description']) ?></td>
<td>
<span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
</td>
<td>
<div class="actions">
<?php if($row['status']!=='approved'): ?>
<form method="POST">
<input type="hidden" name="org_id" value="<?= $row['id'] ?>">
<button type="submit" name="action" value="approved" class="action-btn approve" title="Approve">
<i class="bi bi-check-circle-fill"></i>
</button>
</form>
<?php endif; ?>
<?php if($row['status']!=='rejected'): ?>
<form method="POST">
<input type="hidden" name="org_id" value="<?= $row['id'] ?>">
<button type="submit" name="action" value="rejected" class="action-btn reject" title="Reject">
<i class="bi bi-x-circle-fill"></i>
</button>
</form>
<?php endif; ?>
<?php if($row['status']!=='pending'): ?>
<form method="POST">
<input type="hidden" name="org_id" value="<?= $row['id'] ?>">
<button type="submit" name="action" value="delete" class="action-btn delete" title="Delete">
<i class="bi bi-trash-fill"></i>
</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<div style="text-align:center; margin-top:2rem; color:#dc3545;">No organizer records found.</div>
<?php endif; ?>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
