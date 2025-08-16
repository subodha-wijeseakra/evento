user-select: none;


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
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">


  
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
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm px-3" style="position: fixed; top: 0; width: 100%; z-index: 1030;">
  <div class="container-fluid">
    <!-- Left: Logo -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="Assests/Images/evento-logo.jpg" alt="Logo" height="40" class="me-2">
    </a>

    <!-- Toggler for mobile (uses Bootstrap collapse) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Center: Nav Links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <!-- Mobile Sign In/Sign Up buttons -->
      <style>
      @media (max-width: 991.98px){
        .next-mobile-wrap { display:flex; justify-content:center; gap:10px; align-items:center; padding: .5rem 0; }
      }
      .next-mobile-btn {
        display:inline-flex;
        align-items:center;
        gap:.5rem;
        background:linear-gradient(180deg,#111827,#0f172a);
        color:#fff;
        border-radius:999px;
        padding:.45rem .9rem;
        border:0;
        font-weight:700;
        font-size:.95rem;
        box-shadow:0 8px 24px rgba(2,6,23,0.12);
        text-decoration:none;
      }
      .next-mobile-outline {
        display:inline-flex;
        align-items:center;
        gap:.5rem;
        background:transparent;
        color:#0f172a;
        border-radius:999px;
        padding:.45rem .9rem;
        border:1px solid rgba(15,23,42,0.08);
        font-weight:700;
        font-size:.95rem;
        text-decoration:none;
      }
      .next-mobile-btn:active,
      .next-mobile-outline:active { transform: translateY(1px); }
      </style>

      <div class="d-lg-none text-center mb-3 next-mobile-wrap">
        <a href="#" class="next-mobile-outline" data-bs-toggle="modal" data-bs-target="#signInModal" role="button">Sign In</a>
        <a href="#" class="next-mobile-btn" data-bs-toggle="modal" data-bs-target="#signUpModal" role="button">Sign Up</a>
      </div>

      <ul class="navbar-nav mx-auto text-center">
        <li class="nav-item">
          <a class="nav-link active" href="index.php" onclick="setActive(this)">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#customCarouselTrack" onclick="setActive(this)">Gallery</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" style="cursor:pointer;" onclick="openAboutUsModal()">About Us</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#faq" onclick="setActive(this)">FAQs</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#contact" onclick="setActive(this)">Contact Us</a>
        </li>
      </ul>

      <div class="d-none d-lg-flex align-items-center gap-2 ms-auto">
        <style>
          /* Next.js-like pill CTAs (scoped) */
          .next-cta-btn {
            border-radius: 999px;
            padding: .45rem .95rem;
            font-weight: 700;
            font-size: .95rem;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease;
            box-shadow: 0 6px 18px rgba(2,6,23,0.06);
            border: 1px solid rgba(15,23,42,0.06);
          }
          .next-ghost {
            background: transparent;
            color: #0f172a;
          }
          .next-primary {
            background: linear-gradient(90deg, #0b1220 0%, #111827 100%);
            color: #ffffff;
            border: 0;
            box-shadow: 0 10px 30px rgba(2,6,23,0.12);
          }
          .next-cta-btn:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(2,6,23,0.12); }
          .next-cta-btn:active { transform: translateY(0); }
          .next-cta-btn:focus { outline: none; box-shadow: 0 0 0 6px rgba(99,102,241,0.08); }
          @media (prefers-reduced-motion: reduce) {
            .next-cta-btn { transition: none; transform: none; }
          }
        </style>

        <button type="button"
                class="next-cta-btn next-ghost"
                data-bs-toggle="modal"
                data-bs-target="#signInModal"
                aria-label="Open Sign In">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="opacity:.9">
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
          Sign In
        </button>

        <button type="button"
                class="next-cta-btn next-primary"
                data-bs-toggle="modal"
                data-bs-target="#signUpModal"
                aria-label="Open Sign Up">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="opacity:.96; filter: drop-shadow(0 2px 4px rgba(0,0,0,.08))">
            <path d="M12 5v14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
          Sign Up
        </button>
      </div>
    </div>
  </div>
</nav>

<!-- About Modal — Next.js-like UI -->
<div id="aboutUsModal" aria-hidden="true" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; background:rgba(15,23,42,0.6); backdrop-filter: blur(4px);">
  <div role="dialog" aria-modal="true" aria-labelledby="aboutTitle" class="next-card">
    <button type="button" class="next-close" aria-label="Close about dialog">✕</button>

    <header class="next-header">
      <div class="brand">
       
        <div>
          <h2 id="aboutTitle" class="brand-title">About Evento</h2>
          <div class="next-badge">v1.0 • University Project</div>
        </div>
      </div>
    </header>

    <main class="next-body">
      <p class="about-text">
        Evento is a modern event management system developed by our team of passionate innovators to streamline and enhance how events are organized within the university.
        Designed with efficiency and accessibility in mind, Evento offers advanced tools for event scheduling, participant tracking, and real-time updates.
        By combining intuitive design with powerful functionality, we aim to make event management faster, smarter, and more engaging for students, organizers, and administrators alike.
      </p>

      <section class="tiles">
        <article class="tile">
          <img src="Assests/Images/male.jpg" alt="R.S.C Wijesekara" class="dev-image">
          <h3 class="dev-name">R.S.C Wijesekara</h3>
          <p class="dev-enroll">UWU/ICT/22/012</p>
          <div class="social-links">
            <a href="https://github.com" target="_blank" rel="noopener" aria-label="R.S.C GitHub"><i class="fab fa-github"></i></a>
            <a href="mailto:john@example.com" aria-label="R.S.C Email"><i class="fas fa-envelope"></i></a>
            <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="R.S.C LinkedIn"><i class="fab fa-linkedin"></i></a>
          </div>
        </article>

        <article class="tile">
          <img src="Assests/Images/female.png" alt="T.A.S Wickramarathna" class="dev-image">
          <h3 class="dev-name">T.A.S Wickramarathna</h3>
          <p class="dev-enroll">UWU/ICT/22/050</p>
          <div class="social-links">
            <a href="https://github.com" target="_blank" rel="noopener" aria-label="T.A.S GitHub"><i class="fab fa-github"></i></a>
            <a href="mailto:jane@example.com" aria-label="T.A.S Email"><i class="fas fa-envelope"></i></a>
            <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="T.A.S LinkedIn"><i class="fab fa-linkedin"></i></a>
          </div>
        </article>

        <article class="tile">
          <img src="Assests/Images/male.jpg" alt="N. Chanuuj" class="dev-image">
          <h3 class="dev-name">N. Chanuuj</h3>
          <p class="dev-enroll">UWU/ICT/22/095</p>
          <div class="social-links">
            <a href="https://github.com" target="_blank" rel="noopener" aria-label="N. Chanuuj GitHub"><i class="fab fa-github"></i></a>
            <a href="mailto:alex@example.com" aria-label="N. Chanuuj Email"><i class="fas fa-envelope"></i></a>
            <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="N. Chanuuj LinkedIn"><i class="fab fa-linkedin"></i></a>
          </div>
        </article>
      </section>
    </main>

    <footer class="next-footer">
      <small>Built with care • Group 15</small>
      <button type="button" class="btn-ghost" onclick="closeAboutUsModal()">Close</button>
    </footer>
  </div>

  <style>
    /* Next.js-like card (scoped) */
    .next-card {
      --card-bg: #ffffff;
      width: 90%;
      max-width: 60%;
      border-radius: 14px;
      padding: 0;
      box-shadow: 0 10px 30px rgba(2,6,23,0.12), 0 2px 6px rgba(2,6,23,0.06);
      background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(250,250,250,0.98));
      font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      animation: liftUp .24s ease;
      position: relative;
    }

    @keyframes liftUp {
      from { transform: translateY(8px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .next-close {
      position: absolute;
      right: 12px;
      top: 12px;
      background: transparent;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: #374151;
      padding: 6px;
      border-radius: 8px;
    }
    .next-close:hover { background: rgba(15,23,42,0.06); }

    .next-header { padding: 20px 24px; border-bottom: 1px solid #f3f4f6; display:flex; align-items:center; gap:14px; }
    .brand { display:flex; gap:14px; align-items:center; }
    .brand-logo { width:44px; height:44px; border-radius:8px; object-fit:cover; box-shadow: 0 6px 18px rgba(2,6,23,0.06); }
    .brand-title { margin:0; font-size:2rem; font-weight:700; color:#0f172a; }
    .next-badge { margin-top:6px; display:inline-block; font-size:12px; color:#6b7280; background:rgba(99,102,241,0.06); padding:6px 8px; border-radius:8px; }

    .next-body { padding: 20px 24px; display:block; }
    .about-text {
      font-size:15px;
      color:#374151;
      line-height:1.6;
      margin:0 0 18px 0;
      text-align:justify;    /* keep justified on small screens */
      width:100%;
      box-sizing: border-box;
    }

    /* On wider screens make paragraph 90% width and center aligned */
    @media (min-width: 900px) {
      .about-text {
        width: 90%;
        margin: 0 auto 18px;
        text-align: center;
      }
    }

    .tiles { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; }
    .tile { background: linear-gradient(180deg, #fff, #fbfbfd); border:1px solid #f3f4f6; padding:14px; border-radius:10px; text-align:center; }
    .dev-image { width:72px; height:72px; border-radius:999px; object-fit:cover; display:block; margin:0 auto 10px auto; box-shadow: 0 8px 20px rgba(2,6,23,0.06); }
    .dev-name { margin:0; font-size:15px; font-weight:700; color:#0f172a; }
    .dev-enroll { margin:6px 0 10px 0; color:#6b7280; font-size:13px; }

    .social-links a { margin:0 6px; color:#6b7280; font-size:16px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; transition: all .12s ease; }
    .social-links a:hover { background: rgba(99,102,241,0.08); color:#4f46e5; transform:translateY(-3px); }

    .next-footer { padding:14px 24px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f3f4f6; background:transparent; }
    .btn-ghost { background:transparent; border:1px solid #e6e9ee; padding:8px 12px; border-radius:8px; cursor:pointer; color:#0f172a; }
    .btn-ghost:hover { background:#f8fafc; }

    /* Responsive */
    @media (max-width: 900px) {
      .tiles { grid-template-columns: repeat(2, 1fr); }
      .next-card { max-width: 92%; width: 94%; }
    }
    @media (max-width: 520px) {
      .tiles { grid-template-columns: 1fr; gap:10px; }
      .brand-title { font-size:0.98rem; }
      .dev-image { width:64px; height:64px; }
    }
  </style>
</div>

<script>
  // Controlled open/close for accessibility; keeps same width and behavior
  function openAboutUsModal() {
    const modal = document.getElementById('aboutUsModal');
    if (!modal) return;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    // focus trap: focus close button
    const closeBtn = modal.querySelector('.next-close');
    if (closeBtn) closeBtn.focus();
    document.addEventListener('keydown', escCloseAbout);
  }

  function closeAboutUsModal() {
    const modal = document.getElementById('aboutUsModal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.removeEventListener('keydown', escCloseAbout);
  }

  function escCloseAbout(e) {
    if (e.key === 'Escape') closeAboutUsModal();
  }

  // Click outside to close
  (function () {
    const modal = document.getElementById('aboutUsModal');
    if (!modal) return;
    modal.addEventListener('click', function (ev) {
      if (ev.target === modal) closeAboutUsModal();
    });
    const closeBtn = modal.querySelector('.next-close');
    if (closeBtn) closeBtn.addEventListener('click', closeAboutUsModal);
  })();
</script>



<!-- Slideshow Only Visible on Wide Screens -->
<div class="slideshow-container next-slideshow" aria-roledescription="carousel">
  <!-- Slide 1 -->
  <div class="slide active" style="background-image: url('Assests/Images/1.png');">
    <div class="slide-content">
      <h1 style="font-weight: 650; font-size: 2.5rem; text-transform: none; letter-spacing: 0;">Akurata Rukulak 2025</h1>
      <p>Enrich the school life with innovative learning experiences.</p>
    </div>
  </div>

  <!-- Slide 2 -->
  <div class="slide" style="background-image: url('Assests/Images/2.png');">
    <div class="slide-content">
      <h1 style="font-weight: 650; font-size: 2.5rem; text-transform: none; letter-spacing: 0;">Handawaka 2025</h1>
      <p>Explore vibrant cityscapes and urban experiences around the world.</p>
    </div>
  </div>

  <!-- Slide 3 -->
  <div class="slide" style="background-image: url('Assests/Images/3.jpg');">
    <div class="slide-content">
      <h1 style="font-weight: 650; font-size: 2.5rem; text-transform: none; letter-spacing: 0;">Widhu Nimsara 2025</h1>
      <p>Explore vibrant cityscapes and experiences around the world.</p>
    </div>
  </div>

  <!-- Slide Controls (kept placement) -->
  <div class="slide-control left" onclick="changeSlide(-1)" role="button" aria-label="Previous slide"></div>
  <div class="slide-control right" onclick="changeSlide(1)" role="button" aria-label="Next slide"></div>

  <!-- Pagination dots (visual only) -->
  <div class="slide-dots" aria-hidden="true"></div>
</div>

<style>
/* Next.js-like full-width hero slideshow (scoped) */
.next-slideshow {
  width: 100%;
  height: min(72vh, 760px);
  min-height: 420px;
  position: relative;
  overflow: hidden;
  display: block;
  background: #0b1020;
  font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  border-bottom-left-radius: 6px;
  border-bottom-right-radius: 6px;
}

/* Each slide covers full area */
.next-slideshow .slide {
  position: absolute;
  inset: 0;
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
  opacity: 0;
  transform: scale(1.02);
  transition: opacity 700ms cubic-bezier(.2,.9,.3,1), transform 900ms ease;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding-left: 6%;
}

/* Active slide visible */
.next-slideshow .slide.active {
  opacity: 1;
  transform: scale(1);
  z-index: 1;
}

/* subtle dark gradient overlay for readable text */
.next-slideshow .slide::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(4,6,12,0.55) 0%, rgba(4,6,12,0.25) 40%, rgba(4,6,12,0.55) 100%);
  pointer-events: none;
  transition: opacity .6s ease;
}

/* Slide content styled to look like Next.js hero card */
.next-slideshow .slide-content {
  position: relative;
  z-index: 2;
  color: #fff;
  max-width: 56ch;
  padding: 36px 28px;
  background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(2,6,23,0.5), 0 2px 6px rgba(2,6,23,0.2);
  backdrop-filter: blur(6px) saturate(130%);
  border: 1px solid rgba(255,255,255,0.06);
}

/* Heading and paragraph style */
.next-slideshow .slide-content h1 {
  margin: 0 0 8px;
  font-size: clamp(1.8rem, 3.6vw, 2.5rem);
  line-height: 1.05;
  color: #f8fafc;
  letter-spacing: -0.01em;
}
.next-slideshow .slide-content p {
  margin: 0;
  color: rgba(255,255,255,0.85);
  font-size: 1.02rem;
}

/* Controls — subtle Next.js-like chevrons in rounded glass buttons */
.next-slideshow .slide-control {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 56px;
  height: 56px;
  border-radius: 999px;
  background: rgba(15,23,42,0.5);
  backdrop-filter: blur(6px);
  box-shadow: 0 8px 20px rgba(2,6,23,0.35);
  border: 1px solid rgba(255,255,255,0.06);
  cursor: pointer;
  z-index: 5;
}
.next-slideshow .slide-control.left { left: 22px; }
.next-slideshow .slide-control.right { right: 22px; }

/* add chevrons using linear-gradient mask to avoid extra markup */
.next-slideshow .slide-control.left::after,
.next-slideshow .slide-control.right::after {
  content: "";
  position: absolute;
  inset: 0;
  margin: auto;
  width: 18px;
  height: 18px;
  background-repeat: no-repeat;
  background-position: center;
  background-size: 18px;
  opacity: 0.95;
  filter: drop-shadow(0 2px 6px rgba(2,6,23,0.6));
}
.next-slideshow .slide-control.left::after {
  background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%23ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>');
}
.next-slideshow .slide-control.right::after {
  background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%23ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>');
}

/* Hover states */
.next-slideshow .slide-control:hover {
  transform: translateY(-50%) scale(1.02);
  background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
}

/* Dots */
.next-slideshow .slide-dots {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  bottom: 18px;
  display: flex;
  gap: 8px;
  z-index: 8;
}
.next-slideshow .slide-dots button {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  border: 0;
  background: rgba(255,255,255,0.32);
  cursor: pointer;
  transition: transform .2s ease, background .2s ease;
}
.next-slideshow .slide-dots button.active {
  background: linear-gradient(90deg,#7c3aed,#06b6d4);
  transform: scale(1.2);
}

/* Responsive: hide slideshow on small screens (keeps element placement) */
@media (max-width: 920px) {
  .next-slideshow { display: none; }
}
</style>

<script>
/* Small scoped slideshow controller — non-intrusive */
(function () {
  const container = document.querySelector('.next-slideshow');
  if (!container) return;

  const slides = Array.from(container.querySelectorAll('.slide'));
  const dotsContainer = container.querySelector('.slide-dots');
  let idx = slides.findIndex(s => s.classList.contains('active'));
  if (idx < 0) idx = 0;
  // build dots
  slides.forEach((_, i) => {
    const btn = document.createElement('button');
    btn.setAttribute('aria-label', 'Go to slide ' + (i + 1));
    btn.addEventListener('click', () => goTo(i));
    dotsContainer.appendChild(btn);
  });

  const dots = Array.from(dotsContainer.children);
  function refresh() {
    slides.forEach((s, i) => s.classList.toggle('active', i === idx));
    dots.forEach((d, i) => d.classList.toggle('active', i === idx));
  }
  refresh();

  // expose changeSlide globally (keeps onclick usage)
  window.changeSlide = function (delta) {
    idx = (idx + delta + slides.length) % slides.length;
    refresh();
    resetTimer();
  };

  window.goTo = function (i) {
    idx = i % slides.length;
    refresh();
    resetTimer();
  };

  // auto-advance
  let timer = null;
  function startTimer() {
    timer = setInterval(() => {
      idx = (idx + 1) % slides.length;
      refresh();
    }, 6000);
  }
  function resetTimer() {
    if (timer) { clearInterval(timer); startTimer(); }
  }
  startTimer();

  // keyboard accessibility
  window.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft') changeSlide(-1);
    if (e.key === 'ArrowRight') changeSlide(1);
  });
})();
</script>

<div class="next-meet-evento-section">
  <div class="nx-container">
    <div class="nx-grid">
      <div class="nx-left">
        <h2 class="nx-title">Meet the Evento</h2>

        <p class="nx-desc">
          Your ultimate companion for organizing university events with style and precision. Built to replace outdated processes,
          Evento empowers you to create, manage, and track events effortlessly  from club activities to large university gatherings.
          Bring everything into one sleek, easy-to-use space and transform event management into a smooth, enjoyable experience.
        </p>

        <div class="nx-cta">
        <button class="nx-btn get-started-btn" type="button" data-bs-toggle="modal" data-bs-target="#signUpModal" aria-label="Get Started">Get Started</button>

        <style>
        /* Scoped fix: keep the CTA above nearby content and avoid overlap */
        .nx-cta { position: relative; z-index: 1; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: .25rem; }

        /* Custom Get Started styling to prevent overlap */
        .nx-cta .get-started-btn {
          position: relative;
          z-index: 30;              /* ensure it sits above neighboring elements */
          min-width: 140px;
          padding: 10px 16px;
          border-radius: 10px;
          border: 0;
          background: linear-gradient(90deg,#0b1220 0%, #111827 100%);
          color: #fff;
          box-shadow: 0 10px 30px rgba(2,6,23,0.12);
          white-space: nowrap;
          transform: translateZ(0); /* create its own stacking context */
        }

        /* On smaller screens avoid horizontal overlap by stacking controls */
        @media (max-width: 920px) {
          .nx-cta { gap: 10px; }
          .nx-cta .get-started-btn {
            width: 100%;
            box-sizing: border-box;
            order: 2;
          }
          .nx-cta .nx-link {
            width: 100%;
            order: 3;
            text-align: center;
          }
        }
        </style>
          <a class="nx-link" href="#customCarouselTrack">Explore Gallery</a>
        </div>

        <ul class="nx-features" aria-hidden="false">
          <li><i class="fa fa-calendar-check"></i><span>Smart scheduling</span></li>
          <li><i class="fa fa-users"></i><span>Attendee management</span></li>
          <li><i class="fa fa-bell"></i><span>Realtime notifications</span></li>
        </ul>
      </div>

      <div class="nx-right" aria-hidden="true">
        <div class="nx-preview-card">
          <img src="Assests/Images/1.png" alt="Evento preview" class="nx-preview-img">
          <div class="nx-preview-badge">v1.0 • University Project</div>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Scoped Next.js-like component styling */
    .next-meet-evento-section { padding: 48px 0; }
    /* Default: mobile / tablet */
    .nx-container { width: 94%; max-width: 1100px; margin: 0 auto; padding: 0 20px; box-sizing: border-box; }
    /* Widescreen: use 80% of viewport and keep centered */
    @media (min-width: 1200px) {
      .nx-container { width: 80%; max-width: none; margin: 0 auto; }
    }

    .nx-grid { display: grid; grid-template-columns: 1fr 420px; gap: 28px; align-items: center; }

    .nx-title { font-size: 2rem; margin: 0 0 12px; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; }
    .nx-brand {
      background: linear-gradient(90deg,#6d28d9,#0ea5a4 60%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-weight: 800;
    }

    .nx-desc { font-size: 1.05rem; color: #374151; line-height: 1.6; margin: 0 0 18px; text-align: justify; text-justify: inter-word; }
    .nx-cta { display:flex; gap:12px; align-items:center; margin-bottom: 18px; flex-wrap:wrap; }
    .nx-btn {
      background: linear-gradient(90deg,#0b1220 0%, #111827 100%);
      color:#fff; border:0; padding:10px 16px; border-radius:10px; font-weight:700; box-shadow: 0 8px 30px rgba(2,6,23,0.12);
    }
    .nx-btn:active { transform: translateY(1px); }
    .nx-link { color:#6d28d9; font-weight:600; text-decoration:none; padding:10px 12px; border-radius:8px; }
    .nx-link:hover { text-decoration:underline; }

    .nx-features { list-style:none; padding:0; margin:6px 0 0; display:flex; gap:14px; flex-wrap:wrap; }
    .nx-features li { display:flex; gap:10px; align-items:center; color:#6b7280; font-weight:600; background: rgba(15,23,42,0.03); padding:8px 12px; border-radius:10px; font-size:0.92rem; }
    .nx-features i { color:#0f172a; width:18px; text-align:center; }

    .nx-preview-card { background: linear-gradient(180deg, rgba(255,255,255,0.8), rgba(248,250,252,0.9)); border-radius:14px; padding:14px; box-shadow: 0 18px 40px rgba(2,6,23,0.08); position:relative; display:flex; align-items:center; justify-content:center; min-height:220px; overflow:hidden; }
    .nx-preview-img { width:100%; height:100%; object-fit:cover; border-radius:10px; filter: contrast(0.98) saturate(1.05); }
    .nx-preview-badge { position:absolute; left:14px; top:14px; background: rgba(15,23,42,0.9); color:#fff; padding:6px 10px; border-radius:10px; font-size:12px; font-weight:700; }

    /* Responsive */
    @media (max-width: 920px) {
      .nx-grid { grid-template-columns: 1fr; gap:20px; }
      .nx-right { order: -1; }
      .nx-preview-card { min-height:160px; }
    }
  </style>
</div>
 
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
<script>
  // Safely attach handler only if element exists (prevents console errors)
  (function() {
    const btn = document.getElementById('navbarCloseBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
      const navbarCollapse = document.getElementById('navbarNav');
      const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
      if (bsCollapse) {
        bsCollapse.hide();
      }
    });
  })();
</script>
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
    <div class="modal-content custom-modal next-modal" style="border-radius:12px; overflow:hidden; border:0;">
      <style>
        /* Next.js-like minimalist styling (scoped for this modal) */
        .next-modal { font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
        .next-modal .modal-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:0; background:transparent; }
        .next-modal .brand {
          display:flex; align-items:center; gap:.6rem; font-weight:700; font-size:1.05rem;
        }
        .next-modal .brand .logo {
          width:36px; height:36px; border-radius:8px; object-fit:cover; box-shadow:0 1px 3px rgba(15,23,42,0.06);
        }
        .next-badge {
          background:#111827; color:#fff; font-size:12px; padding:6px 8px; border-radius:8px; font-weight:600;
          display:inline-flex; align-items:center; gap:.4rem;
        }

        .next-modal .modal-body { padding:1.25rem 1.5rem 1.5rem; background:#fff; }
        .next-input {
          border:1px solid #e6e9ee !important;
          border-radius:8px !important;
          padding:.75rem .9rem !important;
          box-shadow:inset 0 1px 2px rgba(16,24,40,0.03);
          transition: box-shadow .15s ease, border-color .15s ease;
        }
        .next-input:focus {
          outline: none;
          border-color: #7c3aed;
          box-shadow: 0 0 0 4px rgba(124,58,237,0.08);
        }
        .next-label { font-size: .875rem; color:#111827; margin-bottom:.35rem; display:block; font-weight:600; }
        .next-note { font-size:.82rem; color:#6b7280; margin-bottom: .75rem; }

        .next-btn {
          background: linear-gradient(90deg,#111827 0%, #0f172a 100%);
          border: none;
          color: #fff;
          font-weight:700;
          padding:.6rem .9rem;
          border-radius:10px;
          box-shadow: 0 6px 18px rgba(2,6,23,0.12);
        }
        .next-divider { height:1px; background:#f3f4f6; margin: .85rem 0; border-radius:2px; }

        .next-footer { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:.85rem; font-size:.875rem; color:#6b7280; }
        .next-link { color:#6d28d9; font-weight:600; text-decoration:none; }
        .alert-inline { margin-bottom: .75rem; border-radius:8px; padding:.6rem .9rem; }
      </style>

     

      <div class="modal-body">
        <form method="POST" action="">
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-inline"><?= $error ?></div>
          <?php endif; ?>

          <label for="signinEmail" class="next-label">Email address</label>
          <input type="email" class="form-control next-input mb-3" id="signinEmail" placeholder="you@company.com" name="email" required>

          <label for="signinPassword" class="next-label">Password</label>
          <input type="password" class="form-control next-input" id="signinPassword" placeholder="••••••••" name="password" required>

          <div class="next-divider"></div>

          <button type="submit" class="btn next-btn w-100" name="login">Sign In</button>

          <div class="next-footer">
            <small class="next-note">Don't have an account?</small>
            <a href="#" class="next-link" data-bs-toggle="modal" data-bs-target="#signUpModal" data-bs-dismiss="modal">Create account</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

             

<!-- Sign Up Modal (responsive improvements) -->
<div class="modal fade" id="signUpModal" tabindex="-1" aria-labelledby="signUpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-custom-width" role="document">
    <div class="modal-content next-modal-card">
      <style>
        /* Base layout */
        .modal-custom-width { width: 40vw; max-width: 680px; min-width: 320px; }
        .next-modal-card {
          border: 0;
          border-radius: 14px;
          overflow: hidden;
          font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
          background: linear-gradient(180deg,#ffffff,#fbfbfd);
          box-shadow: 0 18px 40px rgba(2,6,23,0.14), 0 6px 18px rgba(2,6,23,0.06);
          box-sizing: border-box;
        }

        .next-modal-card .modal-header {
          border-bottom: 0;
          padding: 16px 20px;
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:12px;
          background:transparent;
        }
        .next-modal-card .modal-title {
          margin:0;
          font-weight:700;
          font-size:1.05rem;
          color:#0f172a;
        }
        .next-close-btn {
          background:transparent;
          border:0;
          font-size:18px;
          cursor:pointer;
          color:#6b7280;
          padding:6px;
          border-radius:8px;
        }
        .next-close-btn:hover { background: rgba(15,23,42,0.06); color:#111827; }

        .next-modal-card .modal-body {
          padding: 18px 20px 22px;
        }

        /* Inputs / controls */
        .next-input {
          border:1px solid #eef2f7;
          border-radius:10px;
          padding:.75rem .9rem;
          box-shadow: inset 0 1px 2px rgba(16,24,40,0.03);
          width:100%;
          box-sizing: border-box;
        }
        .next-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 6px rgba(124,58,237,0.06); }

        .next-label { display:block; font-weight:600; margin-bottom:.35rem; color:#111827; font-size:.9rem; }
        .next-note { font-size:.85rem; color:#6b7280; margin-top:10px; display:block; text-align:center; }

        .next-primary-btn {
          background: linear-gradient(90deg,#0b1220 0%, #111827 100%);
          color:#fff;
          border:0;
          border-radius:10px;
          padding:.62rem .85rem;
          font-weight:700;
          width:100%;
          box-sizing: border-box;
        }

        .alert-inline { margin-bottom: 12px; border-radius:8px; padding:.6rem .9rem; }

        /* Make sure form elements stretch and have consistent spacing */
        .next-modal-card .form-control, .next-modal-card .btn { width:100%; box-sizing:border-box; }
        .next-modal-card .mb-3 { margin-bottom: 12px; }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 900px) {
          .modal-custom-width { width: 92vw; max-width: 92vw; min-width: auto; }
          .next-modal-card { border-radius: 12px; }
          .next-modal-card .modal-header { padding: 12px 14px; }
          .next-modal-card .modal-body { padding: 12px 14px 16px; }
          .next-input { padding:.6rem .7rem; border-radius:8px; }
          .next-primary-btn { padding:.55rem .7rem; border-radius:8px; }
          .next-modal-card .modal-title { font-size:1rem; }
          /* Slightly smaller logo on compact screens */
          .next-modal-card .brand-logo-small { width:32px; height:32px; }
        }

        /* Very small screens: maximize usable area inside popup */
        @media (max-width: 480px) {
          .modal-custom-width { width: 96vw; max-width: 96vw; margin: 0 2vw; }
          .next-modal-card { box-shadow: 0 8px 20px rgba(2,6,23,0.08); }
          .next-modal-card .modal-body { padding: 10px 12px 12px; }
          .next-modal-card .modal-header { padding: 10px 12px; gap:8px; }
          .next-input, .next-primary-btn { font-size: 0.95rem; }
          .next-note { font-size:0.82rem; }
        }

        /* Accessibility: ensure focus ring on close button */
        .next-close-btn:focus { outline: 3px solid rgba(124,58,237,0.12); border-radius:8px; }
      </style>

      <div class="modal-header">
        <div style="display:flex;align-items:center;gap:12px;">
        <h5 class="modal-title" id="signUpModalLabel">Create your account</h5>
        </div>
        <button type="button" class="next-close-btn" data-bs-dismiss="modal" aria-label="Close">✕</button>
      </div>

      <div class="modal-body">
        <form method="POST" action="">
          <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-inline"><?= htmlspecialchars($success) ?></div>
          <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger alert-inline"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <div class="mb-3">
            <label for="signupName" class="next-label">Full name</label>
            <input type="text" class="form-control next-input" id="signupName" placeholder="Enter your name" name="username" required aria-required="true">
          </div>

          <div class="mb-3">
            <label for="signupEmail" class="next-label">Email address</label>
            <input type="email" class="form-control next-input" id="signupEmail" placeholder="you@company.com" name="email" required aria-required="true">
          </div>

          <div class="mb-3">
            <label for="signupPassword" class="next-label">Password</label>
            <input type="password" class="form-control next-input" id="signupPassword" placeholder="Create a password" name="password" required aria-required="true">
          </div>

          <button type="submit" class="btn next-primary-btn mb-2" name="register">Create Account</button>

          <span class="next-note">By creating an account you agree to our <a href="#" style="color:#6d28d9;text-decoration:none;font-weight:600">Terms</a> and <a href="#" style="color:#6d28d9;text-decoration:none;font-weight:600">Privacy</a>.</span>
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




<script>
  function openAboutUsModal() {
    document.getElementById("aboutUsModal").style.display = "flex";
  }

  function closeAboutUsModal() {
    document.getElementById("aboutUsModal").style.display = "none";
  }

  // Close modal when clicking outside the content
  window.addEventListener("click", function(e) {
    const modal = document.getElementById("aboutUsModal");
    const modalContent = modal.querySelector("div");
    if (e.target === modal) {
      closeAboutUsModal();
    }
  });
</script>
<?php endif; ?>





</body>
</html>
