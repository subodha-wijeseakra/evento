<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
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
      --bg-light: #fff;
      --text-light: #6c757d;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: white;
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



<div class="event-wrapper">

  <!-- Left image / Right details layout -->
  <div class="event-card">
    <!-- Left Image -->
    <div class="event-image">
      <?php if (!empty($images)) : ?>
        <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="Event Image">
      <?php else: ?>
        <div class="no-image">No Image</div>
      <?php endif; ?>
    </div>

    <!-- Right Details -->
    <div class="event-details">
      <?php 
        $info_fields = [
          'Title' => 'title',
          'Date' => 'event_date',
          'Description' => 'description',
          'Status' => 'status'
        ];

        foreach ($info_fields as $label => $key) :
          if (!empty($event[$key])) :
      ?>
        <div class="info-item">
          <span class="label"><?php echo $label; ?>:</span>
          <span class="value"><?php echo htmlspecialchars($event[$key]); ?></span>
        </div>
      <?php 
          endif;
        endforeach; 
      ?>

      <?php if (!empty($campaigns)) : ?>
        <div class="info-item">
          <span class="label">Fundraising Campaigns:</span>
          <ul>
            <?php foreach ($campaigns as $c) : ?>
              <li><?php echo htmlspecialchars($c['campaign_name']); ?> - <?php echo htmlspecialchars($c['amount_raised']); ?>/<?php echo htmlspecialchars($c['amount_goal']); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="next-event-form">
        <button type="submit" name="next">Next Event</button>
      </form>
    </div>
  </div>
</div>

<style>
body {
  font-family: 'Inter', sans-serif;
  background: #fff;
  margin: 0;
  padding: 0;
}

.event-wrapper {
  width: 90%;
  max-width: 1000px;
  margin: 6rem auto 3rem auto; /* top margin to avoid navbar */
}

.event-card {
  display: flex;
  flex-direction: row;
  background: rgba(255,255,255,0.85);
  border-radius: 20px;
  border: 1px solid rgba(0,0,0,0.08); /* subtle border instead of heavy shadow */
  backdrop-filter: blur(10px);
  overflow: hidden;
  transition: transform 0.3s ease, border-color 0.3s ease;
}

.event-card:hover {
  transform: translateY(-3px);
  border-color: rgba(0,0,0,0.15);
}

/* Left Image */
.event-image {
  flex: 1;
  min-width: 300px;
  max-width: 400px;
  overflow: hidden;
  position: relative;
}

.event-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.event-card:hover .event-image img {
  transform: scale(1.03);
}

.no-image {
  width: 100%;
  height: 100%;
  display:flex;
  justify-content:center;
  align-items:center;
  color:#888;
  font-size:1.2rem;
  background:#e5e7eb;
}

/* Right Details */
.event-details {
  flex: 1;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1rem;
}

.info-item {
  padding: 1rem;
  background: rgba(255,255,255,0.6);
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.08); /* subtle border effect */
  display: flex;
  flex-direction: column;
}

.info-item .label {
  font-weight: 600;
  color: #0f172a;
  font-size: 1.05rem;
  margin-bottom: 0.3rem;
}

.info-item .value {
  color: #4b5563;
  font-size: 0.95rem;
}

.info-item ul {
  padding-left: 1rem;
  margin: 0;
  list-style-type: disc;
  color: #4b5563;
  font-size: 0.95rem;
}

/* Next Event Button */
.next-event-form {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
}

.next-event-form button {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  font-size: 1rem;
  background: #0f172a;
  color: white;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.next-event-form button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}

/* Responsive */
@media (max-width: 768px) {
  .event-card {
    flex-direction: column;
  }
  .event-image {
    min-width: 100%;
    max-width: 100%;
    height: 250px;
  }
  .event-details {
    padding: 1.5rem;
  }
}
</style>






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



<div class="modern-form-wrapper">

  <?php if (isset($success) && $success): ?>
    <div class="alert-success">
      Your event application was submitted successfully!
    </div>
  <?php endif; ?>

  <div class="modern-form-card">

    <!-- Left Image Panel -->
    <div class="form-image">
      <img src="Assests/Images/1.png" alt="Event Illustration">
    </div>

    <!-- Right Form Panel -->
    <div class="form-content">

      <!-- Progress Bar -->
      <div class="progress-bar">
        <div class="progress" style="width:25%"></div>
      </div>

      <!-- Step Form -->
      <form method="POST" enctype="multipart/form-data" id="eventForm">

        <!-- Step 1 -->
        <div class="step-content active" data-step="0">
          <h2>Basic Info</h2>
          <input type="text" name="title" placeholder="Event Title" required>
          <textarea name="description" rows="3" placeholder="Description" required></textarea>
          <input type="date" name="date" required>
          <div class="btn-group">
            <button type="button" class="btn-next" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Next</button>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="step-content" data-step="1">
          <h2>Proposal</h2>
          <input type="file" name="proposal" accept=".pdf,.doc,.docx" required>
          <div class="btn-group">
            <button type="button" class="btn-prev" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Back</button>
            <button type="button" class="btn-next" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Next</button>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="step-content" data-step="2">
          <h2>Gallery</h2>
          <input type="file" name="image0" accept="image/*">
          <input type="file" name="image1" accept="image/*">
          <input type="file" name="image2" accept="image/*">
          <div class="btn-group">
            <button type="button" class="btn-prev" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Back</button>
            <button type="button" class="btn-next" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Next</button>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="step-content" data-step="3">
          <h2>Publish</h2>
          <label><input type="checkbox" name="agree" required> I agree to the terms</label>
          <div class="btn-group">
            <button type="button" class="btn-prev" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Back</button>
            <button type="submit" name="submit_application" class="btn-submit" 
              style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1rem; background: #0f172a; color: white; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease;">Submit</button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
  margin: 0;
  padding: 0;
  min-height: 100%;
  color: #0f172a;
}

.modern-form-wrapper {
  width: 90%;
  max-width: 900px;
  margin: 6rem auto;
}

.alert-success {
  background: rgba(72, 187, 120, 0.2);
  color: #248a4b;
  border: 1px solid #1f6f40;
  padding: 14px 20px;
  border-radius: 12px;
  text-align: center;
  margin-bottom: 20px;
  font-weight: 500;
}

.modern-form-card {
  display: flex;
  border-radius: 20px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.form-image {
  flex: 1;
  overflow: hidden;
}

.form-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.form-image img:hover {
  transform: scale(1.05);
}

.form-content {
  flex: 1;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: rgba(0,0,0,0.08);
  border-radius: 4px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.progress {
  height: 100%;
  background: #0d6efd;
  width: 0%;
  transition: width 0.3s ease;
}

.step-content {
  display: none;
  flex-direction: column;
  gap: 1rem;
}

.step-content.active {
  display: flex;
}

.step-content h2 {
  margin:0;
  color:#0f172a;
}

input[type="text"], input[type="date"], input[type="file"], textarea {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.12);
  background: #ffffff;
  font-size: 14px;
  outline: none;
  transition: border 0.3s ease, box-shadow 0.3s ease;
}

input:focus, textarea:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 2px rgba(13,110,253,0.15);
}

label input[type="checkbox"] { margin-right: 8px; }

@media (max-width: 768px) {
  .modern-form-card { flex-direction: column; }
  .form-image { height: 200px; }
}
</style>

<script>
const steps = document.querySelectorAll('.step-content');
const nextButtons = document.querySelectorAll('.btn-next');
const prevButtons = document.querySelectorAll('.btn-prev');
const progress = document.querySelector('.progress');
let currentStep = 0;

function updateStep() {
  steps.forEach((s,i)=>s.classList.toggle('active',i===currentStep));
  progress.style.width = ((currentStep+1)/steps.length)*100 + '%';
}

nextButtons.forEach(btn => btn.addEventListener('click',()=>{if(currentStep<steps.length-1){currentStep++;updateStep();}}));
prevButtons.forEach(btn => btn.addEventListener('click',()=>{if(currentStep>0){currentStep--;updateStep();}}));
updateStep();
</script>


<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
  margin:0;
  padding:0;
}

/* Wrapper */
.modern-form-wrapper {
  width: 90%;
  max-width: 70%;
  margin: 3rem auto;
}

/* Success Alert */
.alert-success {
  background: rgba(72, 187, 120, 0.2);
  color: #248a4b;
  border: 1px solid #1f6f40;
  padding: 14px 20px;
  border-radius: 12px;
  text-align: center;
  margin-bottom: 20px;
  font-weight: 500;
}

/* Card */
.modern-form-card {
  display: flex;
  border-radius: 20px;
  overflow: hidden;
  backdrop-filter: blur(12px);
  background: rgba(255,255,255,0.75);
  border: 1px solid rgba(0,0,0,0.08);
}

/* Left Image */
.form-image {
  flex: 1;
  overflow: hidden;
}

.form-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.9);
  transition: transform 0.3s ease;
}

.form-image img:hover {
  transform: scale(1.05);
}

/* Right Form Panel */
.form-content {
  flex: 1;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Progress Bar */
.progress-bar {
  width: 100%;
  height: 6px;
  background: rgba(0,0,0,0.08);
  border-radius: 4px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.progress {
  height: 100%;
  background: #0d6efd;
  width: 0%;
  transition: width 0.3s ease;
}

/* Step Content */
.step-content {
  display: none;
  flex-direction: column;
  gap: 1rem;
}

.step-content.active {
  display: flex;
}

.step-content h2 {
  margin:0;
  color:#0f172a;
}

/* Inputs */
input[type="text"], input[type="date"], input[type="file"], textarea {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.12);
  background: rgba(255,255,255,0.7);
  font-size: 14px;
  outline: none;
  transition: border 0.3s ease, box-shadow 0.3s ease;
}

input:focus, textarea:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 2px rgba(13,110,253,0.15);
}

/* Buttons */
.btn-group {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 1rem;
}

.btn-next, .btn-prev, .btn-submit {
  padding: 10px 22px;
  border-radius: 20px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-next { background:#0d6efd; color:#fff; }
.btn-prev { background:#6c757d; color:#fff; }
.btn-submit { background:#198754; color:#fff; }

.btn-next:hover, .btn-prev:hover, .btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.12);
}

/* Checkbox */
label input[type="checkbox"] { margin-right: 8px; }

/* Responsive */
@media (max-width: 768px) {
  .modern-form-card { flex-direction: column; }
  .form-image { height: 200px; }
}
</style>

<script>
const steps = document.querySelectorAll('.step-content');
const nextButtons = document.querySelectorAll('.btn-next');
const prevButtons = document.querySelectorAll('.btn-prev');
const progress = document.querySelector('.progress');
let currentStep = 0;

function updateStep() {
  steps.forEach((s,i)=>s.classList.toggle('active',i===currentStep));
  progress.style.width = ((currentStep+1)/steps.length)*100 + '%';
}

nextButtons.forEach(btn => btn.addEventListener('click',()=>{if(currentStep<steps.length-1){currentStep++;updateStep();}}));
prevButtons.forEach(btn => btn.addEventListener('click',()=>{if(currentStep>0){currentStep--;updateStep();}}));
updateStep();
</script>





  







<!-- Reusable Content Component -->
<div class="content-container">
  <h2 class="content-title">Marketplace</h2>
</div>


<div class="nx-carousel-card" aria-roledescription="carousel" id="nxCarousel">
  <div class="nx-badge">Gallery • v1.0</div>

  <div class="nx-carousel-viewport">
    <div class="nx-carousel-track" id="nxTrack" role="list">
      <!-- Page 1 (first 3) -->
      <div class="nx-carousel-page" role="group" aria-roledescription="page" aria-label="1 of 2">
        <div class="nx-carousel-item"><img src="Assests/Images/1.png" alt="Slide 1"></div>
        <div class="nx-carousel-item"><img src="Assests/Images/3.jpg" alt="Slide 2"></div>
        <div class="nx-carousel-item"><img src="Assests/Images/2.png" alt="Slide 3"></div>
      </div>

      <!-- Page 2 (next 3) -->
      <div class="nx-carousel-page" role="group" aria-roledescription="page" aria-label="2 of 2">
        <div class="nx-carousel-item"><img src="Assests/Images/3.jpg" alt="Slide 4"></div>
        <div class="nx-carousel-item"><img src="Assests/Images/2.png" alt="Slide 5"></div>
        <div class="nx-carousel-item"><img src="Assests/Images/3.jpg" alt="Slide 6"></div>
      </div>
    </div>
  </div>

  <button class="nx-btn nx-prev" id="nxPrev" aria-label="Previous set">&larr;</button>
  <button class="nx-btn nx-next" id="nxNext" aria-label="Next set">&rarr;</button>

  <!-- Lightbox / Zoomed view -->
  <div id="nxLightbox" class="nx-lightbox" role="dialog" aria-hidden="true">
    <button class="nx-lightbox-close" aria-label="Close">&times;</button>
    <button class="nx-lightbox-nav nx-lightbox-prev" aria-label="Previous image">&#10094;</button>
    <div class="nx-lightbox-stage" tabindex="0">
      <img id="nxLightboxImg" src="" alt="Expanded image">
    </div>
    <button class="nx-lightbox-nav nx-lightbox-next" aria-label="Next image">&#10095;</button>
  </div>

  <style>
    /* Desktop width 80% (normal screens) */
    .nx-carousel-card {
      width: 80%;
      max-width: 80%;
      margin: 40px auto;
      padding: 20px;
      border-radius: 14px;
      position: relative;
      background: linear-gradient(180deg,#ffffff,#fbfbfd);
      box-shadow: 0 18px 40px rgba(2,6,23,0.08);
      border: 1px solid rgba(15,23,42,0.04);
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      overflow: visible;
    }

    .nx-badge {
      position: absolute;
      left: 18px;
      top: 14px;
      z-index: 5;
      background: rgba(15,23,42,0.92);
      color: #fff;
      padding: 6px 10px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 12px;
      box-shadow: 0 6px 18px rgba(2,6,23,0.08);
    }

    .nx-carousel-viewport {
      width: 100%;
      overflow: hidden;
      border-radius: 10px;
      margin: 6px 0 0 0;
    }

    .nx-carousel-track {
      display: flex;
      width: 200%;
      transition: transform 600ms cubic-bezier(.2,.9,.3,1);
      will-change: transform;
    }

    .nx-carousel-page {
      width: 50%;
      display: flex;
      gap: 14px;
      padding: 22px;
      box-sizing: border-box;
      align-items: stretch;
      justify-content: center;
    }

    .nx-carousel-item {
      flex: 1 1 calc((100% / 3) - 14px);
      min-width: 0;
      height: 260px;
      border-radius: 12px;
      overflow: hidden;
      background: linear-gradient(180deg,#fff,#f8fafc);
      box-shadow: 0 8px 24px rgba(2,6,23,0.06);
      border: 1px solid #f3f4f6;
      transition: transform .18s ease, box-shadow .18s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: zoom-in;
    }

    .nx-carousel-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      pointer-events: none; /* container handles clicks */
    }

    .nx-carousel-item:hover {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 0 18px 36px rgba(2,6,23,0.10);
    }

    /* Controls placed overlay left/right vertically centered */
    .nx-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 52px;
      height: 52px;
      border-radius: 999px;
      border: 0;
      background: rgba(255,255,255,0.9);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 18px;
      color: #0b1220;
      box-shadow: 0 10px 28px rgba(2,6,23,0.12);
      transition: transform .12s ease, background .12s ease;
      z-index: 7;
    }
    .nx-prev { left: 10px; }
    .nx-next { right: 10px; }
    .nx-btn:hover { transform: translateY(-50%) scale(1.04); background: #fff; }

    /* Lightbox styles */
    .nx-lightbox {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(4,6,12,0.75);
      z-index: 2000;
      padding: 40px 20px;
      box-sizing: border-box;
    }

    .nx-lightbox.open { display: flex; }

    .nx-lightbox-stage {
      max-width: 92%;
      max-height: 86%;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border-radius: 12px;
      backdrop-filter: blur(4px) saturate(120%);
    }

    .nx-lightbox-stage img {
      max-width: 100%;
      max-height: 100%;
      border-radius: 8px;
      transform-origin: center center;
      animation: lightboxIn .36s cubic-bezier(.22,.9,.32,1);
      box-shadow: 0 24px 60px rgba(2,6,23,0.6);
    }

    @keyframes lightboxIn {
      from { opacity: 0; transform: scale(.96); }
      to   { opacity: 1; transform: scale(1); }
    }

    .nx-lightbox-close {
      position: absolute;
      top: 22px;
      right: 22px;
      background: rgba(0,0,0,0.4);
      color: #fff;
      border: 0;
      width: 44px;
      height: 44px;
      border-radius: 999px;
      font-size: 22px;
      z-index: 2010;
      cursor: pointer;
      box-shadow: 0 8px 22px rgba(0,0,0,0.6);
    }

    .nx-lightbox-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.06);
      color: #fff;
      border: 0;
      width: 56px;
      height: 56px;
      border-radius: 999px;
      font-size: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2010;
      cursor: pointer;
      transition: transform .12s ease, background .12s ease;
    }
    .nx-lightbox-prev { left: 24px; }
    .nx-lightbox-next { right: 24px; }

    .nx-lightbox-nav:hover, .nx-lightbox-close:hover { transform: scale(1.05); background: rgba(255,255,255,0.12); }

    /* Responsive adjustments */
    @media (max-width: 920px) {
      .nx-carousel-card { width: 94%; padding: 12px; }
      .nx-carousel-item { height: 160px; }
      .nx-prev { left: 6px; }
      .nx-next { right: 6px; }
      .nx-lightbox { padding: 18px; }
      .nx-lightbox-nav { width: 44px; height: 44px; font-size: 22px; left: 10px; right: 10px; }
    }

    @media (max-width: 520px) {
      .nx-carousel-page { padding: 12px; gap: 8px; }
      .nx-carousel-item { height: 140px; }
      .nx-btn { width: 44px; height: 44px; font-size: 16px; }
    }
  </style>
</div>

<script>
  (function () {
    const track = document.getElementById('nxTrack');
    const prev = document.getElementById('nxPrev');
    const next = document.getElementById('nxNext');
    const carousel = document.getElementById('nxCarousel');

    let page = 0;
    const pages = 2;
    let interval = null;
    const AUTO_MS = 3800;

    function showPage(i, instant = false) {
      page = ((i % pages) + pages) % pages;
      if (instant) {
        track.style.transition = 'none';
        track.style.transform = 'translateX(' + (-50 * page) + '%)';
        void track.offsetWidth;
        track.style.transition = '';
      } else {
        track.style.transform = 'translateX(' + (-50 * page) + '%)';
      }
    }

    function nextPage() { showPage(page + 1); resetTimer(); }
    function prevPage() { showPage(page - 1); resetTimer(); }

    prev.addEventListener('click', prevPage);
    next.addEventListener('click', nextPage);

    function startTimer() {
      if (interval) return;
      interval = setInterval(() => showPage(page + 1), AUTO_MS);
    }
    function stopTimer() { if (interval) { clearInterval(interval); interval = null; } }
    function resetTimer() { stopTimer(); startTimer(); }

    carousel.addEventListener('mouseenter', stopTimer);
    carousel.addEventListener('mouseleave', startTimer);
    carousel.addEventListener('focusin', stopTimer);
    carousel.addEventListener('focusout', startTimer);

    document.addEventListener('keydown', (e) => {
      if (document.getElementById('nxLightbox')?.classList.contains('open')) {
        // lightbox handles keys (see below)
        return;
      }
      if (e.key === 'ArrowLeft') prevPage();
      if (e.key === 'ArrowRight') nextPage();
    });

    showPage(0, true);
    startTimer();

    // Lightbox behaviour
    const lightbox = document.getElementById('nxLightbox');
    const lightboxImg = document.getElementById('nxLightboxImg');
    const lightboxClose = lightbox.querySelector('.nx-lightbox-close');
    const lightboxPrev = lightbox.querySelector('.nx-lightbox-prev');
    const lightboxNext = lightbox.querySelector('.nx-lightbox-next');

    // collect all images in carousel in order
    const imgs = Array.from(document.querySelectorAll('#nxTrack .nx-carousel-item img'));
    const srcs = imgs.map(i => i.getAttribute('src'));
    let currentIndex = 0;

    // open on click
    imgs.forEach((img, idx) => {
      img.parentElement.addEventListener('click', (e) => {
        e.preventDefault();
        openLightbox(idx);
      });
      // keyboard accessibility
      img.parentElement.tabIndex = 0;
      img.parentElement.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter' || ev.key === ' ') openLightbox(idx);
      });
    });

    function openLightbox(idx) {
      currentIndex = idx;
      lightboxImg.src = srcs[currentIndex];
      lightbox.classList.add('open');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      lightbox.focus?.();
    }

    function closeLightbox() {
      lightbox.classList.remove('open');
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    function showLightboxIndex(i) {
      currentIndex = ((i % srcs.length) + srcs.length) % srcs.length;
      // small transition trick: fade out then set src
      lightboxImg.style.opacity = '0';
      setTimeout(() => {
        lightboxImg.src = srcs[currentIndex];
        lightboxImg.style.opacity = '1';
      }, 120);
    }

    lightboxClose.addEventListener('click', closeLightbox);
    lightboxPrev.addEventListener('click', () => showLightboxIndex(currentIndex - 1));
    lightboxNext.addEventListener('click', () => showLightboxIndex(currentIndex + 1));

    // close when clicking outside image
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox || e.target === lightbox.querySelector('.nx-lightbox-stage')) {
        closeLightbox();
      }
    });

    // keyboard for lightbox
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') showLightboxIndex(currentIndex - 1);
      if (e.key === 'ArrowRight') showLightboxIndex(currentIndex + 1);
    });

    // pause carousel autoplay when lightbox open
    const observer = new MutationObserver(() => {
      if (lightbox.classList.contains('open')) stopTimer();
      else startTimer();
    });
    observer.observe(lightbox, { attributes: true, attributeFilter: ['class'] });

    // expose small API
    window.nxCarousel = { showPage, nextPage, prevPage, openLightbox, closeLightbox };
  })();
</script>











<!-- Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">




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
