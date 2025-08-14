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

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser" style="width: 250px;">
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





<div id="fadeSlideshow" class="position-relative overflow-hidden">
  <!-- Google Fonts + Bootstrap -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Slides -->
  <div class="fade-slide active" style="background-image: url('Assests/Images/1.png');">
    <div class="container h-100 d-flex align-items-end pb-5" style="max-width: 80%;">
      <div class="text-white">
        <h1 class="fw-semibold">Sahasak Nimawum 2025</h1>
        <p class="mb-3">A celebration of innovation and culture. Join thousands of students, professionals and creators!</p>
        <a href="#" class="btn btn-primary me-2">Buy Ticket</a>
        <a href="#" class="btn btn-outline-light">Learn More</a>
      </div>
    </div>
  </div>

  <div class="fade-slide" style="background-image: url('Assests/Images/2.png');">
    <div class="container h-100 d-flex align-items-end pb-5" style="max-width: 80%;">
      <div class="text-white">
        <h1 class="fw-semibold">Connect & Celebrate</h1>
        <p class="mb-3">Experience inspiring performances and connect with creators nationwide.</p>
        <a href="#" class="btn btn-primary me-2">Buy Ticket</a>
        <a href="#" class="btn btn-outline-light">Learn More</a>
      </div>
    </div>
  </div>

  <!-- Controls -->
  <button id="prevFade" class="btn btn-primary position-absolute top-50 start-0 translate-middle-y ms-3 z-3">&#8592;</button>
  <button id="nextFade" class="btn btn-primary position-absolute top-50 end-0 translate-middle-y me-3 z-3">&#8594;</button>
</div>


  

</div>

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



<section class="about-us-container">
  <div class="about-us-inner">
    
    <div class="about-text">
      <h2>Success stories</h2>
      <h2>Akurata Rukulak 2024</h2>
      <p>
        We are a passionate team committed to delivering quality, creativity, and innovation.
        Our goal is to create exceptional experiences through every project we undertake.
        With a focus on reliability and excellence, we work closely with our clients to bring
        ideas to life. From strategy to execution, we ensure every step is handled with care
        and dedication.
      </p>
     
    </div>
     <img src="Assests/Images/1.png" alt="About Us Image" class="about-image">
  </div>
</section>
 






<hr class="responsive-stroke">



<section class="about-us-container">
  <div class="about-us-inner">
    
    <div class="about-text">
      <h2>Want to be an Organizer?</h2>
      <h2>Akurata Rukulak 2024</h2>
      <p>
        We are a passionate team committed to delivering quality, creativity, and innovation.
        Our goal is to create exceptional experiences through every project we undertake.
        With a focus on reliability and excellence, we work closely with our clients to bring
        ideas to life. From strategy to execution, we ensure every step is handled with care
        and dedication.
      </p>
     <a href="#" class="btn btn-outline-primary btn-sm nav-btn" data-bs-toggle="modal" data-bs-target="#organizerModal" style="background-color: #007bff; color: white;">Register</a>
    </div>
     <img src="Assests/Images/organizer.png" alt="About Us Image" class="about-image">
  </div>
</section>
 

<hr class="responsive-stroke">
<!-- Reusable Content Component -->
<div class="content-container">
  <h2 class="content-title">Gallery</h2>
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
