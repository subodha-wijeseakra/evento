
<?php
session_start();
include 'db.php'; // Database connection file

$success = '';
$error = '';

// ✅ Handle Login
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password'])); // MD5 hash

    // Prepare statement to validate user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    // If credentials match
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Store user info in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        // ✅ Redirect to dashboard
        header("Location: reguser.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}

// ✅ Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password'])); // MD5 hash

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $error = "This email is already registered.";
    } else {
        // Insert new user into database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $success = "Account created successfully. You can now log in.";
            $triggerDelayedMessages = true; // Let frontend know to trigger AJAX
            $_SESSION['new_user_id'] = $user_id; // Store new user ID for AJAX

            // ✅ Insert 3 welcome messages for the new user
            $messages = [
                "Welcome to our platform!",
                "Explore your dashboard and features.",
                "Need help? Contact support anytime."
            ];

            $msgStmt = $conn->prepare("INSERT INTO messages (user_id, content, is_read) VALUES (?, ?, 0)");
            foreach ($messages as $msg) {
                $msgStmt->bind_param("is", $user_id, $msg);
                $msgStmt->execute();
            }
        } else {
            $error = "Error creating your account. Please try again.";
        }
    }
}

?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Evento</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="Assests/css/navbar.css" rel="stylesheet">
  <link href="Assests/css/slideshow.css" rel="stylesheet">
  <link href="Assests/css/carousel.css" rel="stylesheet">
  <link href="Assests/css/tcontainer.css" rel="stylesheet">
  <link href="Assests/css/aucontainer.css" rel="stylesheet">
  <link href="Assests/css/fqcontainer.css" rel="stylesheet">
  <link href="Assests/css/cucontainer.css" rel="stylesheet">
  <link href="Assests/css/jumpup.css" rel="stylesheet">
  <link href="Assests/css/footer.css" rel="stylesheet">
  <script src="Assests/js/jumpup.js" defer></script>
  <script src="Assests/js/slideshow.js" defer></script>
  <script src="Assests/js/carousel.js" defer></script>
  <script src="Assests/js/navbar.js" defer></script>

  
  <style>
  </style>

</head>

<body style="padding-top: 40px;">
  <?php if (!empty($triggerDelayedMessages)): ?>
<script>
  setTimeout(() => {
    fetch('insert_messages.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ trigger: true })
    })
    .then(res => res.json())
    .then(data => {
      console.log('Messages inserted:', data);
    });
  }, 5000);
</script>
<?php endif; ?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm px-4"  style="position: fixed; top: 0; width: 100%; z-index: 1030;">
  <!-- Left: Logo -->
  <a class="navbar-brand d-flex align-items-center" href="index.php">
    <img src="Assests/Images/evento-logo.jpg" alt="Logo" height="40" class="me-2">
  </a>
<!-- Toggler for mobile -->
<button id="customNavbarToggler" class="navbar-toggler" type="button">
  <span class="navbar-toggler-icon"></span>
</button>

  <!-- Center: Nav Links -->
  <div class="collapse navbar-collapse justify-content-center" id="navbarNav">



   <!-- Mobile Sign In/Sign Up buttons -->
  <div class="d-lg-none text-center mb-3">
    <a href="#" class="btn btn-outline-primary btn-sm nav-btn me-2" data-bs-toggle="modal" data-bs-target="#signInModal">Sign In</a>
   <a href="#" class="btn btn-outline-primary btn-sm nav-btn" data-bs-toggle="modal" data-bs-target="#signUpModal" >Sign Up</a>
  </div>
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link active" href="index.php" onclick="setActive(this)"><span>Home</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#customCarouselTrack" onclick="setActive(this)"><span>Gallery</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#aboutus" onclick="setActive(this)"><span>About Us</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#faq" onclick="setActive(this)"><span>FAQs</span></a>
      </li>
         <li class="nav-item">
        <a class="nav-link" href="#contact" onclick="setActive(this)"><span>Contact Us</span></a>
      </li>
    </ul>
 
<!-- Desktop view Sign In/Up -->
  <div class="d-none d-lg-flex align-items-center gap-2 ms-auto">
    <a href="#" class="btn btn-outline-primary btn-sm nav-btn" data-bs-toggle="modal" data-bs-target="#signInModal">Sign In</a>
     <a href="#" class="btn btn-outline-primary btn-sm nav-btn" data-bs-toggle="modal" data-bs-target="#signUpModal" style="background-color: #007bff; color: white;">Sign Up</a>
    
  </div>
    
  </div>
</nav>



<!-- Slideshow Only Visible on Wide Screens -->
<div class="slideshow-container">
  <!-- Slide 1 -->
  <div class="slide active" style="background-image: url('Assests/Images/1.png');">
    <div class="slide-content">
      <h1>Welcome to Nature</h1>
      <p>Experience the beauty of the outdoors with our immersive slideshow.</p>
    </div>
  </div>

  <!-- Slide 2 -->
  <div class="slide" style="background-image: url('Assests/Images/2.png');">
    <div class="slide-content">
      <h1>Urban Adventures</h1>
      <p>Explore vibrant cityscapes and urban experiences around the world.</p>
    </div>
  </div>

  <!-- Slide 3 -->
  <div class="slide" style="background-image: url('Assests/Images/3.jpg');">
    <div class="slide-content">
      <h1>Escape to Paradise</h1>
      <p>Find peace and relaxation on stunning beaches under blue skies.</p>
    </div>
  </div>

  <!-- Slide Controls -->
  <div class="slide-control left" onclick="changeSlide(-1)">&#10094;</div>
  <div class="slide-control right" onclick="changeSlide(1)">&#10095;</div>
</div>

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



<div class="content-container" id="aboutus">
  <p class="content-paragraph">
    Welcome to our gallery — a visual showcase of our best moments, creativity, and hard work. Here, you'll find a curated collection of images from recent events, projects, and behind-the-scenes glimpses that tell our story. Whether it’s vibrant snapshots of our team in action, highlights from successful milestones, or moments that reflect our values and energy, this space is designed to give you a closer look at who we are and what we do. Use the carousel to easily navigate through different visuals and experience the essence of our journey in motion. Each image captures more than just a moment — it represents our passion, dedication, and commitment to excellence. Feel free to explore, engage, and get inspired as you scroll through our gallery. We’ll keep updating it with fresh memories, so there’s always something new to discover. Thank you for being part of our story!
  </p>
</div>







<!--dddddddddddddddddddddddddddddddddddd-->
<!-- About Us Section -->
<section class="about-us-container">
  <div class="about-us-inner">
    <img src="Assests/Images/1.png" alt="About Us Image" class="about-image">
    <div class="about-text">
      <h2>About Us</h2>
      <p>
        We are a passionate team committed to delivering quality, creativity, and innovation.
        Our goal is to create exceptional experiences through every project we undertake.
        With a focus on reliability and excellence, we work closely with our clients to bring
        ideas to life. From strategy to execution, we ensure every step is handled with care
        and dedication.
      </p>
    </div>
  </div>
</section>



<!--dddddddddddddddddddddddddddddddddddd-->


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

<!--dddddddddddddddddddddddddddddddddddd-->



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
          <li><a href="#">Policies</a></li>
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
  function toggleFAQ(element) {
    const item = element.parentElement;
    item.classList.toggle('active');
  }
</script>











<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>




<!-- Optional: Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>











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
        
        <form method="POST" action="">
          <div class="mb-3">
             <?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

            <label for="signinEmail" class="form-label">Email address</label>
            <input type="email" class="form-control" id="signinEmail" placeholder="Enter email"  name="email">
          </div>
          <div class="mb-3">
            <label for="signinPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="signinPassword" placeholder="Enter password" name="password">
          </div>
          <button type="submit" class="btn btn-primary w-100" name="login">Sign In</button>
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
        <form method="POST" action="">
          <div class="mb-3">
            <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
            <input type="text" class="form-control" id="signupName" placeholder="Enter your name" name="username">
          </div>
          <div class="mb-3">
            <label for="signupEmail" class="form-label">Email address</label>
            <input type="email" class="form-control" id="signupEmail" placeholder="Enter email" name="email">
          </div>
          <div class="mb-3">
            <label for="signupPassword" class="form-label">Password</label>
            <input type="password" class="form-control" id="signupPassword" placeholder="Enter password" name="password">
          </div>
          <button type="submit" class="btn btn-success w-100" name="register">Create Account</button>
        </form>
      </div>
    </div>
  </div>
</div>


<?php if (!empty($success) || !empty($error)): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      <?php if (!empty($success)): ?>
        // Show Sign In modal after successful registration
        var loginModal = new bootstrap.Modal(document.getElementById('signInModal'));
        loginModal.show();

        // Insert message into Sign In modal dynamically
        const signinBody = document.querySelector("#signInModal .modal-body");
        if (signinBody) {
          const alertBox = document.createElement("div");
          alertBox.className = "alert alert-success";
          alertBox.textContent = "You can log in now!";
          signinBody.prepend(alertBox);
        }
      <?php else: ?>
        // If error occurred, keep Sign Up modal open
        var signupModal = new bootstrap.Modal(document.getElementById('signInModal'));
        signupModal.show();
      <?php endif; ?>
    });
  </script>
<?php endif; ?>





</body>
</html>
