<?php
session_start();
include 'db.php';

// Load all event IDs in order (once)
if (!isset($_SESSION['event_ids'])) {
    $result = $conn->query("SELECT id FROM events ORDER BY id ASC");
    $_SESSION['event_ids'] = [];
    while ($row = $result->fetch_assoc()) {
        $_SESSION['event_ids'][] = $row['id'];
    }
}

// Handle index increment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next'])) {
    $_SESSION['current_index'] = ($_SESSION['current_index'] ?? 0) + 1;
    if ($_SESSION['current_index'] >= count($_SESSION['event_ids'])) {
        $_SESSION['current_index'] = 0; // loop back
    }
} else {
    $_SESSION['current_index'] = $_SESSION['current_index'] ?? 0;
}

$currentEventId = $_SESSION['event_ids'][$_SESSION['current_index']];

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $currentEventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Event images
$images = [];
$stmt = $conn->prepare("SELECT image_url FROM event_images WHERE event_id = ?");
$stmt->bind_param("i", $currentEventId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $images[] = $row['image_url'];
$stmt->close();

// Campaigns
$campaigns = [];
$stmt = $conn->prepare("SELECT * FROM fundraising_campaigns WHERE event_id = ?");
$stmt->bind_param("i", $currentEventId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $campaigns[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .event-container { display: flex; margin: 40px; }
        .carousel { width: 40%; position: relative; }
        .carousel img { width: 100%; display: none; }
        .carousel img.active { display: block; }
        .details { width: 50%; margin-left: 5%; }
        .section { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .next-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
    <script>
        let currentImage = 0;
        function showImage(index) {
            const images = document.querySelectorAll('.carousel img');
            images.forEach(img => img.classList.remove('active'));
            images[index].classList.add('active');
        }

        function nextImage() {
            const images = document.querySelectorAll('.carousel img');
            currentImage = (currentImage + 1) % images.length;
            showImage(currentImage);
        }

        function prevImage() {
            const images = document.querySelectorAll('.carousel img');
            currentImage = (currentImage - 1 + images.length) % images.length;
            showImage(currentImage);
        }

        window.onload = () => showImage(currentImage);
    </script>
</head>
<body>
<div class="event-container">
    <div class="carousel">
        <?php foreach ($images as $img): ?>
            <img src="<?= htmlspecialchars($img) ?>" alt="Event Image">
        <?php endforeach; ?>
        <button onclick="prevImage()">❮</button>
        <button onclick="nextImage()">❯</button>
    </div>

    <div class="details">
        <div class="section"><strong>Title:</strong> <?= htmlspecialchars($event['title']) ?></div>
        <div class="section"><strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?></div>
        <div class="section"><strong>Description:</strong> <?= htmlspecialchars($event['description']) ?></div>
        <div class="section">
            <strong>Fundraising:</strong>
            <ul>
                <?php foreach ($campaigns as $c): ?>
                    <li><?= htmlspecialchars($c['campaign_name']) ?> - <?= $c['amount_raised'] ?>/<?= $c['amount_goal'] ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="section"><strong>Status:</strong> <?= htmlspecialchars($event['status']) ?></div>

        <form method="POST">
            <button type="submit" name="next" class="next-btn">Next Event →</button>
        </form>
    </div>
</div>
</body>
</html>
