<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;

$unread_count = 0;
$messages = [];

if ($user_id) {
    // Get unread count
    $count_result = $conn->query("SELECT COUNT(*) AS count FROM messages WHERE user_id = $user_id AND is_read = 0");
    $count_row = $count_result->fetch_assoc();
    $unread_count = $count_row['count'];

    // ✅ Fetch messages with created_at for date-time display
    $msg_result = $conn->query("SELECT id, content, is_read, created_at FROM messages WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
    
    while ($row = $msg_result->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>

<?php

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
<?php

include 'db.php'; // Make sure this is your valid DB connection file

$success = '';
$error = '';

// Fetch user data (for settings)
$userData = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<?php

include 'db.php'; // your DB connection

$organizer_success = '';
$organizer_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['organizer_submit'])) {
    $email = trim($_POST['org_email']);
    $degree = $_POST['org_degree'];
    $experience = trim($_POST['org_experience']);
    $description = trim($_POST['org_description']);
    $status = 'pending'; // default status

    // Step 1: Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM organizer WHERE email = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $organizer_error = "This email is already registered as an organizer.";
        } else {
            // Step 2: Insert only if not exists
            $stmt = $conn->prepare("INSERT INTO organizer (email, degree_program, experience, description, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $email, $degree, $experience, $description, $status);
                if ($stmt->execute()) {
                    $organizer_success = "Your application was submitted successfully!";
                } else {
                    $organizer_error = "Something went wrong. Try again later.";
                }
                $stmt->close();
            } else {
                $organizer_error = "Database error: " . $conn->error;
            }
        }

        $checkStmt->close();
    } else {
        $organizer_error = "Database error: " . $conn->error;
    }
}
?>

<?php 
include 'db.php';


$username = $_SESSION['username'] ?? 'Guest';
$email = $_SESSION['email'] ?? null;
$organizerData = null;
$isOrganizer = false;

if ($email) {
    $stmt = $conn->prepare("SELECT id, name, degree_program, experience FROM organizer WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($organizerData = $result->fetch_assoc()) {
            // Set organizer session variables
            $_SESSION['organizer_id'] = $organizerData['id'];
            $_SESSION['organizer_email'] = $email;
            $_SESSION['organizer_name'] = $organizerData['name'];
            $_SESSION['organizer_degree'] = $organizerData['degree_program'];
            $_SESSION['organizer_experience'] = $organizerData['experience'];
            $isOrganizer = true;
        }
        $stmt->close();
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>
   <?php
    
      if (isset($_SESSION['username'])) {
          echo "Welcome, " . htmlspecialchars($_SESSION['username']);
      } else {
          echo "Evento";
      }
    ?>
</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link href="Assests/css/dbcarousel.css" rel="stylesheet">
<link href="Assests/css/navbar.css" rel="stylesheet">
  <link href="Assests/css/carousel.css" rel="stylesheet">
  <link href="Assests/css/tcontainer.css" rel="stylesheet">
  <link href="Assests/css/aucontainer.css" rel="stylesheet">
  <link href="Assests/css/dbslideshow.css" rel="stylesheet">
  <link href="Assests/css/fqcontainer.css" rel="stylesheet">
  <link href="Assests/css/cucontainer.css" rel="stylesheet">
  <link href="Assests/css/jumpup.css" rel="stylesheet">
  <link href="Assests/css/footer.css" rel="stylesheet">
  <script src="Assests/js/jumpup.js" defer></script>
  <script src="Assests/js/carousel.js" defer></script>
  <script src="Assests/js/navbar.js" defer></script>
<style>
  .responsive-stroke {
    width: 80%;
    margin: 2rem auto;
    border: none;
    border-top: 2px solid #0e0e0e;
  }

  @media (max-width: 768px) {
    .responsive-stroke {
      width: 100%;
    }
  }
    .dropdown-submenu > .dropdown-menu {
    position: static;
    float: none;
    margin-top: 0.25rem;
    border-radius: 0.25rem;
    box-shadow: none;
  }
    

    .event-container {
      display: flex;
      flex-wrap: wrap;
      padding: 2rem;
      gap: 2rem;
    }

    .event-carousel {
      flex: 1 1 100%;
      max-width: 40%;
      position: relative;
    }

    .event-carousel img {
      width: 100%;
      display: none;
      border-radius: 8px;
    }

    .event-carousel img.active-slide {
      display: block;
    }

    .carousel-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0,0,0,0.6);
      color: #fff;
      border: none;
      font-size: 1.5rem;
      padding: 0.5rem 1rem;
      cursor: pointer;
      border-radius: 5px;
    }

    .nav-left { left: 10px; }
    .nav-right { right: 10px; }

    .event-info {
      flex: 1 1 100%;
      max-width: 55%;
    }

    .info-section {
      background: #fff;
      margin-bottom: 1.2rem;
      padding: 1rem;
      border-left: 4px solid #007bff;
      box-shadow: 0 0 6px rgba(0,0,0,0.05);
    }

    .info-section strong {
      display: block;
      color: #333;
      margin-bottom: 0.5rem;
    }

    .event-button {
      padding: 0.75rem 1.5rem;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .event-carousel, .event-info {
        flex: 1 1 100%;
        max-width: 100%;
      }
    }
    
        :root {
      --primary-color: #007bff;
      --success-color: #28a745;
      --bg-light: #f8f9fa;
      --text-light: #6c757d;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--bg-light);
      padding: 0px;
    }

    .form-container {
      max-width: 720px;
      background: white;
      margin: auto;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .step-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .step-tab {
      flex: 1;
      text-align: center;
      padding: 10px;
      border-bottom: 3px solid lightgray;
      color: var(--text-light);
      font-weight: 500;
      cursor: pointer;
    }

    .step-tab.active {
      border-color: var(--primary-color);
      color: var(--primary-color);
      font-weight: bold;
    }

    .step-tab.completed {
      color: var(--success-color);
      border-color: var(--success-color);
    }

    .step-content {
      display: none;
    }

    .step-content.active {
      display: block;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      font-weight: 500;
      display: block;
      margin-bottom: 6px;
    }

    .form-control {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    .btn {
      padding: 10px 18px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
    }

    .btn-next {
      background: var(--primary-color);
      color: white;
    }

    .btn-prev {
      background: #6c757d;
      color: white;
    }

    .btn-submit {
      background: var(--success-color);
      color: white;
    }

    .btn-group {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      gap: 10px;
    }

    .alert {
      padding: 10px 20px;
      background-color: #d4edda;
      color: #155724;
      border-left: 5px solid #28a745;
      margin-bottom: 20px;
      border-radius: 6px;
    }

    @media (max-width: 600px) {
      .step-tab {
        font-size: 14px;
        padding: 8px;
      }

      .btn-group {
        flex-direction: column;
      }
    }
     .btn-next {
    background-color: #0d6efd;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background-color 0.3s ease;
  }
  .btn-next:hover {
    background-color: #0b5ed7;
  }
  .btn-prev {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background-color 0.3s ease;
  }
  .btn-prev:hover {
    background-color: #5c636a;
  }
  .btn-submit {
    background-color: #198754;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background-color 0.3s ease;
  }
  .btn-submit:hover {
    background-color: #157347;
  }

</style>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <!-- Bootstrap CSS & Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>
<body>




<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm px-3" style="position: fixed; top: 0; width: 100%; z-index: 1030;">
  
  <!-- Logo -->
  <a class="navbar-brand" href="reguser.php">
    <img src="Assests/Images/evento-logo.jpg" alt="Logo" height="40">
  </a>

  <!-- Right-side: Icons + Toggler (in one row) -->
  <div class="d-flex align-items-center ms-auto order-lg-3">

    <!-- Notification Icon -->
    <div class="dropdown me-2">
  <a class="btn btn-light position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-bell fs-5"></i>
    <?php if ($unread_count > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
        <?= $unread_count ?>
      </span>
    <?php endif; ?>
  </a>
  <ul class="dropdown-menu dropdown-menu-end p-2 shadow" aria-labelledby="notifDropdown" style="width: 350px; max-width: 90vw;">
    <li><strong class="dropdown-header">Notifications</strong></li>
    <?php if (count($messages) > 0): ?>
      <?php foreach ($messages as $msg): ?>
        <li class="d-flex justify-content-between align-items-start px-2 py-1 border-bottom small">
          <div>
            <div class="<?= $msg['is_read'] ? 'text-muted' : 'fw-bold' ?>">
              <?= htmlspecialchars($msg['content']) ?>
            </div>
            <?php if (isset($msg['created_at'])): ?>
              <small class="text-secondary">
                <?= date("M d, Y - h:i A", strtotime($msg['created_at'])) ?>
              </small>
            <?php endif; ?>
          </div>
          <div class="ms-2 d-flex gap-1">
            <?php if (!$msg['is_read']): ?>
              <button class="btn btn-sm btn-outline-success btn-read" data-id="<?= $msg['id'] ?>" title="Mark as Read">
                <i class="fas fa-eye"></i>
              </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $msg['id'] ?>" title="Delete">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </li>
      <?php endforeach; ?>
    <?php else: ?>
      <li><span class="dropdown-item text-muted">No messages</span></li>
    <?php endif; ?>
  </ul>
</div>

<?php
include 'db.php';


$username = $_SESSION['username'] ?? 'Guest';
$email = $_SESSION['email'] ?? null;

$isOrganizer = false;

if ($email) {
    $stmt = $conn->prepare("SELECT id FROM organizer WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $isOrganizer = $result->num_rows > 0;
        $stmt->close();
    }
}
?>

<!-- Profile Dropdown -->
<div class="dropdown me-2">
  <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser"
     data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-user-circle" style="font-size: 2rem;"></i>
  </a>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser" style="width: 250px;">
    <li><a class="dropdown-item" href="#">Hey, <?= htmlspecialchars($username) ?></a></li>
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">Settings</a></li>
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</a></li>

    <li class="dropdown-submenu">
      <a class="dropdown-item dropdown-toggle text-primary" href="#" id="switchAccountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        Switch Account
      </a>
      <ul class="dropdown-menu" aria-labelledby="switchAccountDropdown">
        <?php if (!$isOrganizer): ?>
          <li><div class="dropdown-item text-muted">No Such account</div></li>
        <?php endif; ?>
        <li>
          <a href="reguser.php" class="dropdown-item" style="color: black;">General profile</a>
        </li>
      </ul>
    </li>

    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
  </ul>
</div>

<!-- JS for toggling submenu click -->
<script>
  document.querySelectorAll('.dropdown-menu .dropdown-toggle').forEach(function(element){
    element.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const submenu = this.nextElementSibling;

      // Close all open submenus
      const allSubmenus = this.closest('.dropdown-menu').querySelectorAll('.dropdown-menu');
      allSubmenus.forEach(menu => {
        if (menu !== submenu) menu.classList.remove('show');
      });

      // Toggle the clicked submenu
      submenu.classList.toggle('show');
    });
  });
</script>



    <!-- Toggler (only for small screens) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>

  <!-- Collapsible Navigation Center -->
  <div class="collapse navbar-collapse justify-content-center order-lg-2" id="navbarNav">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link active" href="reguser.php"><span>Home</span></a></li>
      <li class="nav-item"><a class="nav-link" href="#customCarouselTrack"><span>Gallery</span></a></li>
      <li class="nav-item"><a class="nav-link" href="#aboutus"><span>About Us</span></a></li>
      <li class="nav-item"><a class="nav-link" href="#faq"><span>FAQs</span></a></li>
      <li class="nav-item"><a class="nav-link" href="#contact"><span>Contact Us</span></a></li>
    </ul>
  </div>
</nav>

<!-- Style fix for dropdown on small screens -->
<style>
  @media (max-width: 576px) {
    .dropdown-menu {
      width: 250px !important;
    }
  }
</style>


   <script>
    let currentSlide = 0;

    function showSlide(index) {
      const slides = document.querySelectorAll('.event-carousel img');
      slides.forEach(slide => slide.classList.remove('active-slide'));
      if (slides.length > 0) slides[index].classList.add('active-slide');
    }

    function nextSlide() {
      const slides = document.querySelectorAll('.event-carousel img');
      if (slides.length) {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
      }
    }

    function prevSlide() {
      const slides = document.querySelectorAll('.event-carousel img');
      if (slides.length) {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
      }
    }

    window.onload = () => showSlide(currentSlide);
  </script>



  <div class="event-container" style="margin-top: 80px;">
    <div class="event-carousel">
      <?php foreach ($images as $img): ?>
        <img src="<?= htmlspecialchars($img) ?>" alt="Event Slide">
      <?php endforeach; ?>
      <?php if (count($images) > 1): ?>
        <button class="carousel-nav nav-left" onclick="prevSlide()">&#10094;</button>
        <button class="carousel-nav nav-right" onclick="nextSlide()">&#10095;</button>
      <?php endif; ?>
    </div>

    <div class="event-info">
      <div class="info-section">
        <strong>Title:</strong> <?= htmlspecialchars($event['title']) ?>
      </div>
      <div class="info-section">
        <strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?>
      </div>
      <div class="info-section">
        <strong>Description:</strong> <?= htmlspecialchars($event['description']) ?>
      </div>
      <div class="info-section">
        <strong>Fundraising Campaigns:</strong>
        <ul>
          <?php foreach ($campaigns as $c): ?>
            <li><?= htmlspecialchars($c['campaign_name']) ?> - <?= $c['amount_raised'] ?>/<?= $c['amount_goal'] ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="info-section">
        <strong>Status:</strong> <?= htmlspecialchars($event['status']) ?>
      </div>
      <form method="POST">
        <button type="submit" name="next" class="event-button">Next Event</button>
      </form>
    </div>
  </div>
</div>



<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    // Get form data
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $date = isset($_POST['date']) ? $_POST['date'] : null;
    $agreed = isset($_POST['agree']) ? 1 : 0;

    // Validate session email
    if (!isset($_SESSION['email'])) {
        die("Error: Organizer email not found in session. Please login.");
    }
    $organizer_email = $_SESSION['email'];

    // Insert new event application
    $stmt = $conn->prepare("INSERT INTO event_applications (organizer_email, title, description, date, agreed_terms) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssssi", $organizer_email, $title, $description, $date, $agreed);
    $stmt->execute();

    $event_id = $stmt->insert_id;
    $stmt->close();

    // Create upload directory
    $upload_dir = "uploads/event_$event_id/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Upload proposal file
    $proposalPath = "";
    if (!empty($_FILES['proposal']['name'])) {
        $proposalName = basename($_FILES['proposal']['name']);
        $proposalPath = $upload_dir . $proposalName;
        move_uploaded_file($_FILES['proposal']['tmp_name'], $proposalPath);
    }

    // Upload up to 3 images
    $imagePaths = ["", "", ""];
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES["image$i"]['name'])) {
            $imageName = basename($_FILES["image$i"]['name']);
            $imagePaths[$i] = $upload_dir . $imageName;
            move_uploaded_file($_FILES["image$i"]['tmp_name'], $imagePaths[$i]);
        }
    }

    // Update record with file paths
    $stmt = $conn->prepare("UPDATE event_applications SET proposal_file = ?, image1 = ?, image2 = ?, image3 = ? WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssssi", $proposalPath, $imagePaths[0], $imagePaths[1], $imagePaths[2], $event_id);
    $stmt->execute();
    $stmt->close();

    $success = true;
    echo "<script>
  setTimeout(function() {
    window.location.href = window.location.href;
  }, 3000);
</script>";
}

?>


<!-- Reusable Content Component -->
<div class="content-container">
  <h2 class="content-title">Apply for an event</h2>
</div>



<div class="form-container">
 

  <?php if (isset($success) && $success): ?>
    <div class="alert" 
     style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px 20px; border-radius: 4px; margin-bottom: 15px;">
  Your event application was submitted successfully!
</div>
  <?php endif; ?>

  <div class="step-header" id="stepIndicator">
    <div class="step-tab active" data-step="0">Basic</div>
    <div class="step-tab" data-step="1">Proposal</div>
    <div class="step-tab" data-step="2">Gallery</div>
    <div class="step-tab" data-step="3">Publish</div>
  </div>

  <form method="POST" enctype="multipart/form-data" id="eventForm">
    <!-- Step 1 -->
 <div class="step-content active" data-step="0">
  <div class="form-group">
    <label>Event Title</label>
    <input type="text" name="title" class="form-control" required>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description" class="form-control" rows="3" required></textarea>
  </div>
  <div class="form-group">
    <label>Event Date</label>
    <input type="date" name="date" class="form-control" required>
  </div>
  <div class="btn-group">
    <button type="button" class="btn btn-next"
      style="background-color: #0d6efd; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Next</button>
  </div>
</div>
    <!-- Step 2 -->
    <div class="step-content" data-step="1">
      <div class="form-group">
        <label>Upload Proposal (PDF/DOCX)</label>
        <input type="file" name="proposal" class="form-control" accept=".pdf,.doc,.docx" required>
      </div>
      <div class="btn-group">
        <button type="button" class="btn btn-prev"  style="background-color: #6c757d; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Back</button>
        <button type="button" class="btn btn-next"  style="background-color: #0d6efd; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Next</button>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="step-content" data-step="2">
      <div class="form-group">
        <label>Flyer 1</label>
        <input type="file" name="image0" class="form-control" accept="image/*">
      </div>
      <div class="form-group">
        <label>Flyer 2</label>
        <input type="file" name="image1" class="form-control" accept="image/*">
      </div>
      <div class="form-group">
        <label>Flyer 3</label>
        <input type="file" name="image2" class="form-control" accept="image/*">
      </div>
      <div class="btn-group">
        <button type="button" class="btn btn-prev"  style="background-color: #6c757d; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Back</button>
        <button type="button" class="btn btn-next"  style="background-color: #0d6efd; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Next</button>
      </div>
    </div>

    <!-- Step 4 -->
    <div class="step-content" data-step="3">
      <div class="form-group">
        <input type="checkbox" name="agree" required>
        <label>I agree to the terms and conditions.</label>
      </div>
      <div class="btn-group">
        <button type="button" class="btn btn-prev"  style="background-color: #6c757d; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Back</button>
        <button type="submit" class="btn btn-submit"  name="submit_application"  style="background-color: #198754; color: white; border: none; padding: 8px 18px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 14px;">Submit Application</button>
      </div>
    </div>
  </form>
</div>

<script>
  const steps = document.querySelectorAll('.step-content');
  const stepTabs = document.querySelectorAll('.step-tab');
  const nextButtons = document.querySelectorAll('.btn-next');
  const prevButtons = document.querySelectorAll('.btn-prev');

  let currentStep = 0;

  function updateStepDisplay() {
    steps.forEach((step, index) => {
      step.classList.toggle('active', index === currentStep);
    });

    stepTabs.forEach((tab, index) => {
      tab.classList.toggle('active', index === currentStep);
      tab.classList.toggle('completed', index < currentStep);
    });
  }

  nextButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep < steps.length - 1) {
        currentStep++;
        updateStepDisplay();
      }
    });
  });

  prevButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 0) {
        currentStep--;
        updateStepDisplay();
      }
    });
  });

  stepTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = parseInt(tab.getAttribute('data-step'));
      if (target <= currentStep) {
        currentStep = target;
        updateStepDisplay();
      }
    });
  });
</script>




<div class="content-container" style="margin-bottom: 20px;">
  <h2 class="content-title">Event Calendar</h2>
</div>
  
<div class="carousel-wrapper">
  <div id="courseCarousel" class="carousel slide" data-ride="carousel" data-interval="10000">
    <div class="carousel-inner">
      <?php
      include 'fetch_courses.php';
      $chunks = array_chunk($courses, 4);
      foreach ($chunks as $index => $chunk) {
        echo '<div class="carousel-item'.($index === 0 ? ' active' : '').'">';
        echo '<div class="row justify-content-center">';
        foreach ($chunk as $i => $course) {
          echo '<div class="col-md-5">';
          echo '<div class="tile">';
          echo '<h5>'.$course['title'].'</h5>';
          echo '<div class="department">'.$course['department'].'</div>';
          echo '<div class="description">'.$course['description'].'</div>';
          echo '<div class="footer">';
          echo '<span>'.$course['date'].'</span>';
          echo '<span>$'.$course['fee'].'</span>';
          echo '</div>'; // footer
          echo '</div>'; // tile
          echo '</div>'; // col
        }
        echo '</div>'; // row
        echo '</div>'; // carousel-item
      }
      ?>
      
    </div>
<!-- Controls -->
<div class="carousel-controls d-flex justify-content-between align-items-center position-absolute top-50 start-0 w-100 px-3" style="transform: translateY(-40%); z-index: 3;">
 

   <a class="carousel-control-prev" href="#courseCarousel" role="button" data-slide="prev">
  <i class="bi bi-arrow-left-circle-fill carousel-btn left"></i>
</a>
<a class="carousel-control-next" href="#courseCarousel" role="button" data-slide="next">
  <i class="bi bi-arrow-right-circle-fill carousel-btn right"></i>
</a>
</div>
  </div>
</div>





<hr class="responsive-stroke">


<!-- Reusable Content Component -->
<div class="content-container">
  <h2 class="content-title">Marketplace</h2>
</div>



<div class="carousel-section">
  <div class="custom-carousel-wrapper">
    <div class="custom-carousel-track" id="customCarouselTrack">
      <div class="custom-carousel-item"><img src="Assests/Images/1.png" alt="Slide 1"></div>
      <div class="custom-carousel-item"><img src="Assests/Images/3.jpg" alt="Slide 2"></div>
      <div class="custom-carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
      <div class="custom-carousel-item"><img src="Assests/Images/3.jpg" alt="Slide 2"></div>
      <div class="custom-carousel-item"><img src="Assests/Images/2.png" alt="Slide 1"></div>
      <div class="custom-carousel-item"><img src="Assests/Images/3.jpg" alt="Slide 2"></div>  
    </div>
    <div class="custom-carousel-controls">
      <button class="custom-carousel-button" id="customPrevBtn">&#10094;</button>
      <button class="custom-carousel-button" id="customNextBtn">&#10095;</button>
    </div>
  </div>
</div>
</div>




<!-- FAQs Section -->
<section class="faqs-section" id="faq">
  <div class="faq-container">
    <h2>Frequently Asked Questions</h2>

    <div class="faq-item">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>What services do you offer?</span>
        <span class="faq-icon">&#9662;</span>
      </div>
      <div class="faq-answer">
        We offer a wide range of services including design, development, event planning, and more.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>How can I contact your team?</span>
        <span class="faq-icon">&#9662;</span>
      </div>
      <div class="faq-answer">
        You can reach us through our contact form, email, or social media channels listed on our website.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Do you provide support after delivery?</span>
        <span class="faq-icon">&#9662;</span>
      </div>
      <div class="faq-answer">
        Yes, we offer dedicated support and maintenance even after project delivery.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>What is your pricing model?</span>
        <span class="faq-icon">&#9662;</span>
      </div>
      <div class="faq-answer">
        Our pricing is flexible and based on project scope, duration, and complexity.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-question" onclick="toggleFAQ(this)">
        <span>Can I customize my project?</span>
        <span class="faq-icon">&#9662;</span>
      </div>
      <div class="faq-answer">
        Absolutely! We welcome customization and work closely with you to meet your needs.
      </div>
    </div>
  </div>
</section>





<!-- Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<section class="contact-section" id="contact">
  <div class="contact-container">
    <!-- Left: Title + Form -->
    <div class="contact-left">
      <h2 class="contact-title">Contact Us</h2>
      <form class="contact-form">
        <input type="text" placeholder="Your Name" required>
        <input type="text" placeholder="Subject" required>
        <textarea placeholder="Message" rows="5" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>

    <!-- Right: Info with Icons -->
    <div class="contact-right">
      <div class="contact-info"><i class="bi bi-geo-alt-fill"></i> 123 Main Street, Colombo, Sri Lanka</div>
      <div class="contact-info"><i class="bi bi-telephone-fill"></i> +94 77 123 4567</div>
      <div class="contact-info"><i class="bi bi-envelope-fill"></i> support@example.com</div>
    </div>
  </div>
</section>


<footer class="footer-section">
  <div class="footer-container">
    <div class="footer-columns">
      <!-- Quick Links -->
      <div class="footer-column">
        <h5>Quick Links</h5>
        <ul>
          <li><a href="#">Evento</a></li>
          <li><a href="#">Sign In</a></li>
          <li><a href="#">Sign Up</a></li>
          <li><a href="#">Gallery</a></li>
          <li><a href="#">About Us</a></li>
          <li><a href="#">FAQs</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>

      <!-- Web Service -->
      <div class="footer-column">
        <h5>Web Service</h5>
        <ul>
          <li><a href="#"  data-bs-toggle="modal" data-bs-target="#policiesModal">Policies</a></li>
          <li><a href="#">Privacy</a></li>
          <li><a href="#">Be a Partner</a></li>
          <li><a href="#">Beta</a></li>
        </ul>
      </div>
    </div>

    <!-- Bottom Footer -->
    <div class="footer-bottom">
      <p>&copy; Group 15</p>
    </div>
  </div>
</footer>

<button id="jumpToTopBtn" title="Go to top">
  <i class="bi bi-arrow-up-circle-fill"></i>
</button>

<script>
    document.getElementById('navbarCloseBtn').addEventListener('click', function() {
  // Bootstrap 5 collapse instance
  const navbarCollapse = document.getElementById('navbarNav');
  const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
  if(bsCollapse) {
    bsCollapse.hide();
  }
});
</script>


<!-- Active Link Script -->
<script>
  function setActive(clickedLink) {
    // Remove 'active' from all nav links
    const links = document.querySelectorAll('.nav-link');
    links.forEach(link => link.classList.remove('active'));

    // Add 'active' to the clicked link
    clickedLink.classList.add('active');
  }
</script>



<script>
document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".fade-slide");
  const nextBtn = document.getElementById("nextFade");
  const prevBtn = document.getElementById("prevFade");

  let current = 0;
  const total = slides.length;

  function showFadeSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove("active");
    });
    slides[index].classList.add("active");
  }

  function nextSlide() {
    current = (current + 1) % total;
    showFadeSlide(current);
  }

  function prevSlide() {
    current = (current - 1 + total) % total;
    showFadeSlide(current);
  }

  nextBtn.addEventListener("click", nextSlide);
  prevBtn.addEventListener("click", prevSlide);

  // Auto-play every 7 seconds
  setInterval(() => {
    nextSlide();
  }, 7000);
});
</script>


<script>
  function toggleFAQ(element) {
    const item = element.parentElement;
    item.classList.toggle('active');
  }
</script>

<!--Sign in model-->
<!-- Sign In Modal -->
<div class="modal fade" id="signInModal" tabindex="-1" aria-labelledby="signInModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-custom-width">
    <div class="modal-content custom-modal">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold" id="signInModalLabel">Sign In</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form autocomplete="off">
          <div class="mb-3">
            <label for="signinEmail" class="form-label">Email address</label>
            <input type="email" class="form-control" id="signinEmail" placeholder="Enter email">
          </div>
          <div class="mb-3">
            <label for="signinPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="signinPassword" placeholder="Enter password">
          </div>
          <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>
      </div>
    </div>
  </div>
</div>



<!-- Sign Up Modal -->
<div class="modal fade" id="signUpModal" tabindex="-1" aria-labelledby="signUpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-custom-width">
    <div class="modal-content custom-modal">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold" id="signUpModalLabel">Sign Up</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form autocomplete="off">
          <div class="mb-3">
            <label for="signupName" class="form-label">Your Name</label>
            <input type="text" class="form-control" id="signupName" placeholder="Enter your name">
          </div>
          <div class="mb-3">
            <label for="signupEmail" class="form-label">Email address</label>
            <input type="email" class="form-control" id="signupEmail" placeholder="Enter email">
          </div>
          <div class="mb-3">
            <label for="signupPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="signupPassword" placeholder="Enter password">
          </div>
          <button type="submit" class="btn btn-success w-100">Create Account</button>
        </form>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", () => {
    // Mark as read
    document.querySelectorAll('.btn-read').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            fetch('message_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=read&id=${id}`
            }).then(() => location.reload());
        });
    });

    // Delete
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            fetch('message_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&id=${id}`
            }).then(() => location.reload());
        });
    });
});
</script>


<!-- Modal HTML -->
<div class="modal fade" id="policiesModal" tabindex="-1" aria-labelledby="policiesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 90%; margin: auto;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="policiesModalLabel">Our Policies</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="text-align: justify;">
        <h6>1. Privacy Policy</h6>
        <p>We respect your privacy and ensure that your data is kept secure.</p>

        <h6>2. Refund Policy</h6>
        <p>Refunds are processed within 5–7 business days under the following conditions...</p>

        <h6>3. Event Guidelines</h6>
        <p>All participants must follow our safety and conduct rules at every event.</p>

        <img src="assets/images/policies.jpg" class="img-fluid mt-3" alt="Policy Image">
      </div>
    </div>
  </div>
</div>

<!-- Organizer Modal -->
<div class="modal fade" id="organizerModal" tabindex="-1" aria-labelledby="organizerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="max-width: 600px; margin: auto;">
      <div class="modal-header">
        <h5 class="modal-title">Organizer Registration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Success/Error Message -->
        <?php if (!empty($organizer_success)): ?>
          <div class="alert alert-success"><?= $organizer_success ?></div>
        <?php elseif (!empty($organizer_error)): ?>
          <div class="alert alert-danger"><?= $organizer_error ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
          <div class="mb-2">
            <label for="orgEmail" class="form-label">Email</label>
            <input type="email" name="org_email" class="form-control form-control-sm" id="orgEmail" required>
          </div>

          <div class="mb-2">
            <label for="orgDegree" class="form-label">Degree Program</label>
            <select name="org_degree" class="form-select form-select-sm" id="orgDegree" required>
              <option value="" disabled selected>Select program</option>
              <option>BBST</option>
              <option>BICT</option>
              <option>BET</option>
              <option>CST</option>
              <option>IIT</option>
              <option>MRT</option>
              <option>AQT</option>
              <option>HTE</option>
            </select>
          </div>

          <div class="mb-2">
            <label for="orgExperience" class="form-label">Experience</label>
            <input type="text" name="org_experience" class="form-control form-control-sm" id="orgExperience" required>
          </div>

          <div class="mb-2">
            <label for="orgDescription" class="form-label">Description</label>
            <textarea name="org_description" class="form-control form-control-sm" id="orgDescription" rows="3" required></textarea>
          </div>

          <div class="d-grid">
            <button type="submit" name="organizer_submit" class="btn btn-success btn-sm">Submit</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>



<?php if (!empty($organizer_success) || !empty($organizer_error)) : ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    var modal = new bootstrap.Modal(document.getElementById('organizerModal'));
    modal.show();
  });
</script>
<?php endif; ?>

<?php

include 'db.php'; // Your database connection file

// For demo: set user id and username (simulate logged in user)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;  // change this to a valid user id
    $_SESSION['username'] = 'DemoUser';
    $_SESSION['email'] = 'demo@example.com';
}

$success = '';
$error = '';

// Handle settings form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_settings'])) {
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['username']);
    $email = trim($_POST['email']);
    $whatsapp = trim($_POST['whatsapp']);
    $currentPassword = md5(trim($_POST['current_password'])); // Password hashed with MD5 for demo

    // Verify current password
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND password = ?");
    $stmt->bind_param("is", $userId, $currentPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        // Update user info
        $updateStmt = $conn->prepare("UPDATE users SET username = ?, email = ?, whatsapp = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $name, $email, $whatsapp, $userId);

        if ($updateStmt->execute()) {
            $success = "Profile updated successfully!";
            // Update session variables
            $_SESSION['username'] = $name;
            $_SESSION['email'] = $email;
            // Insert a message in messages table
    $msgContent = "Your profile information has been updated successfully.";
    $isRead = 0; // unread

    $msgStmt = $conn->prepare("INSERT INTO messages (user_id, content, is_read, created_at) VALUES (?, ?, ?, NOW())");
    $msgStmt->bind_param("isi", $userId, $msgContent, $isRead);
    $msgStmt->execute();
    $msgStmt->close();
        } else {
            $error = "Update failed. Please try again.";
        }
    } else {
        $error = "Incorrect current password.";
    }
}

// Fetch current user info to prefill the form
$stmt = $conn->prepare("SELECT username, email, whatsapp FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

?>


<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm"> <!-- modal-sm for smaller width -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="settingsModalLabel">Settings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Display messages -->
        <?php if ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
          <div class="mb-3">
            <label for="username" class="form-label">Your Name</label>
            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label for="whatsapp" class="form-label">WhatsApp Number</label>
            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password to save changes" required>
          </div>
          <button type="submit" name="update_settings" class="btn btn-success w-100">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>


<?php if (!empty($success) || !empty($error)): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    var settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
    settingsModal.show();
  });
</script>
<?php endif; ?>

<?php

include 'db.php'; // your DB connection

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: reguser.php');
    exit;
}

$modalMessage = '';
$modalMessageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Simple validations
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $modalMessage = 'All fields are required.';
        $modalMessageClass = 'text-danger';
    } elseif ($new_password !== $confirm_password) {
        $modalMessage = 'New password and confirm password do not match.';
        $modalMessageClass = 'text-danger';
    } else {
        // Fetch user's current password hash from DB
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Assuming passwords are stored as MD5 (not recommended for production)
            if (md5($current_password) === $row['password']) {
                // Update with new password
                $new_password_hash = md5($new_password);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $new_password_hash, $user_id);
                if ($update->execute()) {
                    $modalMessage = 'Password updated successfully.';
                    $modalMessageClass = 'text-success';
                } else {
                    $modalMessage = 'Failed to update password. Please try again.';
                    $modalMessageClass = 'text-danger';
                }
            } else {
                $modalMessage = 'Current password is incorrect.';
                $modalMessageClass = 'text-danger';
            }
        } else {
            $modalMessage = 'User not found.';
            $modalMessageClass = 'text-danger';
        }
    }

    // Keep modal open after submit
    $showModal = true;
} else {
    $showModal = false;
}

?>



<!-- Modal -->
<div class="modal fade <?= $showModal ? 'show' : '' ?>" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="<?= $showModal ? 'false' : 'true' ?>" style="<?= $showModal ? 'display:block;' : '' ?>">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="hideModal()"></button>
        </div>
        <div class="modal-body">

          <?php if ($modalMessage): ?>
            <div class="<?= $modalMessageClass ?> mb-3"><?= htmlspecialchars($modalMessage) ?></div>
          <?php endif; ?>

          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="current_password" name="current_password" required>
              <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                Show
              </button>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="new_password" name="new_password" required>
              <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                Show
              </button>
            </div>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                Show
              </button>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" name="change_password" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="hideModal()">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>



<script>
  // Show/hide password toggle buttons
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
      const inputId = button.getAttribute('data-target');
      const input = document.getElementById(inputId);
      if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
      } else {
        input.type = 'password';
        button.textContent = 'Show';
      }
    });
  });

  // Forcing modal to stay open if there was a message after submit
  <?php if ($showModal): ?>
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
  <?php endif; ?>

  // Close modal helper to remove inline styles when closing
  function hideModal() {
    const modal = document.getElementById('changePasswordModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }
</script>

<!-- JS to toggle the submenu -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const switchAccountToggle = document.getElementById('switchAccountDropdown');
    const submenu = switchAccountToggle.nextElementSibling;

    switchAccountToggle.addEventListener('click', function(event) {
      event.preventDefault();
      submenu.classList.toggle('show');

      const expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !expanded);
    });

    document.addEventListener('click', function(event) {
      if (!switchAccountToggle.contains(event.target) && !submenu.contains(event.target)) {
        submenu.classList.remove('show');
        switchAccountToggle.setAttribute('aria-expanded', 'false');
      }
    });
  });
</script>


</body>
</html>

<?php

include 'db.php';  // Your database connection

// Redirect if not logged in as organizer
if (!isset($_SESSION['organizer_email'])) {
    header("Location: index.php");
    exit();
}

$organizerEmail = $_SESSION['organizer_email'];

// Get name from users table using email
$organizerName = 'N/A';
if ($stmt = $conn->prepare("SELECT username FROM users WHERE email = ? LIMIT 1")) {
    $stmt->bind_param("s", $organizerEmail);
    $stmt->execute();
    $stmt->bind_result($name);
    if ($stmt->fetch()) {
        $organizerName = htmlspecialchars($name);
    }
    $stmt->close();
}

// Get other organizer info from session or default
$organizerDegree = htmlspecialchars($_SESSION['organizer_degree'] ?? 'N/A');
$organizerExperience = htmlspecialchars($_SESSION['organizer_experience'] ?? 'N/A');
?>





</body>
</html>
