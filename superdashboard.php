<?php
session_start();
$mysqli = new mysqli("localhost","root","","evento");
if($mysqli->connect_error){ die("Connection failed: ".$mysqli->connect_error); }
// Redirect if not logged in
if(!isset($_SESSION['admin_id'])){
    header("Location: admin.php");
    exit;
}

// Grab session data
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'];

// Get total events count
$result = $mysqli->query("SELECT COUNT(*) AS total_events FROM event_applications");
$row = $result->fetch_assoc();
$total_events = $row['total_events'] ?? 0;

// Get total active campaigns (or events) from "ecalendar" table
$result_campaigns = $mysqli->query("SELECT COUNT(*) AS total_campaigns FROM ecalendar");
$row_campaigns = $result_campaigns->fetch_assoc();
$total_campaigns = $row_campaigns['total_campaigns'] ?? 0;

// SQL query to count the number of rows in the 'users' table
$sql = "SELECT COUNT(*) AS total_users FROM users";
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

// SQL query to count active organizers
// It joins the 'users' and 'organizer' tables on email and counts the matching rows.
$sql = "SELECT COUNT(*) AS total_organizers FROM users u JOIN organizer o ON u.email = o.email WHERE o.status = 'approved'";
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();
$total_organizers = $row['total_organizers'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dasboard</title>
<style>
/* Reset */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
body, html { height: 100%; background: linear-gradient(135deg,#ffffff,#f5f5f5); color: #0f172a; }

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 250px;
  height: 100%;
  background: #0f172a;
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.5rem 1rem;
  border-right: 2px solid rgba(255,255,255,0.1);
}

.sidebar h2 {
  font-size: 1.5rem;
  margin-bottom: 2rem;
}

.nav-links {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.nav-links a {
  color: white;
  text-decoration: none;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border: 1px solid transparent;
  transition: all 0.3s ease;
}

.nav-links a:hover {
  border: 1px solid rgba(255,255,255,0.3);
  background: rgba(255,255,255,0.05);
  transform: translateX(4px);
}

/* Header */
.header {
  position: fixed;
  top: 0;
  left: 250px;
  right: 0;
  height: 60px;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 1.5rem;
  border-bottom: 1px solid rgba(0,0,0,0.08);
  z-index: 100;
}

.profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.profile:hover img {
  transform: scale(1.1);
}

.profile img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #0f172a;
  transition: transform 0.3s ease;
}

/* Main content */
.main {
  margin-left: 250px;
  margin-top: 60px;
  padding: 2rem;
  min-height: calc(100vh - 60px);
}

/* Cards / Operations */
.card-container {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
  gap: 1.5rem;
}

.card {
  background: #ffffff;
  border-radius: 20px;
  border: 1px solid rgba(0,0,0,0.08);
  padding: 1.5rem;
  transition: transform 0.3s ease, border 0.3s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-4px);
  border: 1px solid #0d6efd;
}

/* Card text */
.card h3 {
  margin-bottom: 1rem;
  font-size: 1.2rem;
  color: #0f172a;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar { width: 200px; }
  .header { left: 200px; }
  .main { margin-left: 200px; }
}

@media (max-width: 500px) {
  .sidebar {
    position: relative;
    width: 100%;
    height: auto;
    flex-direction: row;
    justify-content: space-around;
    border-right: none;
    border-bottom: 2px solid rgba(0,0,0,0.08);
  }
  .header { left: 0; }
  .main { margin-left: 0; margin-top: 120px; }
}
/* Profile dropdown */
.profile {
  position: relative;
}

.profile-dropdown {
  position: absolute;
  top: 55px;
  right: 0;
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  display: none;
  flex-direction: column;
  min-width: 150px;
  z-index: 200;
}

.profile-dropdown a {
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: #0f172a;
  transition: all 0.2s ease;
}

.profile-dropdown a:hover {
  background: #f1f3f5;
  color: #0d6efd;
  transform: translateX(2px);
}

/* Show dropdown on active */
.profile.active .profile-dropdown {
  display: flex;
}

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div>
    <h2>Dashboard</h2>
    <div class="nav-links">
      <a href="#">Home</a>
      <a href="#events">Events</a>
      <a href="#organizers">Organizers</a>
      <a href="#users">Manage Users</a>
      <a href="#analytics">Analytics</a>
    </div>
  </div>
  <div style="font-size:0.8rem; text-align:center; padding-bottom:1rem;">created by Group 15</div>
</div>

<!-- Header -->
<div class="header">
  <div class="profile" id="profileMenu">
    <span><?= htmlspecialchars($admin_username) ?></span>
     <!-- Profile Icon instead of image -->
    <svg xmlns="http://www.w3.org/2000/svg" fill="#0f172a" viewBox="0 0 24 24" width="40" height="40" style="border-radius:50%; border:2px solid #0f172a; padding:4px;">
      <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
    </svg>
    
    <!-- Dropdown Menu -->
    <div class="profile-dropdown" id="profileDropdown">
      <a href="edit_profile.php">Edit Profile</a>
      <a href="adminlogout.php">Log Out</a>
    </div>
  </div>
</div>


<!-- Main Content -->
<div class="main">
  <h1>Welcome, <?= htmlspecialchars($admin_username) ?>!</h1>
  <p>Email: <?= htmlspecialchars($admin_email) ?></p>
<br><br>
  <div class="card-container">
    <div class="card">
      <h3>Total Events</h3>
  <p><?= htmlspecialchars($total_events) ?></p>
    </div>
    <div class="card">
   <h3>Active Organizers</h3>
<p><?= $total_organizers ?></p>
    </div>
    <div class="card">
     <h3>System Users</h3>
<p><?= $total_users ?></p>
    </div>
    <div class="card">
      <h3>Analytics</h3>
       <p><?= htmlspecialchars($total_campaigns) ?></p>
    </div>
  </div>

<!-- Include Approve Events using iframe -->
<div class="approve-events-wrapper" style="margin-top: 2.5rem;">
  <iframe src="manage_events.php" style="
      width: 100%; 
      border: none; 
      border-radius: 20px; 
      min-height: 600px; 
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  " id="events"></iframe>
</div>

<!-- Include Approve Events using iframe -->
<div class="approve-events-wrapper" style="margin-top: 2.5rem;">
  <iframe src="manage_organizer.php" style="
      width: 100%; 
      border: none; 
      border-radius: 20px; 
      min-height: 600px; 
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  " id="organizers"></iframe>
</div>

<!-- Include Approve Events using iframe -->
<div class="approve-events-wrapper" style="margin-top: 2.5rem;">
  <iframe src="manage_users.php" style="
      width: 100%; 
      border: none; 
      border-radius: 20px; 
      min-height: 800px; 
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  " id="users"></iframe>
</div>

<!-- Include Approve Events using iframe -->
<div class="approve-events-wrapper" style="margin-top: 2.5rem;">
  <iframe src="manage_reports.php" style="
      width: 100%; 
      border: none; 
      border-radius: 20px; 
      min-height: 800px; 
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  " id="analytics"></iframe>
</div>
  
</div>
<script>
const profile = document.getElementById('profileMenu');
const dropdown = document.getElementById('profileDropdown');

profile.addEventListener('click', () => {
  profile.classList.toggle('active');
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
  if(!profile.contains(e.target)){
    profile.classList.remove('active');
  }
});
</script>







</body>
</html>
