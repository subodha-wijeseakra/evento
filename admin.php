<?php
session_start();
$mysqli = new mysqli("localhost","root","","evento");
if($mysqli->connect_error){ die("Connection failed: ".$mysqli->connect_error); }

$message = "";
$message_type = "";
$show_signin_form = true; // Default to Sign In

// Sign Up
if(isset($_POST['signup'])){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $mysqli->prepare("INSERT INTO admin (username,email,password) VALUES (?,?,?)");
    $stmt->bind_param("sss",$username,$email,$password);

    if($stmt->execute()){ 
        $message = "Registration successful! Please sign in.";
        $message_type = "success";
        $show_signin_form = true; // Show Sign In form after successful signup
    } else { 
        $message = "Registration failed: ".$stmt->error; 
        $message_type = "error";
        $show_signin_form = false; // Keep Sign Up form visible on error
    }
}

// Sign In
if(isset($_POST['signin'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT * FROM admin WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if($admin && password_verify($password,$admin['password'])){
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        header("Location: superdashboard.php"); 
        exit;
    } else { 
        $message = "Invalid email or password!"; 
        $message_type = "error";
        $show_signin_form = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>
<style>
/* General */
body, html { margin:0; padding:0; font-family:'Inter',sans-serif; background: linear-gradient(135deg,#f8f9fa,#ffffff); height:100vh; display:flex; justify-content:center; align-items:center; }
.container { display:flex; flex-direction:row; width:90%; max-width:1000px; background:#fff; border-radius:25px; border:1px solid rgba(0,0,0,0.08); overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,0.05); transition: all 0.3s ease; }
.left-panel, .right-panel { flex:1; display:flex; flex-direction:column; justify-content:center; padding:3rem; }
.left-panel { background:#0f172a; color:white; position:relative; }
.left-panel h1 { font-size:2.5rem; margin-bottom:1rem; }
.left-panel p { font-size:1.1rem; color: rgba(255,255,255,0.75); line-height:1.5; }
.left-panel img { width:100%; max-width:250px; margin-top:2rem; }

.right-panel { background:#fff; gap:1.5rem; display:flex; flex-direction:column; }
.form-container { display:flex; flex-direction:column; gap:1rem; }
input {
  padding: 1rem;
  border-radius: 15px;
  border: 1px solid rgba(0,0,0,0.12);
  font-size: 1rem;
  outline: none;
  transition: border 0.3s ease;
  width: 100%;
  margin: 1rem 0; /* Increased top & bottom margin */
}
input:focus { border-color:#0f172a; }
button { padding:0.9rem 2rem; font-weight:600; font-size:1rem; background:#0f172a; color:white; border-radius:12px; border:none; cursor:pointer; transition:all 0.3s ease; margin-top:0.5rem; }
button:hover { transform:translateY(-3px); box-shadow:0 6px 15px rgba(0,0,0,0.1); }
.message { padding:12px 20px; border-radius:12px; font-weight:600; margin-bottom:1rem; text-align:center; }
.message.success { background: #d4edda; color:#155724; border:1px solid #c3e6cb; }
.message.error { background: #f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* Toggle link at bottom */
.toggle-text { text-align:center; font-size:0.95rem; color:rgba(0,0,0,0.6); cursor:pointer; margin-top:0.5rem; }
.toggle-text span { font-weight:600; color:#0f172a; text-decoration:underline; transition: all 0.3s ease; }
.toggle-text span:hover { color:#0d6efd; }

/* Responsive */
@media(max-width:768px){ .container{flex-direction:column;} .left-panel,.right-panel{padding:2rem;} .left-panel img{margin-top:1.5rem;} }
</style>
</head>
<body>

<div class="container">
  <div class="left-panel">
    <h1>Welcome Admin</h1>
    <p>Manage your platform efficiently. Sign in to continue or create a new admin account.</p>
    <img src="https://via.placeholder.com/250" alt="Admin Illustration">
  </div>

  <div class="right-panel">
    <?php if($message){ echo '<div class="message '.($message_type==="success"?"success":"error").'">'.$message.'</div>'; } ?>

    <!-- Sign In Form -->
    <div class="form-container" id="signin-form" style="display:<?= $show_signin_form ? 'flex' : 'none'; ?>;">
      <form method="POST">
        <h2>Sign In</h2>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="signin">Sign In</button>
        <div class="toggle-text">Don't have an account? <span id="show-signup">Sign Up</span></div>
      </form>
    </div>

    <!-- Sign Up Form -->
    <div class="form-container" id="signup-form" style="display:<?= !$show_signin_form ? 'flex' : 'none'; ?>;">
      <form method="POST">
        <h2>Create Account</h2>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="signup">Sign Up</button>
        <div class="toggle-text">Already have an account? <span id="show-signin">Sign In</span></div>
      </form>
    </div>
  </div>
</div>

<script>
const signinForm = document.getElementById('signin-form');
const signupForm = document.getElementById('signup-form');
const showSignup = document.getElementById('show-signup');
const showSignin = document.getElementById('show-signin');

showSignup.addEventListener('click',()=>{
  signinForm.style.display='none';
  signupForm.style.display='flex';
});
showSignin.addEventListener('click',()=>{
  signupForm.style.display='none';
  signinForm.style.display='flex';
});
</script>

</body>
</html>
