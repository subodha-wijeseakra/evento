<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location:index.php");
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
            $stmt = $conn->prepare("INSERT INTO organizer (email, degree_program, experience, description) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $email, $degree, $experience, $description);
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
    $_SESSION['organizer_email'] = $email; // Add this line
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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
</style>


</head>
<body style="padding-top:40px;">


<!-- Bootstrap CSS & Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm px-3" style="position: fixed; top: 0; width: 100%; z-index: 1030;">
  
  <!-- Logo -->
  <a class="navbar-brand" href="reguser.php">
    <img src="Assests/Images/evento-logo.jpg" alt="Logo" height="40">
  </a>

  <!-- Right-side: Icons + Toggler (in one row) -->
<div class="d-flex align-items-center order-lg-3" style="margin-left: auto; gap: 0.5rem;">

<style>
    .notifications-list {
        max-height: 250px; /* Adjust this value to show approximately 5 messages */
        overflow-y: auto;
    }
    .btn-icon-only {
        border: none;
        background-color: transparent;
        padding: 0.25rem 0.5rem; /* Adjust padding as needed */
    }
    .btn-icon-only i {
        color: inherit; /* Use the parent button's color */
    }
</style>

<div class="dropdown me-2">
    <a class="btn btn-light position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell fs-5"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="color:white;">
                <?= $unread_count ?>
            </span>
        <?php endif; ?>
    </a>

    <ul class="dropdown-menu dropdown-menu-end p-2 shadow notifications-list" aria-labelledby="notifDropdown" style="width: 500px; max-width: 90vw; right:0; left:auto;">
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
                            <button class="btn btn-sm btn-icon-only btn-read text-success" data-id="<?= $msg['id'] ?>" title="Mark as Read">
                                <i class="fas fa-eye"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-icon-only btn-delete text-danger" data-id="<?= $msg['id'] ?>" title="Delete">
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
    $stmt = $conn->prepare("SELECT id FROM organizer WHERE email = ? AND status = 'approved'");
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

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser" style="width: 250px; max-width: 90vw; overflow:auto; right:0; left:auto;">
    <li><a class="dropdown-item" href="#">Hey, <?= htmlspecialchars($username) ?></a></li>
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">Settings</a></li>
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</a></li>

    <li class="dropdown-submenu">
      <a class="dropdown-item dropdown-toggle text-primary" href="#" id="switchAccountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        Switch Account
      </a>
      <ul class="dropdown-menu" aria-labelledby="switchAccountDropdown" style="margin: 10px; padding: 2px; min-width: 200px;">
        <?php if ($isOrganizer): ?>
          <li>
            <a href="organizer_dashboard.php" class="dropdown-item" style="color: black;">
              Organizer Profile
            </a>
          </li>
        <?php else: ?>
          <li>
            <div class="dropdown-item text-muted">
             No Organizer Profile
            </div>
          </li>
        <?php endif; ?>
      </ul>
    </li>

    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
  </ul>
</div>



<!-- JS to enable nested dropdown toggling -->
<script>
document.querySelectorAll('.dropdown-menu .dropdown-toggle').forEach(function(element){
  element.addEventListener('click', function (e) {
    e.preventDefault(); // Prevent link navigation
    e.stopPropagation(); // Stop event bubbling

    if (!this.nextElementSibling.classList.contains('show')) {
      // Close other open submenus
      this.closest('.dropdown-menu').querySelectorAll('.show').forEach(function(submenu){
        submenu.classList.remove('show');
      });
    }
    // Toggle current submenu
    this.nextElementSibling.classList.toggle('show');
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







<div class="content-container mb-6">
  <h2 class="text-3xl font-bold text-center mb-6">Event Calendar</h2>
</div>

<!-- Filter + Search -->
<form method="GET" class="filter-bar">
  <input type="text" name="search" placeholder="Search events..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
  <select name="sort">
    <option value="">Sort By</option>
    <option value="date_asc" <?php if(isset($_GET['sort']) && $_GET['sort']=="date_asc") echo "selected"; ?>>Date ↑</option>
    <option value="date_desc" <?php if(isset($_GET['sort']) && $_GET['sort']=="date_desc") echo "selected"; ?>>Date ↓</option>
    <option value="fee_low" <?php if(isset($_GET['sort']) && $_GET['sort']=="fee_low") echo "selected"; ?>>Fee Low → High</option>
    <option value="fee_high" <?php if(isset($_GET['sort']) && $_GET['sort']=="fee_high") echo "selected"; ?>>Fee High → Low</option>
  </select>
  <button type="submit">Search</button>
</form>

<?php
include 'fetch_courses.php';

// Search filter
if(isset($_GET['search']) && $_GET['search'] !== ""){
  $search = strtolower($_GET['search']);
  $courses = array_filter($courses, function($course) use ($search) {
    return strpos(strtolower($course['title']), $search) !== false ||
           strpos(strtolower($course['department']), $search) !== false ||
           strpos(strtolower($course['description']), $search) !== false;
  });
}

// Sorting
if(isset($_GET['sort'])){
  if($_GET['sort'] == "date_asc"){
    usort($courses, fn($a,$b)=>strtotime($a['date'])-strtotime($b['date']));
  }
  if($_GET['sort'] == "date_desc"){
    usort($courses, fn($a,$b)=>strtotime($b['date'])-strtotime($a['date']));
  }
  if($_GET['sort'] == "fee_low"){
    usort($courses, fn($a,$b)=>$a['fee']-$b['fee']);
  }
  if($_GET['sort'] == "fee_high"){
    usort($courses, fn($a,$b)=>$b['fee']-$a['fee']);
  }
}

// Pagination
$perPage = 6;
$total = count($courses);
$totalPages = ceil($total / $perPage);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
if($page > $totalPages) $page = $totalPages;

$start = ($page-1) * $perPage;
$paginatedCourses = array_slice($courses, $start, $perPage);
?>

<div class="events-grid">
  <?php
  foreach ($paginatedCourses as $course) {
    echo '<div class="event-card">';
    echo '<div class="event-date">'.date("M d, Y", strtotime($course['date'])).'</div>';
    echo '<h3 class="event-title">'.$course['title'].'</h3>';
    echo '<p class="event-dept">'.$course['department'].'</p>';
    echo '<p class="event-desc">'.$course['description'].'</p>';
    echo '<div class="event-footer">';
    echo '<span class="event-fee">$'.$course['fee'].'</span>';
    echo '</div>';
    echo '</div>';
  }
  ?>
</div>

<!-- Pagination -->
<div class="pagination">
  <?php if($page > 1): ?>
    <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>">&laquo; Prev</a>
  <?php endif; ?>

  <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$i])); ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
  <?php endfor; ?>

  <?php if($page < $totalPages): ?>
    <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>">Next &raquo;</a>
  <?php endif; ?>
</div>


<style>
/* Filter bar */
.filter-bar {
  display: flex;
  justify-content: center;
  gap: 10px;
  max-width: 900px;
  margin: 0 auto 20px;
}
.filter-bar input, .filter-bar select {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #d1d5db;
}
.filter-bar button {
  background: #2563eb;
  color: #fff;
  padding: 8px 16px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: 0.2s;
}
.filter-bar button:hover {
  background: #2563eb;
}

/* Grid */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px;
}

/* Card */
.event-card {
  background: #fff;
  border-radius: 14px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  transition: all 0.25s ease;
}
.event-card:hover {
  border-color: #2563eb;
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.05);
}
.event-date {
  font-size: 0.85rem;
  font-weight: 600;
  color: #2563eb;
  margin-bottom: 8px;
}
.event-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 6px 0 4px;
  color: #111827;
}
.event-dept {
  font-size: 0.9rem;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 10px;
}
.event-desc {
  font-size: 0.95rem;
  color: #374151;
  margin-bottom: 15px;
  line-height: 1.45;
}
.event-footer {
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #f3f4f6;
  padding-top: 8px;
}
.event-fee {
  font-weight: 600;
  color: #2563eb;
  font-size: 0.95rem;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin: 20px 0;
}
.pagination a {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  color: #111827;
  text-decoration: none;
}
.pagination a.active {
  background: #4f46e5;
  color: #fff;
  border-color: #4f46e5;
}
.pagination a:hover {
  background: #f3f4f6;
}
</style>






<section id="nr-about-us">
  <div id="nr-about-inner">
    <div id="nr-about-text">
      <h2 id="nr-small-title">Success stories</h2>
      <h2 id="nr-small-title">Akurata Rukulak 2024 yako</h2>
      <p id="nr-about-paragraph">
        We are a passionate team committed to delivering quality, creativity, and innovation.
        Our goal is to create exceptional experiences through every project we undertake. With
        a focus on reliability and excellence, we work closely with our clients to bring ideas
        to life. From strategy to execution, we ensure every step is handled with care and
        dedication.
      </p>
    </div>
    <div id="nr-about-image-wrapper">
      <img src="Assests/Images/1.png" alt="About Us Image" id="nr-about-image" />
    </div>
  </div>
</section>

<style>
/* Container */
#nr-about-us {
  padding: 5rem 2rem;
  background-color: #f5f7fa;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Inner flex layout */
#nr-about-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  gap: 3rem;
  flex-wrap: wrap;
}

/* Text content */
#nr-about-text {
  flex: 1 1 500px;
}

#nr-small-title {
  font-size: 2rem;
  color: #444;
  margin-bottom: 0.5rem;
  font-weight: 500;
  letter-spacing: 1px;
}

#nr-main-title {
  font-size: 2.8rem;
  color: #0070f3; /* Next.js blue */
  margin-bottom: 1.2rem;
  font-weight: 700;
}

#nr-about-paragraph {
  font-size: 1rem;
  line-height: 1.8;
  color: #555;
}

/* Image wrapper */
#nr-about-image-wrapper {
  flex: 1 1 450px;
  position: relative;
}

#nr-about-image {
  width: 100%;
  height: auto;
  object-fit: cover;
  border-radius: 15px;
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}

#nr-about-image:hover {
  transform: translateY(-8px);
  box-shadow: 0 18px 30px rgba(0, 0, 0, 0.18);
}

/* Responsive */
@media (max-width: 768px) {
  #nr-about-inner {
    flex-direction: column;
    text-align: center;
  }

  #nr-about-image-wrapper {
    margin-top: 2rem;
  }

  #nr-main-title {
    font-size: 2.2rem;
  }
}
</style>

 










<section id="nr-organizer-us">
  <div id="nr-organizer-inner">
    <div id="nr-organizer-text">
      <h2 id="nr-org-small-title">Want to be an Organizer?</h2>
      <h2 id="nr-org-main-title">The master mind </h2>
      <p id="nr-org-paragraph">
        We are a passionate team committed to delivering quality, creativity, and innovation.
        Our goal is to create exceptional experiences through every project we undertake.
        With a focus on reliability and excellence, we work closely with our clients to bring
        ideas to life. From strategy to execution, we ensure every step is handled with care
        and dedication.
      </p>
      <a href="#" id="nr-org-btn" data-bs-toggle="modal" data-bs-target="#organizerModal">Register</a>
    </div>
    <div id="nr-organizer-image-wrapper">
      <img src="Assests/Images/organizer.png" alt="Organizer Image" id="nr-organizer-image">
    </div>
  </div>
</section>

<style>
/* Container */
#nr-organizer-us {
  padding: 5rem 2rem;
  background-color: #fff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Inner flex layout */
#nr-organizer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  gap: 3rem;
  flex-wrap: wrap;
}

/* Text content */
#nr-organizer-text {
  flex: 1 1 500px;
}

#nr-org-small-title {
  font-size: 2rem;
  color: #444;
  margin-bottom: 0.5rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Dynamic color-changing main title */
#nr-org-main-title {
  font-size: 2.8rem;
  font-weight: 700;
  margin-bottom: 1.2rem;
  background: linear-gradient(90deg, #0070f3, #ff4d6d, #ff8a71);
  background-size: 300% 300%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: nr-colorShift 5s ease infinite;
}

@keyframes nr-colorShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

#nr-org-paragraph {
  font-size: 1rem;
  line-height: 1.8;
  color: #555;
  margin-bottom: 1.5rem;
}

/* Button */
#nr-org-btn {
  display: inline-block;
  padding: 0.6rem 1.4rem;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  border-radius: 8px;
  background-color: #007bff;
  color: white;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#nr-org-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 15px rgba(0, 123, 255, 0.3);
}

/* Image wrapper */
#nr-organizer-image-wrapper {
  flex: 1 1 450px;
  position: relative;
}

#nr-organizer-image {
  width: 100%;
  height: auto;
  object-fit: cover;
  border-radius: 15px;
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}

#nr-organizer-image:hover {
  transform: translateY(-8px);
  box-shadow: 0 18px 30px rgba(0, 0, 0, 0.18);
}

/* Responsive */
@media (max-width: 768px) {
  #nr-organizer-inner {
    flex-direction: column;
    text-align: center;
  }

  #nr-organizer-image-wrapper {
    margin-top: 2rem;
  }

  #nr-org-main-title {
    font-size: 2.2rem;
  }
}
</style>

 


<!-- Reusable Content Component -->
<div class="content-container">
  <h2 class="content-title">Gallery</h2>
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
<div class="modal-dialog modal-dialog-centered modal-lg" style="display:flex; justify-content:center; align-items:center;">
  <div class="modal-content" style="max-width:600px; width:100%; margin:auto; border-radius:16px; box-shadow:0 30px 60px rgba(0,0,0,0.15); overflow:hidden; font-family:'Inter', sans-serif; background:#fff;">

    <!-- Header -->
    <div class="modal-header" style="background-color:#f8fafc; color:#0f172a; border-bottom:none; padding:1.5rem 2rem;">
      <h5 class="modal-title" style="font-size:1.5rem; font-weight:700; letter-spacing:0.5px;">Organizer Registration</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1); transition: transform 0.2s ease;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"></button>
    </div>

    <!-- Body -->
    <div class="modal-body" style="padding:2rem; background-color:#f8fafc;">

      <!-- Success/Error Message -->
      <?php if (!empty($organizer_success)): ?>
        <div style="padding:1rem; border-radius:12px; margin-bottom:1rem; font-size:0.95rem; background-color:#d1fae5; color:#065f46; font-weight:500; box-shadow:0 2px 6px rgba(0,0,0,0.05);"><?= $organizer_success ?></div>
      <?php elseif (!empty($organizer_error)): ?>
        <div style="padding:1rem; border-radius:12px; margin-bottom:1rem; font-size:0.95rem; background-color:#fee2e2; color:#991b1b; font-weight:500; box-shadow:0 2px 6px rgba(0,0,0,0.05);"><?= $organizer_error ?></div>
      <?php endif; ?>

      <form method="POST" action="" autocomplete="off" style="display:flex; flex-direction:column; gap:1.2rem;">

        <!-- Auto-filled Email (read-only) -->
        <div style="display:flex; flex-direction:column;">
          <label for="orgEmail" style="font-weight:600; color:#0f172a; margin-bottom:0.25rem;">Email</label>
          <input type="email" name="org_email" id="orgEmail" value="<?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '' ?>" readonly style="padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:12px; font-size:0.95rem; color:#111827; background:#e5e7eb; cursor:not-allowed; transition: all 0.3s ease;">
        </div>

        <!-- Degree Program -->
        <div style="display:flex; flex-direction:column;">
          <label for="orgDegree" style="font-weight:600; color:#0f172a; margin-bottom:0.25rem;">Degree Program</label>
          <select name="org_degree" id="orgDegree" required style="padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:12px; font-size:0.95rem; color:#111827; background:#fff; transition: all 0.3s ease;">
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

        <div style="display:flex; flex-direction:column;">
          <label for="orgExperience" style="font-weight:600; color:#0f172a; margin-bottom:0.25rem;">Experience</label>
          <input type="text" name="org_experience" id="orgExperience" required style="padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:12px; font-size:0.95rem; color:#111827; background:#fff; transition: all 0.3s ease;">
        </div>

        <div style="display:flex; flex-direction:column;">
          <label for="orgDescription" style="font-weight:600; color:#0f172a; margin-bottom:0.25rem;">Description</label>
          <textarea name="org_description" id="orgDescription" rows="3" required style="padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:12px; font-size:0.95rem; color:#111827; background:#fff; transition: all 0.3s ease;"></textarea>
        </div>

        <div style="display:grid; margin-top:1rem;">
          <button type="submit" name="organizer_submit" style="padding:0.75rem 1.5rem; font-size:1rem; font-weight:600; border:none; border-radius:12px; background-color:#0f172a; color:white; cursor:pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 15px rgba(0,0,0,0.25)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">Submit</button>
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
    header('Location: index.php');
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
