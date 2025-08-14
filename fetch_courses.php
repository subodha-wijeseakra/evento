<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "evento");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch courses
$sql = "SELECT title, department, description, date, fee FROM ecalendar";
$result = $conn->query($sql);

$courses = [];
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $courses[] = $row;
  }
}
$conn->close();
?>
